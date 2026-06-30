<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use App\Services\FGInventoryService;
use Illuminate\Support\Facades\DB;

class FGReceiptController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FGReceipt::with(['customer', 'product', 'creator'])
                ->whereNotIn('id', function($q) {
                    $q->select('reference_id')
                      ->from('fg_stock_ledger')
                      ->where('transaction_type', 'receipt_reversal');
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

            if ($request->filled('item_search')) {
                $itemSearch = $request->item_search;
                $query->whereHas('product', function ($productQuery) use ($itemSearch) {
                    $productQuery->where('item_code', 'like', '%' . $itemSearch . '%')
                        ->orWhere('item_name', 'like', '%' . $itemSearch . '%');
                });
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            $totals = [
                'total_quantity_produced' => (float)$query->sum('quantity_produced'),
                'total_wastage' => (float)$query->sum('wastage'),
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
            'job_card_id' => 'nullable|exists:job_cards,id',
            'job_number' => 'required|string|max:100',
            'production_date' => 'required|date',
            'quantity_produced' => 'required|numeric|min:0.01',
            'carton_price' => 'nullable|numeric|min:0',
            'wastage' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $warehouseId = $request->input('warehouse_id', 1);

            $receipt = FGReceipt::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'warehouse_id' => $warehouseId,
                'job_card_id' => $request->job_card_id,
                'job_number' => $request->job_number,
                'production_date' => $request->production_date,
                'quantity_produced' => $request->quantity_produced,
                'carton_price' => $request->carton_price,
                'wastage' => $request->wastage ?? 0,
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            // Log movement through FGInventoryService
            $dateStr = ($receipt->date instanceof \DateTimeInterface) ? $receipt->date->format('Y-m-d') : substr((string)$receipt->date, 0, 10);
            FGInventoryService::recordMovement(
                'receipt',
                $receipt->id,
                'RCPT-' . $receipt->id,
                (int)$receipt->product_id,
                (int)$warehouseId,
                (int)$receipt->customer_id,
                $receipt->job_number,
                (float)$receipt->quantity_produced,
                0.0,
                $dateStr,
                $request->user()->id,
                $receipt->remarks
            );

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
                $receipt = FGReceipt::findOrFail($id);

                // Check if already reversed
                $alreadyReversed = FGStockLedger::where('transaction_type', 'receipt_reversal')
                    ->where('reference_id', $receipt->id)
                    ->exists();
                if ($alreadyReversed) {
                    return response()->json(['error' => 'This receipt has already been reversed.'], 422);
                }

                $dateStr = ($receipt->date instanceof \DateTimeInterface) ? $receipt->date->format('Y-m-d') : substr((string)$receipt->date, 0, 10);

                // Record the reversal movement (reduces stock)
                FGInventoryService::recordMovement(
                    'receipt_reversal',
                    $receipt->id,
                    'REV-RCPT-' . $receipt->id,
                    (int)$receipt->product_id,
                    (int)$receipt->warehouse_id,
                    (int)$receipt->customer_id,
                    $receipt->job_number,
                    0.0,
                    (float)$receipt->quantity_produced,
                    $dateStr,
                    $request->user()->id,
                    'Reversal of Receipt #' . $receipt->id . '. Reason: ' . ($request->input('reason') ?? 'User correction.')
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Receipt reversed successfully.'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
