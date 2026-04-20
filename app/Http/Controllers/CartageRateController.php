<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartageRate;

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
}
