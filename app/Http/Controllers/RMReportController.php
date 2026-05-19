<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Models\RMStockLedger;
use App\Models\RMReceiptItem;
use App\Models\RMConsumptionItem;
use Illuminate\Http\Request;

class RMReportController extends Controller
{
    public function currentInventory(Request $request)
    {
        $items = RMItem::with('paperQuality')->get();
        $report = [];

        foreach ($items as $item) {
            $lastLedger = RMStockLedger::where('rm_item_id', $item->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : 0;

            $report[] = [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'unit' => $item->unit_type,
                'cost_price' => $item->cost_price,
                'balance' => $balance,
                'valuation' => $balance * (float)$item->cost_price,
                'min_stock' => $item->min_stock_alert,
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
            'item' => RMItem::find($request->rm_item_id),
            'opening_balance' => $openingBalance,
            'ledger' => $ledger
        ]);
    }

    public function receivingReport(Request $request)
    {
        $query = RMReceiptItem::with(['receipt.supplier', 'item']);

        if ($request->filled('supplier_id')) {
            $query->whereHas('receipt', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('receipt', function($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        return response()->json($query->get());
    }

    public function consumptionReport(Request $request)
    {
        $query = RMConsumptionItem::with(['consumption', 'item']);

        if ($request->filled('department')) {
            $query->whereHas('consumption', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('consumption', function($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        return response()->json($query->get());
    }
}
