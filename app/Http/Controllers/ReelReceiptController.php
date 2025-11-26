<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\Reel;
use App\Models\ReelReceipt;

class ReelReceiptController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ReelReceipt::query();

            if ($request->has('reel_no')) {
                $query->whereHas('reel', function ($q) use ($request) {
                    $q->where('reel_no', 'like', '%' . $request->reel_no . '%');
                });
            }

            if ($request->has('supplier')) {
                $query->whereHas('reel.supplier', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->supplier . '%');
                });
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('receiving_date', [$request->date_from, $request->date_to]);
            }

            if ($request->has('orderBy') && $request->orderBy === 'id' && $request->has('sort') && $request->sort === 'desc') {
                $query->orderBy('id', 'desc');
            } else {
                $query->orderBy('id', 'desc');
            }

            if ($request->has('limit')) {
                $query->limit($request->limit);
            }

            $prefix = Setting::where('key', 'reel_no_prefix')->value('value') ?? 'RL2026';

            $receipts = $query->paginate(50);

            // Manually include reel data
            $receipts->getCollection()->transform(function ($receipt) {
                $reel = Reel::with('paperQuality', 'supplier')->find($receipt->reel_id);
                $receiptArray = $receipt->toArray();
                if ($reel) {
                    $reelArray = $reel->toArray();
                    // Concatenate quality and gsm_range
                    if ($reel->paperQuality) {
                        $quality = $reel->paperQuality->quality ?? $reel->paperQuality->item_code ?? '';
                        $gsm = $reel->paperQuality->gsm_range ? ' ' . $reel->paperQuality->gsm_range : '';
                        $reelArray['paper_quality_display'] = $quality . $gsm;
                    } else {
                        $reelArray['paper_quality_display'] = 'N/A';
                    }
                    $receiptArray['reel'] = $reelArray;
                } else {
                    $receiptArray['reel'] = null;
                }
                return $receiptArray;
            });

            $response = $receipts->toArray();
            $response['reel_prefix'] = $prefix;

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'paper_quality_id' => 'required|exists:paper_qualities,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'reel_size' => 'required|numeric|min:0',
                'reel_weight' => 'required|numeric|min:0',
                'receiving_date' => 'required|date',
                'received_by' => 'nullable|string',
                'gsm' => 'nullable|numeric|min:0',
                'bursting_strength' => 'nullable|numeric|min:0',
                'rate_per_kg' => 'nullable|numeric|min:0',
                'qc_status' => 'required|in:approved,rejected,on_hold',
                'remarks' => 'nullable|string',
            ]);

            // Fetch reel settings
            $prefix = Setting::where('key', 'reel_no_prefix')->value('value') ?? 'RL111';
            $padding = (int) Setting::where('key', 'reel_padding')->value('value') ?: 3;
            $currentNextNumber = (int) Setting::where('key', 'reel_next_number')->value('value') ?: 1;

            $nextNumber = $currentNextNumber;
            $reelNo = $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);

            $reel = Reel::create([
                'reel_no' => $reelNo,
                'paper_quality_id' => $request->paper_quality_id,
                'supplier_id' => $request->supplier_id,
                'reel_size' => $request->reel_size,
                'original_weight' => $request->reel_weight,
                'balance_weight' => $request->reel_weight,
                'status' => $request->qc_status === 'approved' ? 'in_stock' : 'in_stock', // adjust status based on qc
            ]);

            $receipt = ReelReceipt::create([
                'reel_id' => $reel->id,
                'receiving_date' => $request->receiving_date,
                'received_by' => $request->received_by,
                'gsm' => $request->gsm,
                'bursting_strength' => $request->bursting_strength,
                'rate_per_kg' => $request->rate_per_kg,
                'qc_status' => $request->qc_status,
                'remarks' => $request->remarks,
            ]);

            Setting::updateOrCreate(
                ['key' => 'reel_next_number'],
                ['value' => (string) ($nextNumber + 1)]
            );

            return response()->json($receipt->load('reel.paperQuality', 'reel.supplier'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'common' => 'required|array',
            'common.paper_quality_id' => 'required|exists:paper_qualities,id',
            'common.supplier_id' => 'required|exists:suppliers,id',
            'common.receiving_date' => 'required|date',
            'common.received_by' => 'nullable|string',
            'common.gsm' => 'nullable|numeric|min:0',
            'common.bursting_strength' => 'nullable|numeric|min:0',
            'common.rate_per_kg' => 'nullable|numeric|min:0',
            'common.qc_status' => 'required|in:approved,rejected,on_hold',
            'common.remarks' => 'nullable|string',
            'reels' => 'required|array|min:1',
            'reels.*.reel_size' => 'required|numeric|min:0',
            'reels.*.reel_weight' => 'required|numeric|min:0',
        ]);

        $receipts = [];
        $prefix = Setting::where('key', 'reel_no_prefix')->value('value') ?? 'RL111';
        $padding = (int) Setting::where('key', 'reel_padding')->value('value') ?: 3;
        $currentNextNumber = (int) Setting::where('key', 'reel_next_number')->value('value') ?: 1;
        $nextId = $currentNextNumber;

        foreach ($request->reels as $reelData) {
            $reelNo = $prefix . str_pad($nextId++, $padding, '0', STR_PAD_LEFT);

            $reel = Reel::create([
                'reel_no' => $reelNo,
                'paper_quality_id' => $request->common['paper_quality_id'],
                'supplier_id' => $request->common['supplier_id'],
                'reel_size' => $reelData['reel_size'],
                'original_weight' => $reelData['reel_weight'],
                'balance_weight' => $reelData['reel_weight'],
                'status' => $request->common['qc_status'] === 'approved' ? 'in_stock' : 'in_stock',
            ]);

            $receipt = ReelReceipt::create([
                'reel_id' => $reel->id,
                'receiving_date' => $request->common['receiving_date'],
                'received_by' => $request->common['received_by'],
                'gsm' => $request->common['gsm'],
                'bursting_strength' => $request->common['bursting_strength'],
                'rate_per_kg' => $request->common['rate_per_kg'],
                'qc_status' => $request->common['qc_status'],
                'remarks' => $request->common['remarks'],
            ]);

            $receipts[] = $receipt;
        }

        Setting::updateOrCreate(
            ['key' => 'reel_next_number'],
            ['value' => (string) $nextId]
        );

        return response()->json(ReelReceipt::with('reel.paperQuality', 'reel.supplier')->whereIn('id', array_column($receipts, 'id'))->get(), 201);
    }

    public function show($id)
    {
        $receipt = ReelReceipt::with('reel.paperQuality', 'reel.supplier')->findOrFail($id);
        return response()->json($receipt);
    }

    public function update(Request $request, $id)
    {
        $receipt = ReelReceipt::findOrFail($id);
        
        // Update receipt fields
        $receiptFields = ['receiving_date', 'received_by', 'gsm', 'bursting_strength', 'rate_per_kg', 'qc_status', 'remarks'];
        $receiptData = $request->only($receiptFields);
        $receipt->update($receiptData);

        // Update reel fields if provided
        $reelFields = ['paper_quality_id', 'supplier_id', 'reel_size'];
        $reelData = $request->only($reelFields);
        if (!empty($reelData)) {
            $receipt->reel->update($reelData);
            
            // Update reel weight if provided
            if ($request->has('reel_weight')) {
                $receipt->reel->update([
                    'original_weight' => $request->reel_weight,
                    'balance_weight' => $request->reel_weight
                ]);
            }
        }

        // Update reel status if qc approved
        if ($request->qc_status === 'approved' && $receipt->reel->status !== 'in_stock') {
            $receipt->reel->update(['status' => 'in_stock']);
        }

        return response()->json($receipt->load('reel.paperQuality', 'reel.supplier'));
    }
}
