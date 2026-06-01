<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Models\RMItemSupplierRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RMItemSupplierRateController extends Controller
{
    public function index(Request $request)
    {
        $query = RMItemSupplierRate::with(['item:id,name,code', 'supplier:id,name'])->orderBy('id', 'desc');

        if ($request->filled('rm_item_id')) {
            $query->where('rm_item_id', $request->rm_item_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $rate = RMItemSupplierRate::create($data);

        return response()->json($rate->load(['item:id,name,code', 'supplier:id,name']), 201);
    }

    public function update(Request $request, RMItemSupplierRate $rmItemSupplierRate)
    {
        $data = $this->validatePayload($request, $rmItemSupplierRate->id);
        $rmItemSupplierRate->update($data);

        return response()->json($rmItemSupplierRate->load(['item:id,name,code', 'supplier:id,name']));
    }

    public function destroy(RMItemSupplierRate $rmItemSupplierRate)
    {
        $rmItemSupplierRate->delete();
        return response()->json(['message' => 'Supplier rate deleted successfully.']);
    }

    public function resolve(Request $request)
    {
        $request->validate([
            'rm_item_id' => 'required|exists:rm_items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'nullable|date',
        ]);

        $forDate = $request->date ?: now()->toDateString();

        $mapped = RMItemSupplierRate::where('rm_item_id', $request->rm_item_id)
            ->where('supplier_id', $request->supplier_id)
            ->where('is_active', true)
            ->where(function ($q) use ($forDate) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $forDate);
            })
            ->orderByRaw('effective_from IS NULL ASC')
            ->orderByDesc('effective_from')
            ->first();

        if ($mapped) {
            return response()->json([
                'rate' => (float) $mapped->rate,
                'source' => 'supplier_mapping',
                'effective_from' => optional($mapped->effective_from)->toDateString(),
            ]);
        }

        $item = RMItem::findOrFail($request->rm_item_id);
        return response()->json([
            'rate' => (float) $item->cost_price,
            'source' => 'item_default',
            'effective_from' => null,
        ]);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'rm_item_id' => 'required|exists:rm_items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'rate' => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:255',
        ]);

        $validator->after(function ($validator) use ($request, $ignoreId) {
            $existsRule = RMItemSupplierRate::where('rm_item_id', $request->rm_item_id)
                ->where('supplier_id', $request->supplier_id);

            if ($request->filled('effective_from')) {
                $existsRule->whereDate('effective_from', $request->effective_from);
            } else {
                $existsRule->whereNull('effective_from');
            }

            if ($ignoreId) {
                $existsRule->where('id', '!=', $ignoreId);
            }

            if ($existsRule->exists()) {
                $validator->errors()->add('effective_from', 'A supplier rate already exists for this item, supplier, and effective date.');
            }
        });

        return $validator->validate();
    }
}
