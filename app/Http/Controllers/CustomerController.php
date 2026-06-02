<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(Customer::with('shippingAddresses')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'shipping_addresses' => 'nullable|array',
            'shipping_addresses.*.address_name' => 'required|string',
            'shipping_addresses.*.full_address' => 'required|string',
            'shipping_addresses.*.round_trip_distance_km' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $customer = Customer::create($request->only(['name', 'email', 'phone', 'address']));

            if ($request->has('shipping_addresses')) {
                foreach ($request->shipping_addresses as $addr) {
                    $customer->shippingAddresses()->create($addr);
                }
            }

            return response()->json($customer->load('shippingAddresses'), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string',
            'shipping_addresses' => 'nullable|array',
            'shipping_addresses.*.address_name' => 'required|string',
            'shipping_addresses.*.full_address' => 'required|string',
            'shipping_addresses.*.round_trip_distance_km' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $customer) {
            $customer->update($request->only(['name', 'email', 'phone', 'address']));

            if ($request->has('shipping_addresses')) {
                // Simplest way: delete and recreate OR sync. 
                // For a more robust app, sync is better.
                $customer->shippingAddresses()->delete();
                foreach ($request->shipping_addresses as $addr) {
                    $customer->shippingAddresses()->create($addr);
                }
            }

            return response()->json($customer->load('shippingAddresses'));
        });
    }

    public function destroy($id)
    {
        Customer::destroy($id);
        return response()->json(['message' => 'Customer deleted']);
    }
}
