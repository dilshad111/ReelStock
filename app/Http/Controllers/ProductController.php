<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FGStockLedger;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::with('customer');

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
            'opening_balance' => 'nullable|numeric|min:0',
        ]);



        return DB::transaction(function () use ($request) {
            $product = Product::create([
                'customer_id' => $request->customer_id,
                'item_code' => $request->item_code,
                'item_name' => $request->item_name,
                'rate' => $request->rate,
                'opening_balance' => $request->opening_balance ?? 0,
            ]);

            // Record opening balance in ledger if > 0
            if ($product->opening_balance > 0) {
                FGStockLedger::create([
                    'transaction_type' => 'opening',
                    'reference_id' => $product->id,
                    'product_id' => $product->id,
                    'customer_id' => $product->customer_id,
                    'job_number' => null,
                    'quantity_in' => $product->opening_balance,
                    'quantity_out' => 0,
                    'balance_after' => $product->opening_balance,
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            return response()->json($product->load('customer'), 201);
        });
    }

    public function show($id)
    {
        $product = Product::with('customer')->findOrFail($id);
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
        ]);



        $product->update($request->only(['customer_id', 'item_code', 'item_name', 'rate']));

        return response()->json($product->load('customer'));
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
        $products = Product::where('customer_id', $customerId)
            ->orderBy('item_name')
            ->get();

        return response()->json($products);
    }
}
