<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FGProductCustomerLink;
use App\Models\FGStockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::with(['customer', 'customerLinks.customer']);

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('item_code', 'like', "%{$search}%")
                      ->orWhere('item_name', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $products = $query->orderBy('id', 'desc')->paginate(50);

            // Append current balance to each product
            $products->getCollection()->each(function ($product) {
                $lastLedger = FGStockLedger::where('product_id', $product->id)
                    ->latest('id')->first();
                $product->current_balance = $lastLedger ? $lastLedger->balance_after : $product->opening_balance;
            });

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'item_code' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'rate' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|integer|min:0',
            'dispatch_policy' => ['nullable', Rule::in([Product::POLICY_SHARED, Product::POLICY_RESTRICTED])],
            'customer_links' => 'nullable|array',
            'customer_links.*.customer_id' => 'nullable|exists:customers,id',
            'customer_links.*.customer_item_code' => 'nullable|string|max:100',
            'customer_links.*.customer_item_name' => 'nullable|string|max:255',
            'customer_links.*.customer_rate' => 'nullable|numeric|min:0',
            'customer_links.*.status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);

        if (($request->dispatch_policy ?: Product::POLICY_RESTRICTED) === Product::POLICY_SHARED
            && count($request->input('customer_links', [])) === 0) {
            return response()->json(['error' => 'Add at least one linked customer item for a shared product.'], 422);
        }



        return DB::transaction(function () use ($request) {
            $product = Product::create([
                'customer_id' => $request->customer_id,
                'item_code' => $request->item_code,
                'item_name' => $request->item_name,
                'rate' => $request->rate,
                'opening_balance' => (int) ($request->opening_balance ?? 0),
                'dispatch_policy' => $request->dispatch_policy ?: Product::POLICY_RESTRICTED,
            ]);

            // Record opening balance in ledger
            FGStockLedger::recalculateForProduct($product->id);

            $this->syncCustomerLinks($product, $request);

            return response()->json($product->load(['customer', 'customerLinks.customer']), 201);
        });
    }

    public function show($id)
    {
        $product = Product::with(['customer', 'customerLinks.customer'])->findOrFail($id);
        $lastLedger = FGStockLedger::where('product_id', $product->id)
            ->latest('id')->first();
        $product->current_balance = $lastLedger ? $lastLedger->balance_after : $product->opening_balance;
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'item_code' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'rate' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|integer|min:0',
            'dispatch_policy' => ['nullable', Rule::in([Product::POLICY_SHARED, Product::POLICY_RESTRICTED])],
            'customer_links' => 'nullable|array',
            'customer_links.*.customer_id' => 'nullable|exists:customers,id',
            'customer_links.*.customer_item_code' => 'nullable|string|max:100',
            'customer_links.*.customer_item_name' => 'nullable|string|max:255',
            'customer_links.*.customer_rate' => 'nullable|numeric|min:0',
            'customer_links.*.status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);

        if (($request->dispatch_policy ?: Product::POLICY_RESTRICTED) === Product::POLICY_SHARED
            && count($request->input('customer_links', [])) === 0) {
            return response()->json(['error' => 'Add at least one linked customer item for a shared product.'], 422);
        }

        return DB::transaction(function () use ($request, $product) {
            $oldOpening = (float) $product->opening_balance;
            $newOpening = (int) ($request->opening_balance ?? 0);

            $product->update([
                'customer_id' => $request->customer_id,
                'item_code' => $request->item_code,
                'item_name' => $request->item_name,
                'rate' => $request->rate,
                'opening_balance' => $newOpening,
                'dispatch_policy' => $request->dispatch_policy ?: Product::POLICY_RESTRICTED,
            ]);

            $this->syncCustomerLinks($product, $request);

            FGStockLedger::recalculateForProduct($product->id);

            return response()->json($product->load(['customer', 'customerLinks.customer']));
        });
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Prevent deletion if transactions exist
        $hasReceipts = $product->fgReceipts()->exists();
        $hasDispatches = $product->fgDispatches()->exists();

        if ($hasReceipts || $hasDispatches) {
            return response()->json([
                'error' => 'Cannot delete product with existing receipts or dispatches.'
            ], 400);
        }

        return DB::transaction(function () use ($product) {
            $product->stockLedger()->delete();
            $product->delete();
            return response()->json(['message' => 'Product deleted successfully.']);
        });
    }

    /**
     * Get products filtered by customer_id (for dropdown)
     */
    public function byCustomer($customerId)
    {
        $products = Product::with(['customer', 'customerLinks.customer'])
            ->where(function ($query) use ($customerId) {
                $query->where('customer_id', $customerId)
                    ->orWhereHas('customerLinks', function ($linkQuery) use ($customerId) {
                        $linkQuery->where('customer_id', $customerId)
                            ->where('status', 'Active');
                    });
            })
            ->orderBy('item_name')
            ->get();

        return response()->json($products);
    }

    public function customerItems($customerId)
    {
        $products = Product::where('customer_id', $customerId)
            ->orderBy('item_name')
            ->get(['id', 'customer_id', 'item_code', 'item_name', 'rate']);

        return response()->json($products);
    }

    private function syncCustomerLinks(Product $product, Request $request): void
    {
        if ($product->dispatch_policy !== Product::POLICY_SHARED) {
            $product->customerLinks()->delete();
            return;
        }

        $links = collect($request->input('customer_links', []))
            ->filter(fn ($link) => !empty($link['customer_id']) && !empty($link['customer_item_code']) && !empty($link['customer_item_name']))
            ->values();

        $seen = [];
        $ids = [];

        foreach ($links as $index => $link) {
            $key = $product->id . ':' . $link['customer_id'] . ':' . strtoupper(trim($link['customer_item_code']));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $record = FGProductCustomerLink::updateOrCreate(
                [
                    'id' => $link['id'] ?? null,
                    'product_id' => $product->id,
                ],
                [
                    'product_id' => $product->id,
                    'customer_id' => $link['customer_id'],
                    'customer_item_code' => trim($link['customer_item_code']),
                    'customer_item_name' => trim($link['customer_item_name']),
                    'customer_rate' => (float)($link['customer_rate'] ?? 0),
                    'is_default' => (bool)($link['is_default'] ?? $index === 0),
                    'status' => $link['status'] ?? 'Active',
                ]
            );

            $ids[] = $record->id;
        }

        $product->customerLinks()
            ->whereNotIn('id', $ids ?: [0])
            ->delete();
    }
}
