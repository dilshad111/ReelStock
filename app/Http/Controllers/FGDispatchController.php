<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FGDispatch;
use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use App\Models\Product;
use App\Models\FGProductCustomerLink;
use App\Services\FGInventoryService;
use Illuminate\Support\Facades\DB;

class FGDispatchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FGDispatch::with(['customer', 'product', 'customerLink', 'creator'])
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'dispatch_reversal');
                });

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            if ($request->filled('job_number')) {
                $query->where('job_number', 'like', '%' . $request->job_number . '%');
            }
            if ($request->filled('dc_number')) {
                $query->where('dc_number', 'like', '%' . $request->dc_number . '%');
            }
            if ($request->filled('item_search')) {
                $itemSearch = $request->item_search;
                $query->where(function ($searchQuery) use ($itemSearch) {
                    $searchQuery->where('dispatch_item_code', 'like', '%' . $itemSearch . '%')
                        ->orWhere('dispatch_item_name', 'like', '%' . $itemSearch . '%')
                        ->orWhereHas('product', function ($productQuery) use ($itemSearch) {
                            $productQuery->where('item_code', 'like', '%' . $itemSearch . '%')
                                ->orWhere('item_name', 'like', '%' . $itemSearch . '%');
                        });
                });
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
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'fg_product_customer_link_id' => 'nullable|exists:fg_product_customer_links,id',
            'job_number' => 'nullable|string|max:100',
            'dc_number' => 'required|string|max:100',
            'quantity_dispatched' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $product = Product::findOrFail($request->product_id);
            $warehouseId = $request->input('warehouse_id', 1);

            $dispatchIdentity = $this->resolveDispatchIdentity($product, (int)$request->customer_id, $request->fg_product_customer_link_id);
            if ($dispatchIdentity['error']) {
                return response()->json($dispatchIdentity['error'], 422);
            }

            $dispatchQty = (float)$request->quantity_dispatched;

            // 1. Create dispatch entry
            $dispatch = FGDispatch::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'warehouse_id' => $warehouseId,
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

            // 2. Log movement and enforce validations via FGInventoryService
            $dateStr = ($dispatch->date instanceof \DateTimeInterface) ? $dispatch->date->format('Y-m-d') : substr((string)$dispatch->date, 0, 10);
            try {
                FGInventoryService::recordMovement(
                    'dispatch',
                    $dispatch->id,
                    $dispatch->dc_number,
                    (int)$dispatch->product_id,
                    (int)$warehouseId,
                    (int)$dispatch->customer_id,
                    $dispatch->job_number,
                    0.0,
                    $dispatchQty,
                    $dateStr,
                    $request->user()->id,
                    $dispatch->remarks
                );
            } catch (\Exception $e) {
                // Let transaction rollback and throw exception to user
                throw $e;
            }

            return response()->json($dispatch->load(['customer', 'product', 'customerLink', 'creator']), 201);
        });
    }

    public function show($id)
    {
        return response()->json(FGDispatch::with(['customer', 'product', 'customerLink', 'creator'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'error' => 'Editing posted transactions directly is prohibited to maintain audit trails. Please use the reversal workflow.'
        ], 403);
    }

    public function destroy($id)
    {
        return response()->json([
            'error' => 'Deleting posted transactions directly is prohibited to maintain audit trails. Please use the reversal workflow.'
        ], 403);
    }

    public function reverse(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $dispatch = FGDispatch::findOrFail($id);

                // Check if already reversed
                $alreadyReversed = FGStockLedger::where('transaction_type', 'dispatch_reversal')
                    ->where('reference_id', $dispatch->id)
                    ->exists();
                if ($alreadyReversed) {
                    return response()->json(['error' => 'This dispatch has already been reversed.'], 422);
                }

                $dateStr = ($dispatch->date instanceof \DateTimeInterface) ? $dispatch->date->format('Y-m-d') : substr((string)$dispatch->date, 0, 10);

                // Record the reversal movement (increases stock)
                FGInventoryService::recordMovement(
                    'dispatch_reversal',
                    $dispatch->id,
                    'REV-' . $dispatch->dc_number,
                    (int)$dispatch->product_id,
                    (int)$dispatch->warehouse_id,
                    (int)$dispatch->customer_id,
                    $dispatch->job_number,
                    (float)$dispatch->quantity_dispatched,
                    0.0,
                    $dateStr,
                    $request->user()->id,
                    'Reversal of Dispatch #' . $dispatch->id . '. Reason: ' . ($request->input('reason') ?? 'User correction.')
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Dispatch reversed successfully.'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getJobMovementDetail(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'product_id' => 'nullable|exists:products,id',
        ]);

        try {
            $jobNumber = $request->job_number;
            $productId = $request->product_id;

            $receiptQuery = FGReceipt::with(['customer', 'product'])
                ->where('job_number', $jobNumber)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'receipt_reversal');
                });

            $dispatchQuery = FGDispatch::with(['customer', 'product'])
                ->where('job_number', $jobNumber)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'dispatch_reversal');
                });

            if ($productId) {
                $receiptQuery->where('product_id', $productId);
                $dispatchQuery->where('product_id', $productId);
            }

            $receipts = $receiptQuery->orderBy('date')->orderBy('id')->get();
            $dispatches = $dispatchQuery->orderBy('date')->orderBy('id')->get();

            if ($receipts->isEmpty() && $dispatches->isEmpty()) {
                return response()->json(['error' => 'No receipt or dispatch entries found for this job.'], 404);
            }

            $movements = collect();

            foreach ($receipts as $receipt) {
                $movements->push([
                    'type' => 'receipt',
                    'source_id' => $receipt->id,
                    'date' => $receipt->date,
                    'customer_name' => $receipt->customer?->name,
                    'item_code' => $receipt->product?->item_code,
                    'item_name' => $receipt->product?->item_name,
                    'dc_number' => null,
                    'receipt_qty' => (float)$receipt->quantity_produced,
                    'dispatch_qty' => 0,
                    'sort_order' => 0,
                ]);
            }

            foreach ($dispatches as $dispatch) {
                $movements->push([
                    'type' => 'dispatch',
                    'source_id' => $dispatch->id,
                    'date' => $dispatch->date,
                    'customer_name' => $dispatch->customer?->name,
                    'item_code' => $dispatch->dispatch_item_code ?: $dispatch->product?->item_code,
                    'item_name' => $dispatch->dispatch_item_name ?: $dispatch->product?->item_name,
                    'dc_number' => $dispatch->dc_number,
                    'receipt_qty' => 0,
                    'dispatch_qty' => (float)$dispatch->quantity_dispatched,
                    'sort_order' => 1,
                ]);
            }

            $runningBalance = 0;
            $movements = $movements
                ->sort(function ($a, $b) {
                    return [strtotime((string)$a['date']), $a['sort_order'], $a['source_id']]
                        <=> [strtotime((string)$b['date']), $b['sort_order'], $b['source_id']];
                })
                ->values()
                ->map(function ($movement) use (&$runningBalance) {
                    $runningBalance += (float)$movement['receipt_qty'];
                    $runningBalance -= (float)$movement['dispatch_qty'];
                    $movement['balance'] = $runningBalance;
                    unset($movement['sort_order']);
                    return $movement;
                });

            $totalProduced = (float)$receipts->sum('quantity_produced');
            $totalDispatched = (float)$dispatches->sum('quantity_dispatched');
            $firstEntry = $receipts->first() ?: $dispatches->first();

            return response()->json([
                'job_number' => $jobNumber,
                'customer' => $firstEntry?->customer,
                'product' => $firstEntry?->product,
                'receipts' => $receipts,
                'dispatches' => $dispatches,
                'movements' => $movements,
                'total_produced' => $totalProduced,
                'total_dispatched' => $totalDispatched,
                'remaining_balance' => $totalProduced - $totalDispatched,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAvailableStock($productId)
    {
        // Read directly from cached CurrentFGStock
        $currentStock = \App\Models\CurrentFGStock::where('product_id', $productId)->sum('quantity');
        return response()->json(['available_stock' => (float)$currentStock]);
    }

    public function getJobDetails($jobNumber)
    {
        try {
            $jobReceipts = FGReceipt::with(['customer', 'product.customerLinks.customer'])
                ->where('job_number', $jobNumber)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'receipt_reversal');
                })
                ->get();

            if ($jobReceipts->isEmpty()) {
                return response()->json(['error' => 'Job number not found.'], 404);
            }

            $firstReceipt = $jobReceipts->first();
            $productId = $firstReceipt->product_id;
            $customer = $firstReceipt->customer;
            $product = $firstReceipt->product;

            $totalProduced = FGReceipt::where('job_number', $jobNumber)
                ->where('product_id', $productId)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'receipt_reversal');
                })
                ->sum('quantity_produced');

            $dispatches = FGDispatch::with(['creator'])
                ->where('job_number', $jobNumber)
                ->where('product_id', $productId)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'dispatch_reversal');
                })
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
            $currentStock = \App\Models\CurrentFGStock::where('product_id', $productId)->sum('quantity');

            $dispatches = \App\Models\FGDispatch::with(['creator'])
                ->where('product_id', $productId)
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'dispatch_reversal');
                })
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'job_number' => 'MANUAL/OPENING',
                'customer' => $product->customer,
                'product' => $product,
                'linked_customers' => $this->linkedCustomersForProduct($product),
                'total_produced' => (float)$currentStock + (float)$dispatches->sum('quantity_dispatched'),
                'total_dispatched' => (float)$dispatches->sum('quantity_dispatched'),
                'balance' => (float)$currentStock,
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
