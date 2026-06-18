<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FGDispatch;
use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use App\Models\Product;
use App\Models\FGProductCustomerLink;
use Illuminate\Support\Facades\DB;

class FGDispatchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FGDispatch::with(['customer', 'product', 'customerLink', 'creator']);

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            if ($request->filled('job_number')) {
                $query->where('job_number', 'like', '%' . $request->job_number . '%');
            }
            if ($request->filled('dc_number')) {
                $query->where('dc_number', 'like', '%' . $request->dc_number . '%');
            }
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            $totals = [
                'total_quantity_dispatched' => (float)$query->sum('quantity_dispatched'),
                'total_dispatch_amount' => (float)$query->sum('dispatch_amount'),
            ];

            $perPage = $request->input('per_page', 50);
            $results = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'pagination' => $results,
                'totals' => $totals
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'fg_product_customer_link_id' => 'nullable|exists:fg_product_customer_links,id',
            'job_number' => 'nullable|string|max:100',
            'dc_number' => 'required|string|max:100',
            'quantity_dispatched' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $product = Product::findOrFail($request->product_id);
            $dispatchIdentity = $this->resolveDispatchIdentity($product, (int)$request->customer_id, $request->fg_product_customer_link_id);
            if ($dispatchIdentity['error']) {
                return response()->json($dispatchIdentity['error'], 422);
            }

            $lastLedger = FGStockLedger::where('product_id', $request->product_id)
                ->latest('id')->lockForUpdate()->first();
            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $dispatchQty = (float)$request->quantity_dispatched;

            if ($dispatchQty > $currentBalance) {
                return response()->json([
                    'error' => "Insufficient stock. Available: {$currentBalance}, Requested: {$dispatchQty}"
                ], 422);
            }

            $newBalance = $currentBalance - $dispatchQty;

            $dispatch = FGDispatch::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'fg_product_customer_link_id' => $dispatchIdentity['link_id'],
                'dispatch_item_code' => $dispatchIdentity['item_code'],
                'dispatch_item_name' => $dispatchIdentity['item_name'],
                'job_number' => $request->job_number,
                'dc_number' => $request->dc_number,
                'quantity_dispatched' => $dispatchQty,
                'dispatch_rate' => $dispatchIdentity['rate'],
                'dispatch_amount' => $dispatchQty * $dispatchIdentity['rate'],
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            FGStockLedger::create([
                'transaction_type' => 'dispatch',
                'reference_id' => $dispatch->id,
                'product_id' => $request->product_id,
                'customer_id' => $request->customer_id,
                'job_number' => $request->job_number,
                'quantity_in' => 0,
                'quantity_out' => $dispatchQty,
                'balance_after' => $newBalance,
                'transaction_date' => $request->date,
            ]);

            return response()->json($dispatch->load(['customer', 'product', 'customerLink', 'creator']), 201);
        });
    }

    public function show($id)
    {
        return response()->json(FGDispatch::with(['customer', 'product', 'customerLink', 'creator'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'fg_product_customer_link_id' => 'nullable|exists:fg_product_customer_links,id',
            'job_number' => 'nullable|string|max:100',
            'dc_number' => 'required|string|max:100',
            'quantity_dispatched' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $dispatch = FGDispatch::findOrFail($id);
            $oldProductId = $dispatch->product_id;
            $newProductId = (int)$request->product_id;
            $oldQty = (float)$dispatch->quantity_dispatched;
            $newQty = (float)$request->quantity_dispatched;
            $product = Product::findOrFail($newProductId);
            $dispatchIdentity = $this->resolveDispatchIdentity($product, (int)$request->customer_id, $request->fg_product_customer_link_id);
            if ($dispatchIdentity['error']) {
                return response()->json($dispatchIdentity['error'], 422);
            }

            if ($oldProductId !== $newProductId) {
                // 1. Revert old product stock
                $lastLedgerOld = FGStockLedger::where('product_id', $oldProductId)
                    ->latest('id')->lockForUpdate()->first();
                $oldProdBalance = $lastLedgerOld ? (float)$lastLedgerOld->balance_after : 0;
                
                FGStockLedger::create([
                    'transaction_type' => 'adjustment',
                    'reference_id' => $dispatch->id,
                    'product_id' => $oldProductId,
                    'customer_id' => $dispatch->customer_id,
                    'job_number' => $dispatch->job_number,
                    'quantity_in' => $oldQty,
                    'quantity_out' => 0,
                    'balance_after' => $oldProdBalance + $oldQty,
                    'transaction_date' => $request->date,
                ]);

                // 2. Deduct from new product stock
                $lastLedgerNew = FGStockLedger::where('product_id', $newProductId)
                    ->latest('id')->lockForUpdate()->first();
                $newProdBalance = $lastLedgerNew ? (float)$lastLedgerNew->balance_after : 0;
                
                if ($newQty > $newProdBalance) {
                    return response()->json([
                        'error' => "Insufficient stock in new product. Available: {$newProdBalance}"
                    ], 422);
                }

                FGStockLedger::create([
                    'transaction_type' => 'dispatch',
                    'reference_id' => $dispatch->id,
                    'product_id' => $newProductId,
                    'customer_id' => $request->customer_id,
                    'job_number' => $request->job_number,
                    'quantity_in' => 0,
                    'quantity_out' => $newQty,
                    'balance_after' => $newProdBalance - $newQty,
                    'transaction_date' => $request->date,
                ]);
            } else {
                // Same product, handle quantity change
                $diff = $newQty - $oldQty;
                if ($diff != 0) {
                    $lastLedger = FGStockLedger::where('product_id', $newProductId)
                        ->latest('id')->lockForUpdate()->first();
                    $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
                    $newBalance = $currentBalance - $diff;

                    if ($newBalance < 0) {
                        return response()->json([
                            'error' => "Insufficient stock for update. Available: {$currentBalance}"
                        ], 422);
                    }

                    FGStockLedger::create([
                        'transaction_type' => 'adjustment',
                        'reference_id' => $dispatch->id,
                        'product_id' => $newProductId,
                        'customer_id' => $request->customer_id,
                        'job_number' => $request->job_number,
                        'quantity_in' => $diff < 0 ? abs($diff) : 0,
                        'quantity_out' => $diff > 0 ? $diff : 0,
                        'balance_after' => $newBalance,
                        'transaction_date' => $request->date,
                    ]);
                }
            }

            $dispatch->update([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'fg_product_customer_link_id' => $dispatchIdentity['link_id'],
                'dispatch_item_code' => $dispatchIdentity['item_code'],
                'dispatch_item_name' => $dispatchIdentity['item_name'],
                'job_number' => $request->job_number,
                'dc_number' => $request->dc_number,
                'quantity_dispatched' => $newQty,
                'dispatch_rate' => $dispatchIdentity['rate'],
                'dispatch_amount' => $newQty * $dispatchIdentity['rate'],
                'remarks' => $request->remarks,
            ]);

            return response()->json($dispatch->load(['customer', 'product', 'customerLink', 'creator']));
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $dispatch = FGDispatch::findOrFail($id);
            $lastLedger = FGStockLedger::where('product_id', $dispatch->product_id)
                ->latest('id')->lockForUpdate()->first();
            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $newBalance = $currentBalance + (float)$dispatch->quantity_dispatched;

            FGStockLedger::create([
                'transaction_type' => 'adjustment',
                'reference_id' => $dispatch->id,
                'product_id' => $dispatch->product_id,
                'customer_id' => $dispatch->customer_id,
                'job_number' => $dispatch->job_number,
                'quantity_in' => $dispatch->quantity_dispatched,
                'quantity_out' => 0,
                'balance_after' => $newBalance,
                'transaction_date' => now()->toDateString(),
            ]);

            $dispatch->delete();
            return response()->json(['message' => 'Dispatch deleted successfully.']);
        });
    }

    public function getAvailableStock($productId)
    {
        $lastLedger = FGStockLedger::where('product_id', $productId)->latest('id')->first();
        return response()->json(['available_stock' => $lastLedger ? (float)$lastLedger->balance_after : 0]);
    }

    public function getJobDetails($jobNumber)
    {
        try {
            // Find the job in receipts to get customer and product
            $jobReceipts = FGReceipt::with(['customer', 'product.customerLinks.customer'])
                ->where('job_number', $jobNumber)
                ->get();

            if ($jobReceipts->isEmpty()) {
                return response()->json(['error' => 'Job number not found.'], 404);
            }

            // Assume one Job # corresponds to one Product for simplicity in this fetch
            $firstReceipt = $jobReceipts->first();
            $productId = $firstReceipt->product_id;
            $customer = $firstReceipt->customer;
            $product = $firstReceipt->product;

            // Calculate total produced for this job and product
            $totalProduced = FGReceipt::where('job_number', $jobNumber)
                ->where('product_id', $productId)
                ->sum('quantity_produced');

            // Get dispatch history for this job and product
            $dispatches = FGDispatch::with(['creator'])
                ->where('job_number', $jobNumber)
                ->where('product_id', $productId)
                ->orderBy('date', 'desc')
                ->get();

            $totalDispatched = $dispatches->sum('quantity_dispatched');
            $balance = (float)$totalProduced - (float)$totalDispatched;

            return response()->json([
                'job_number' => $jobNumber,
                'customer' => $customer,
                'product' => $product,
                'linked_customers' => $this->linkedCustomersForProduct($product),
                'total_produced' => (float)$totalProduced,
                'total_dispatched' => (float)$totalDispatched,
                'balance' => $balance,
                'history' => $dispatches
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProductDetails($productId)
    {
        try {
            $product = \App\Models\Product::with(['customer', 'customerLinks.customer'])->findOrFail($productId);
            $lastLedger = \App\Models\FGStockLedger::where('product_id', $productId)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : 0;

            // Get dispatch history for this product
            $dispatches = \App\Models\FGDispatch::with(['creator'])
                ->where('product_id', $productId)
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'job_number' => 'MANUAL/OPENING',
                'customer' => $product->customer,
                'product' => $product,
                'linked_customers' => $this->linkedCustomersForProduct($product),
                'total_produced' => $balance + $dispatches->sum('quantity_dispatched'),
                'total_dispatched' => $dispatches->sum('quantity_dispatched'),
                'balance' => $balance,
                'history' => $dispatches
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function resolveDispatchIdentity(Product $product, int $customerId, $linkId): array
    {
        if ($product->dispatch_policy !== Product::POLICY_SHARED) {
            if ((int)$product->customer_id !== $customerId) {
                return [
                    'error' => [
                        'error' => 'This finished good is customer restricted and can only be dispatched to its assigned customer.',
                        'product_id' => $product->id,
                        'dispatch_policy' => $product->dispatch_policy,
                    ],
                ];
            }

            return [
                'error' => null,
                'link_id' => null,
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'rate' => (float)$product->rate,
            ];
        }

        if ((int)$product->customer_id === $customerId && empty($linkId)) {
            return [
                'error' => null,
                'link_id' => null,
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'rate' => (float)$product->rate,
            ];
        }

        $link = FGProductCustomerLink::where('product_id', $product->id)
            ->where('customer_id', $customerId)
            ->where('status', 'Active')
            ->when($linkId, fn ($query) => $query->where('id', $linkId))
            ->orderByDesc('is_default')
            ->first();

        if (!$link) {
            return [
                'error' => [
                    'error' => 'This shared product is not linked with the selected customer/item. Please add the customer item mapping in Product Master first.',
                    'product_id' => $product->id,
                    'dispatch_policy' => $product->dispatch_policy,
                ],
            ];
        }

        return [
            'error' => null,
            'link_id' => $link->id,
            'item_code' => $link->customer_item_code,
            'item_name' => $link->customer_item_name,
            'rate' => (float)$link->customer_rate,
        ];
    }

    private function linkedCustomersForProduct(?Product $product): array
    {
        if (!$product || $product->dispatch_policy !== Product::POLICY_SHARED) {
            return [];
        }

        return $product->customerLinks
            ->where('status', 'Active')
            ->groupBy('customer_id')
            ->map(function ($links) {
                $first = $links->first();
                return [
                    'id' => $first->customer_id,
                    'name' => $first->customer?->name,
                    'items_count' => $links->count(),
                ];
            })
            ->values()
            ->all();
    }
}
