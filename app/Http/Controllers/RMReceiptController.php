<?php

namespace App\Http\Controllers;

use App\Models\RMReceipt;
use App\Domains\RawMaterial\Actions\RecordRMReceiptAction;
use App\Domains\RawMaterial\DTOs\RMReceiptDTO;
use Illuminate\Http\Request;

class RMReceiptController extends Controller
{
    public function nextGrnNo()
    {
        $lastReceipt = RMReceipt::where('grn_no', 'like', 'GRN-%')->orderBy('id', 'desc')->first();
        $nextNum = 1;
        if ($lastReceipt) {
            $parts = explode('-', $lastReceipt->grn_no);
            if (count($parts) > 1 && is_numeric($parts[1])) {
                $nextNum = intval($parts[1]) + 1;
            }
        }
        $nextGrn = 'GRN-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        return response()->json(['next_grn_no' => $nextGrn]);
    }

    public function index(Request $request)
    {
        $query = RMReceipt::with(['supplier', 'items.item']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        return response()->json($query->latest()->paginate($request->per_page ?? 15));
    }

    public function store(Request $request, RecordRMReceiptAction $action)
    {
        $request->validate([
            'grn_no' => 'required|string|unique:rm_receipts,grn_no',
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rm_item_id' => 'required|exists:rm_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
        ]);

        $dto = RMReceiptDTO::fromRequest($request);
        $receipt = $action->execute($dto);

        return response()->json($receipt, 201);
    }

    public function show(RMReceipt $rmReceipt)
    {
        return response()->json($rmReceipt->load(['supplier', 'items.item', 'creator']));
    }
}
