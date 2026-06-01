<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Models\RMStockLedger;
use App\Models\RMReceiptItem;
use App\Models\RMConsumptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RMReportController extends Controller
{
    public function currentInventory(Request $request)
    {
        $items = RMItem::with(['paperQuality', 'category', 'subcategory', 'preferredSupplier'])
            ->when($request->filled('rm_category_id'), fn ($q) => $q->where('rm_category_id', $request->rm_category_id))
            ->when($request->filled('rm_subcategory_id'), fn ($q) => $q->where('rm_subcategory_id', $request->rm_subcategory_id))
            ->when($request->filled('preferred_supplier_id'), fn ($q) => $q->where('preferred_supplier_id', $request->preferred_supplier_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->get();

        $report = [];

        foreach ($items as $item) {
            $lastLedger = RMStockLedger::where('rm_item_id', $item->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $stockStatus = $this->stockStatus($item, $balance);

            if ($request->filled('stock_status') && $stockStatus !== $request->stock_status) {
                continue;
            }

            $report[] = [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'category' => $item->category?->name,
                'subcategory' => $item->subcategory?->name,
                'preferred_supplier' => $item->preferredSupplier?->name,
                'material_type' => $item->material_type,
                'unit' => $item->unit_type,
                'cost_price' => $item->cost_price,
                'balance' => $balance,
                'valuation' => $balance * (float)$item->cost_price,
                'reorder_level' => $item->reorder_level,
                'min_stock' => $item->minimum_stock ?: $item->min_stock_alert,
                'max_stock' => $item->maximum_stock,
                'stock_status' => $stockStatus,
                'status' => $item->status,
            ];
        }

        return response()->json($report);
    }

    public function stockLedger(Request $request)
    {
        $request->validate([
            'rm_item_id' => 'required|exists:rm_items,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $ledger = RMStockLedger::where('rm_item_id', $request->rm_item_id)
            ->whereBetween('transaction_date', [$request->date_from, $request->date_to])
            ->orderBy('id', 'asc')
            ->get();

        // Get opening balance before date_from
        $openingLedger = RMStockLedger::where('rm_item_id', $request->rm_item_id)
            ->where('transaction_date', '<', $request->date_from)
            ->latest('id')
            ->first();

        $openingBalance = $openingLedger ? (float)$openingLedger->balance_after : 0;

        return response()->json([
            'item' => RMItem::with(['category', 'subcategory', 'preferredSupplier'])->find($request->rm_item_id),
            'opening_balance' => $openingBalance,
            'ledger' => $ledger
        ]);
    }

    public function receivingReport(Request $request)
    {
        $query = RMReceiptItem::with(['receipt.supplier', 'item.category', 'item.subcategory', 'item.preferredSupplier']);

        if ($request->filled('supplier_id')) {
            $query->whereHas('receipt', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        $this->applyItemFilters($query, $request);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('receipt', function($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        return response()->json($query->get());
    }

    public function consumptionReport(Request $request)
    {
        $query = RMConsumptionItem::with(['consumption', 'item.category', 'item.subcategory', 'item.preferredSupplier']);

        if ($request->filled('department')) {
            $query->whereHas('consumption', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        $this->applyItemFilters($query, $request);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('consumption', function($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        return response()->json($query->get());
    }

    public function reorderRequirement(Request $request)
    {
        $request->merge(['stock_status' => 'reorder_required']);

        return $this->currentInventory($request);
    }

    public function materialCostByCategory(Request $request)
    {
        $inventory = collect($this->currentInventory($request)->getData(true));

        return response()->json(
            $inventory
                ->groupBy(fn ($item) => $item['category'] ?: 'Uncategorised')
                ->map(function ($items, $category) {
                    return [
                        'category' => $category,
                        'items_count' => $items->count(),
                        'stock_value' => round($items->sum('valuation'), 2),
                        'stock_qty' => round($items->sum('balance'), 2),
                    ];
                })
                ->values()
        );
    }

    public function consumptionAnalysis(Request $request)
    {
        $query = RMConsumptionItem::query()
            ->join('rm_consumptions', 'rm_consumption_items.rm_consumption_id', '=', 'rm_consumptions.id')
            ->join('rm_items', 'rm_consumption_items.rm_item_id', '=', 'rm_items.id')
            ->leftJoin('rm_categories', 'rm_items.rm_category_id', '=', 'rm_categories.id')
            ->leftJoin('rm_subcategories', 'rm_items.rm_subcategory_id', '=', 'rm_subcategories.id')
            ->select([
                DB::raw("COALESCE(rm_categories.name, 'Uncategorised') as category"),
                DB::raw("COALESCE(rm_subcategories.name, '-') as subcategory"),
                DB::raw('SUM(rm_consumption_items.quantity) as consumed_qty'),
                DB::raw('SUM(rm_consumption_items.quantity * rm_items.cost_price) as consumed_value'),
            ])
            ->groupBy('category', 'subcategory')
            ->orderBy('category')
            ->orderBy('subcategory');

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('rm_consumptions.date', [$request->date_from, $request->date_to]);
        }

        if ($request->filled('rm_category_id')) {
            $query->where('rm_items.rm_category_id', $request->rm_category_id);
        }

        if ($request->filled('rm_subcategory_id')) {
            $query->where('rm_items.rm_subcategory_id', $request->rm_subcategory_id);
        }

        if ($request->filled('preferred_supplier_id')) {
            $query->where('rm_items.preferred_supplier_id', $request->preferred_supplier_id);
        }

        return response()->json($query->get());
    }

    private function applyItemFilters($query, Request $request): void
    {
        if ($request->filled('rm_category_id')) {
            $query->whereHas('item', fn ($q) => $q->where('rm_category_id', $request->rm_category_id));
        }

        if ($request->filled('rm_subcategory_id')) {
            $query->whereHas('item', fn ($q) => $q->where('rm_subcategory_id', $request->rm_subcategory_id));
        }

        if ($request->filled('preferred_supplier_id')) {
            $query->whereHas('item', fn ($q) => $q->where('preferred_supplier_id', $request->preferred_supplier_id));
        }
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
}
