<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use Illuminate\Support\Facades\DB;

class FGReceiptController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FGReceipt::with(['customer', 'product', 'creator']);

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->filled('job_number')) {
                $query->where('job_number', 'like', '%' . $request->job_number . '%');
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            return response()->json(
                $query->orderBy('id', 'desc')->paginate(50)
            );
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
            'job_number' => 'required|string|max:100',
            'production_date' => 'required|date',
            'quantity_produced' => 'required|numeric|min:0.01',
            'carton_price' => 'nullable|numeric|min:0',
            'wastage' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $receipt = FGReceipt::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'job_number' => $request->job_number,
                'production_date' => $request->production_date,
                'quantity_produced' => $request->quantity_produced,
                'carton_price' => $request->carton_price,
                'wastage' => $request->wastage ?? 0,
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            // Get current balance for this product
            $lastLedger = FGStockLedger::where('product_id', $request->product_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $newBalance = $currentBalance + (float)$request->quantity_produced;

            // Record in ledger
            FGStockLedger::create([
                'transaction_type' => 'receipt',
                'reference_id' => $receipt->id,
                'product_id' => $request->product_id,
                'customer_id' => $request->customer_id,
                'job_number' => $request->job_number,
                'quantity_in' => $request->quantity_produced,
                'quantity_out' => 0,
                'balance_after' => $newBalance,
                'transaction_date' => $request->date,
            ]);

            return response()->json($receipt->load(['customer', 'product', 'creator']), 201);
        });
    }

    public function show($id)
    {
        return response()->json(
            FGReceipt::with(['customer', 'product', 'creator'])->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'job_number' => 'required|string|max:100',
            'production_date' => 'required|date',
            'quantity_produced' => 'required|numeric|min:0.01',
            'carton_price' => 'nullable|numeric|min:0',
            'wastage' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $receipt = FGReceipt::findOrFail($id);
            $oldQty = (float)$receipt->quantity_produced;
            $newQty = (float)$request->quantity_produced;
            $diff = $newQty - $oldQty;

            $receipt->update([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'job_number' => $request->job_number,
                'production_date' => $request->production_date,
                'quantity_produced' => $newQty,
                'carton_price' => $request->carton_price,
                'wastage' => $request->wastage ?? 0,
                'remarks' => $request->remarks,
            ]);

            // If quantity changed, record adjustment in ledger
            if ($diff != 0) {
                $lastLedger = FGStockLedger::where('product_id', $request->product_id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
                $newBalance = $currentBalance + $diff;

                if ($newBalance < 0) {
                    throw new \Exception('Update would result in negative stock balance.');
                }

                FGStockLedger::create([
                    'transaction_type' => 'adjustment',
                    'reference_id' => $receipt->id,
                    'product_id' => $request->product_id,
                    'customer_id' => $request->customer_id,
                    'job_number' => $request->job_number,
                    'quantity_in' => $diff > 0 ? $diff : 0,
                    'quantity_out' => $diff < 0 ? abs($diff) : 0,
                    'balance_after' => $newBalance,
                    'transaction_date' => $request->date,
                ]);
            }

            return response()->json($receipt->load(['customer', 'product', 'creator']));
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $receipt = FGReceipt::findOrFail($id);

            // Check if removing this would cause negative balance
            $lastLedger = FGStockLedger::where('product_id', $receipt->product_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $newBalance = $currentBalance - (float)$receipt->quantity_produced;

            if ($newBalance < 0) {
                return response()->json([
                    'error' => 'Cannot delete: would result in negative stock. Dispatch entries may depend on this receipt.'
                ], 400);
            }

            // Record reversal in ledger
            FGStockLedger::create([
                'transaction_type' => 'adjustment',
                'reference_id' => $receipt->id,
                'product_id' => $receipt->product_id,
                'customer_id' => $receipt->customer_id,
                'job_number' => $receipt->job_number,
                'quantity_in' => 0,
                'quantity_out' => $receipt->quantity_produced,
                'balance_after' => $newBalance,
                'transaction_date' => now()->toDateString(),
            ]);

            $receipt->delete();

            return response()->json(['message' => 'Receipt deleted successfully.']);
        });
    }
}
