<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartageBill;
use App\Models\CartageEntry;
use Illuminate\Support\Facades\DB;

class CartageBillController extends Controller
{
    public function index()
    {
        return response()->json(CartageBill::with(['transporter', 'approver', 'entries.customer', 'entries.shippingAddress', 'entries.subEntries'])->orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bill_date' => 'required|date',
            'bill_to' => 'required|string',
            'entries' => 'required|array|min:1',
            'entries.*.entry_date' => 'required|date',
            'entries.*.customer_id' => 'required|exists:customers,id',
            'entries.*.shipping_address_id' => 'required|exists:shipping_addresses,id',
            'entries.*.vehicle_number' => 'required|string',
            'entries.*.dc_number' => 'nullable|string',
            'entries.*.slip_no' => 'nullable|string',
            'entries.*.amount' => 'required|numeric',
            'entries.*.is_return' => 'boolean',
            'entries.*.is_second_location' => 'boolean',
            'entries.*.remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $totalAmount = collect($request->entries)->sum('amount');

            $bill = CartageBill::create([
                'transporter_id' => $request->transporter_id,
                'bill_to' => $request->bill_to,
                'bill_date' => $request->bill_date,
                'total_amount' => $totalAmount,
            ]);

            $entryMap = []; // To store created entries and map them by index

            foreach ($request->entries as $index => $entryData) {
                $parentIndex = $entryData['parent_index'] ?? null;
                $parentEntryId = null;

                if ($parentIndex !== null && isset($entryMap[$parentIndex])) {
                    $parentEntryId = $entryMap[$parentIndex]->id;
                }

                $data = collect($entryData)->except(['parent_index'])->toArray();
                $data['parent_entry_id'] = $parentEntryId;

                $entry = $bill->entries()->create($data);
                $entryMap[$index] = $entry;
            }

            return response()->json($bill->load(['transporter', 'entries.customer', 'entries.shippingAddress']), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $bill = CartageBill::findOrFail($id);

        $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bill_date' => 'required|date',
            'bill_to' => 'required|string',
            'entries' => 'required|array|min:1',
            'entries.*.entry_date' => 'required|date',
            'entries.*.customer_id' => 'required|exists:customers,id',
            'entries.*.shipping_address_id' => 'required|exists:shipping_addresses,id',
            'entries.*.vehicle_number' => 'required|string',
            'entries.*.dc_number' => 'nullable|string',
            'entries.*.amount' => 'required|numeric',
            'entries.*.is_return' => 'boolean',
            'entries.*.is_second_location' => 'boolean',
            'entries.*.remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $bill) {
            $totalAmount = collect($request->entries)->sum('amount');

            $bill->update([
                'transporter_id' => $request->transporter_id,
                'bill_to' => $request->bill_to,
                'bill_date' => $request->bill_date,
                'total_amount' => $totalAmount,
            ]);

            // Delete old entries and re-create (simplest way for nested structures in this context)
            $bill->entries()->delete();

            $entryMap = [];

            foreach ($request->entries as $index => $entryData) {
                $parentIndex = $entryData['parent_index'] ?? null;
                $parentEntryId = null;

                if ($parentIndex !== null && isset($entryMap[$parentIndex])) {
                    $parentEntryId = $entryMap[$parentIndex]->id;
                }

                $data = collect($entryData)->except(['parent_index', 'id'])->toArray();
                $data['parent_entry_id'] = $parentEntryId;

                $entry = $bill->entries()->create($data);
                $entryMap[$index] = $entry;
            }

            return response()->json($bill->load(['transporter', 'entries.customer', 'entries.shippingAddress']));
        });
    }

    public function show($id)
    {
        $bill = CartageBill::with(['transporter', 'entries.customer', 'entries.shippingAddress', 'entries.subEntries'])->findOrFail($id);
        return response()->json($bill);
    }

    public function destroy($id)
    {
        CartageBill::destroy($id);
        return response()->json(['message' => 'Bill deleted']);
    }

    public function getNextId()
    {
        $lastId = CartageBill::max('id') ?? 0;
        return response()->json(['next_id' => $lastId + 1]);
    }

    public function getPendingCount()
    {
        $count = CartageBill::where('status', 'Pending')->count();
        return response()->json(['count' => $count]);
    }

    public function approve(Request $request, $id)
    {
        $bill = CartageBill::findOrFail($id);
        
        $request->validate([
            'tax_type' => 'nullable|string',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'net_amount' => 'required|numeric',
        ]);

        $bill->update([
            'status' => 'Approved',
            'tax_type' => $request->tax_type,
            'tax_percentage' => $request->tax_percentage ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'net_amount' => $request->net_amount,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json($bill->load('approver'));
    }
}
