<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartageRate;
use App\Models\VehicleType;
use App\Models\CartageIncrementLog;
use App\Models\CartageIncrementDetail;
use Illuminate\Support\Facades\DB;

class CartageRateController extends Controller
{
    public function index()
    {
        return response()->json(CartageRate::with(['shippingAddress.customer'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'vehicle_type' => 'required|string',
            'rate' => 'required|numeric',
        ]);

        $rate = CartageRate::updateOrCreate(
            ['shipping_address_id' => $request->shipping_address_id, 'vehicle_type' => $request->vehicle_type],
            ['rate' => $request->rate]
        );

        return response()->json($rate->load(['shippingAddress.customer']), 201);
    }

    public function getRate(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'vehicle_type' => 'required|string',
        ]);

        $rate = CartageRate::where('shipping_address_id', $request->shipping_address_id)
            ->where('vehicle_type', $request->vehicle_type)
            ->first();

        return response()->json($rate);
    }

    public function destroy($id)
    {
        CartageRate::destroy($id);
        return response()->json(['message' => 'Rate deleted']);
    }

    public function getClassifications()
    {
        return response()->json(VehicleType::pluck('name')->toArray());
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|string',
            'effective_date' => 'required|date',
            'increment_type' => 'nullable|string',
            'increment_value' => 'nullable|numeric',
            'rates' => 'required|array',
            'rates.*.shipping_address_id' => 'required|exists:shipping_addresses,id',
            'rates.*.old_rate' => 'required|numeric',
            'rates.*.new_rate' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            $log = CartageIncrementLog::create([
                'vehicle_type' => $request->vehicle_type,
                'effective_date' => $request->effective_date,
                'increment_type' => $request->increment_type,
                'increment_value' => $request->increment_value,
            ]);

            foreach ($request->rates as $rateData) {
                CartageRate::updateOrCreate(
                    [
                        'shipping_address_id' => $rateData['shipping_address_id'],
                        'vehicle_type' => $request->vehicle_type
                    ],
                    ['rate' => $rateData['new_rate']]
                );

                CartageIncrementDetail::create([
                    'cartage_increment_log_id' => $log->id,
                    'shipping_address_id' => $rateData['shipping_address_id'],
                    'old_rate' => $rateData['old_rate'],
                    'new_rate' => $rateData['new_rate'],
                    'amount_increase' => $rateData['new_rate'] - $rateData['old_rate'],
                ]);
            }
        });

        return response()->json(['message' => 'Rates updated and history recorded successfully']);
    }

    public function getIncrementHistory()
    {
        return response()->json(CartageIncrementLog::with(['details.shippingAddress.customer'])->orderBy('created_at', 'desc')->get());
    }

    public function destroyIncrementHistory($id)
    {
        CartageIncrementLog::destroy($id);
        return response()->json(['message' => 'History record deleted successfully']);
    }
}
