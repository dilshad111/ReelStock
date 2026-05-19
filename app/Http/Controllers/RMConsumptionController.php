<?php

namespace App\Http\Controllers;

use App\Models\RMConsumption;
use App\Domains\RawMaterial\Actions\RecordRMConsumptionAction;
use App\Domains\RawMaterial\DTOs\RMConsumptionDTO;
use Illuminate\Http\Request;

class RMConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $query = RMConsumption::with(['items.item']);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        return response()->json($query->latest()->paginate($request->per_page ?? 15));
    }

    public function store(Request $request, RecordRMConsumptionAction $action)
    {
        $request->validate([
            'voucher_no' => 'required|string|unique:rm_consumptions,voucher_no',
            'job_card_id' => 'nullable', // Will be validated against job_cards in Phase 2
            'date' => 'required|date',
            'department' => 'nullable|string',
            'issued_to' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rm_item_id' => 'required|exists:rm_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $dto = RMConsumptionDTO::fromRequest($request);
            $consumption = $action->execute($dto);
            return response()->json($consumption, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(RMConsumption $rmConsumption)
    {
        return response()->json($rmConsumption->load(['items.item', 'creator']));
    }
}
