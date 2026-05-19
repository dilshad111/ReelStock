<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Domains\RawMaterial\Actions\CreateRMItemAction;
use Illuminate\Http\Request;

class RMItemController extends Controller
{
    public function index(Request $request)
    {
        $query = RMItem::with('paperQuality');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request, CreateRMItemAction $action)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'paper_quality_id' => 'nullable|exists:paper_qualities,id',
            'unit_type' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'opening_stock' => 'required|numeric|min:0',
            'min_stock_alert' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'remarks' => 'nullable|string',
        ]);

        $item = $action->execute($data);

        return response()->json($item->load('paperQuality'), 201);
    }

    public function show(RMItem $rmItem)
    {
        return response()->json($rmItem->load('paperQuality', 'latestLedger'));
    }

    public function update(Request $request, RMItem $rmItem)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'paper_quality_id' => 'nullable|exists:paper_qualities,id',
            'unit_type' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'remarks' => 'nullable|string',
        ]);

        $rmItem->update($data);

        return response()->json($rmItem->load('paperQuality'));
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
}
