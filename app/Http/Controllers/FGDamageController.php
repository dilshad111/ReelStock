<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFGDamageRequest;
use App\Models\FGDamage;
use App\Models\CurrentFGStock;
use App\Models\Warehouse;
use App\Models\FGStockLedger;
use App\Services\FGInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FGDamageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FGDamage::with(['customer', 'product', 'warehouse', 'creator', 'approver', 'reverser']);

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('job_number')) {
                $query->where('job_number', 'like', '%' . $request->job_number . '%');
            }
            if ($request->filled('damage_number')) {
                $query->where('damage_number', 'like', '%' . $request->damage_number . '%');
            }
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            $totals = [
                'total_quantity' => (float)$query->sum('quantity'),
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

    public function store(StoreFGDamageRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $productId = $request->product_id;
            $warehouseId = $request->warehouse_id;
            $qty = (float)$request->quantity;

            // 1. Verify warehouse negative stock allowance
            $warehouse = Warehouse::findOrFail($warehouseId);
            if ($warehouse->status !== 'Active') {
                throw ValidationException::withMessages(['warehouse_id' => 'The selected warehouse is not active.']);
            }

            if (!$warehouse->allow_negative_stock) {
                $available = (float)CurrentFGStock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->value('quantity');

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock. Available quantity in warehouse {$warehouse->code}: " . number_format($available, 2)
                    ]);
                }
            }

            // 2. Generate sequential damage number DMG-XXXXXX
            $nextId = (FGDamage::max('id') ?? 0) + 1;
            $damageNumber = 'DMG-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // 3. Create damage record
            $damage = FGDamage::create([
                'damage_number' => $damageNumber,
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'job_card_id' => $request->job_card_id,
                'job_number' => $request->job_number,
                'quantity' => $qty,
                'reason' => $request->reason,
                'remarks' => $request->remarks,
                'approved_by' => $request->approved_by,
                'created_by' => $request->user()->id,
                'status' => 'posted',
            ]);

            // 4. Log movement (reduces stock)
            $dateStr = ($damage->date instanceof \DateTimeInterface) ? $damage->date->format('Y-m-d') : substr((string)$damage->date, 0, 10);
            FGInventoryService::recordMovement(
                'damage',
                $damage->id,
                $damage->damage_number,
                (int)$damage->product_id,
                (int)$damage->warehouse_id,
                (int)$damage->customer_id,
                $damage->job_number,
                0.0,
                $qty,
                $dateStr,
                $request->user()->id,
                $damage->remarks
            );

            return response()->json($damage->load(['customer', 'product', 'warehouse', 'creator', 'approver']), 201);
        });
    }

    public function show($id)
    {
        return response()->json(FGDamage::with(['customer', 'product', 'warehouse', 'creator', 'approver', 'reverser'])->findOrFail($id));
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
                $damage = FGDamage::findOrFail($id);

                if ($damage->status === 'reversed') {
                    return response()->json(['error' => 'This damage transaction has already been reversed.'], 422);
                }

                $qty = (float)$damage->quantity;

                // 1. Update status
                $damage->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'reversed_by' => $request->user()->id,
                    'reversal_reason' => $request->input('reason') ?? 'User correction.',
                ]);

                // 2. Record the reversal movement (increases stock)
                FGInventoryService::recordMovement(
                    'damage_reversal',
                    $damage->id,
                    'REV-' . $damage->damage_number,
                    (int)$damage->product_id,
                    (int)$damage->warehouse_id,
                    (int)$damage->customer_id,
                    $damage->job_number,
                    $qty,
                    0.0,
                    now()->toDateString(),
                    $request->user()->id,
                    'Reversal of Damage #' . $damage->id . '. Reason: ' . ($request->input('reason') ?? 'User correction.')
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Damage entry reversed successfully.'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getWarehouseStock($productId, $warehouseId)
    {
        $currentStock = \App\Models\CurrentFGStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity');
        return response()->json(['available_stock' => (float)($currentStock ?? 0.0)]);
    }
}
