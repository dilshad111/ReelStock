<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Models\RMItemSupplierRate;
use App\Models\RMSubcategory;
use App\Domains\RawMaterial\Actions\CreateRMItemAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RMItemController extends Controller
{
    public function index(Request $request)
    {
        $query = RMItem::with(['paperQuality', 'category', 'subcategory', 'preferredSupplier', 'latestLedger', 'supplierRates.supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rm_category_id')) {
            $query->where('rm_category_id', $request->rm_category_id);
        }

        if ($request->filled('rm_subcategory_id')) {
            $query->where('rm_subcategory_id', $request->rm_subcategory_id);
        }

        if ($request->filled('preferred_supplier_id')) {
            $query->where('preferred_supplier_id', $request->preferred_supplier_id);
        }

        if ($request->filled('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $items = $query->orderBy('name')->get()->map(function ($item) {
            $balance = $item->latestLedger ? (float) $item->latestLedger->balance_after : 0;
            $item->current_balance = $balance;
            $item->stock_status = $this->stockStatus($item, $balance);
            return $item;
        });

        if ($request->filled('stock_status')) {
            $items = $items->where('stock_status', $request->stock_status)->values();
        }

        return response()->json($items->values());
    }

    public function store(Request $request, CreateRMItemAction $action)
    {
        $data = $this->validateItem($request, true);
        $data = $this->normalizeStockFields($data);

        $item = $action->execute($data);
        if ($request->has('supplier_rates')) {
            $this->syncSupplierRates($item, $request->input('supplier_rates', []));
        }

        return response()->json($item->fresh()->load(['paperQuality', 'category', 'subcategory', 'preferredSupplier', 'supplierRates.supplier']), 201);
    }

    public function show(RMItem $rmItem)
    {
        return response()->json($rmItem->load(['paperQuality', 'category', 'subcategory', 'preferredSupplier', 'latestLedger', 'supplierRates.supplier']));
    }

    public function update(Request $request, RMItem $rmItem)
    {
        $data = $this->validateItem($request, false);
        $data = $this->normalizeStockFields($data);

        $rmItem->update($data);
        if ($request->has('supplier_rates')) {
            $this->syncSupplierRates($rmItem, $request->input('supplier_rates', []));
        }

        return response()->json($rmItem->fresh()->load(['paperQuality', 'category', 'subcategory', 'preferredSupplier', 'supplierRates.supplier']));
    }

    public function destroy(RMItem $rmItem)
    {
        // Check if item has transactions
        if ($rmItem->ledgerEntries()->where('transaction_type', '!=', 'opening')->exists()) {
            return response()->json(['error' => 'Cannot delete item with transaction history.'], 422);
        }

        $rmItem->delete();
        return response()->json(['message' => 'Item deleted successfully.']);
    }

    private function validateItem(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'paper_quality_id' => 'nullable|exists:paper_qualities,id',
            'rm_category_id' => 'required|exists:rm_categories,id',
            'rm_subcategory_id' => 'nullable|exists:rm_subcategories,id',
            'unit_type' => 'required|string|max:50',
            'material_type' => 'required|in:Direct Material,Indirect Material,Consumable',
            'cost_price' => 'required|numeric|min:0',
            'opening_stock' => $isCreate ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'min_stock_alert' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'gst_tax_code' => 'nullable|string|max:50',
            'status' => 'required|in:Active,Inactive',
            'remarks' => 'nullable|string',
            'supplier_rates' => 'sometimes|array',
            'supplier_rates.*.supplier_id' => 'required_with:supplier_rates|exists:suppliers,id',
            'supplier_rates.*.rate' => 'required_with:supplier_rates|numeric|min:0',
            'supplier_rates.*.effective_from' => 'nullable|date',
            'supplier_rates.*.is_active' => 'nullable|boolean',
            'supplier_rates.*.remarks' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('rm_subcategory_id') && $request->filled('rm_category_id')) {
                $isValid = RMSubcategory::where('id', $request->rm_subcategory_id)
                    ->where('rm_category_id', $request->rm_category_id)
                    ->exists();

                if (! $isValid) {
                    $validator->errors()->add('rm_subcategory_id', 'The selected subcategory does not belong to the selected category.');
                }
            }

            if ($request->filled('maximum_stock') && $request->filled('minimum_stock') && (float) $request->maximum_stock > 0) {
                if ((float) $request->maximum_stock < (float) $request->minimum_stock) {
                    $validator->errors()->add('maximum_stock', 'Maximum stock must be greater than or equal to minimum stock.');
                }
            }
        });

        return $validator->validate();
    }

    private function normalizeStockFields(array $data): array
    {
        $data['minimum_stock'] = $data['minimum_stock'] ?? $data['min_stock_alert'] ?? 0;
        $data['min_stock_alert'] = $data['min_stock_alert'] ?? $data['minimum_stock'];
        $data['reorder_level'] = $data['reorder_level'] ?? $data['minimum_stock'];
        $data['maximum_stock'] = $data['maximum_stock'] ?? 0;

        return $data;
    }

    private function stockStatus(RMItem $item, float $balance): string
    {
        if ($balance <= 0) {
            return 'out_of_stock';
        }

        $reorderLevel = (float) ($item->reorder_level ?: $item->minimum_stock ?: $item->min_stock_alert);

        if ($reorderLevel > 0 && $balance <= $reorderLevel) {
            return 'reorder_required';
        }

        return 'in_stock';
    }

    private function syncSupplierRates(RMItem $item, array $rows): void
    {
        $item->supplierRates()->delete();

        foreach ($rows as $row) {
            if (empty($row['supplier_id'])) {
                continue;
            }

            RMItemSupplierRate::create([
                'rm_item_id' => $item->id,
                'supplier_id' => $row['supplier_id'],
                'rate' => $row['rate'] ?? 0,
                'effective_from' => $row['effective_from'] ?? null,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
                'remarks' => $row['remarks'] ?? null,
            ]);
        }
    }
}
