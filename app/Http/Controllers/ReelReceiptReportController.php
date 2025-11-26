<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReelReceipt;

class ReelReceiptReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ReelReceipt::with('reel.paperQuality', 'reel.supplier');

        if ($request->has('reel_no')) {
            $query->whereHas('reel', function ($q) use ($request) {
                $q->where('reel_no', 'like', '%' . $request->reel_no . '%');
            });
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('receiving_date', [$request->date_from, $request->date_to]);
        }

        if ($request->has('supplier_id') && $request->supplier_id !== '') {
            $query->whereHas('reel.supplier', function ($q) use ($request) {
                $q->where('id', $request->supplier_id);
            });
        }

        $data = $query->get()->map(function ($receipt) {
            $reel = $receipt->reel;
            $rate = $receipt->rate_per_kg ?? 0;
            $weight = $reel->original_weight ?? 0;
            return [
                'id' => $receipt->id,
                'receiving_date' => $receipt->receiving_date,
                'reel_no' => $reel->reel_no ?? null,
                'supplier_id' => $reel->supplier->id ?? null,
                'supplier' => $reel->supplier->name ?? null,
                'paper_quality' => $reel->paperQuality ? $reel->paperQuality->quality : null,
                'gsm_range' => $reel->paperQuality ? $reel->paperQuality->gsm_range : null,
                'reel_size' => $reel->reel_size,
                'weight' => $weight,
                'rate_per_kg' => $rate,
                'amount' => $weight * $rate,
                'qc_status' => $receipt->qc_status,
            ];
        });

        return response()->json($data);
    }
}
