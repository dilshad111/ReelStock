<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartageEntry;
use App\Models\Customer;
use App\Models\Transporter;
use App\Models\Vehicle;
use App\Models\ShippingAddress;

class CartageReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CartageEntry::with(['cartageBill.transporter', 'customer', 'shippingAddress', 'vehicle']);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('entry_date', [$request->start_date, $request->end_date]);
        }
        
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->transporter_id) {
            $query->whereHas('cartageBill', function($q) use ($request) {
                $q->where('transporter_id', $request->transporter_id);
            });
        }
        
        if ($request->vehicle_number) {
            $query->where('vehicle_number', $request->vehicle_number);
        }

        return response()->json($query->orderBy('entry_date', 'desc')->get());
    }

    public function getFilters()
    {
        return response()->json([
            'customers' => Customer::orderBy('name')->get(),
            'transporters' => Transporter::orderBy('name')->get(),
            'vehicles' => Vehicle::orderBy('vehicle_number')->get(),
            'locations' => ShippingAddress::with('customer')->orderBy('address_name')->get(),
        ]);
    }

    public function fuelCost(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'vehicle_number' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
            'fuel_rate' => 'required|numeric|min:0.01',
        ]);

        $fuelRate = (float) $validated['fuel_rate'];

        $query = CartageEntry::with(['cartageBill.transporter', 'customer', 'shippingAddress', 'vehicle'])
            ->whereBetween('entry_date', [$validated['start_date'], $validated['end_date']]);

        if (!empty($validated['vehicle_number'])) {
            $query->where('vehicle_number', $validated['vehicle_number']);
        }

        if (!empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (!empty($validated['shipping_address_id'])) {
            $query->where('shipping_address_id', $validated['shipping_address_id']);
        }

        $rows = $query->orderBy('entry_date', 'desc')
            ->get()
            ->map(function (CartageEntry $entry) use ($fuelRate) {
                $distance = (float) ($entry->shippingAddress?->round_trip_distance_km ?? 0);
                $mileage = (float) ($entry->vehicle?->mileage ?? 0);
                $freight = (float) $entry->amount;
                $fuelConsumption = $mileage > 0 ? $distance / $mileage : 0;
                $fuelCost = $fuelConsumption * $fuelRate;

                return [
                    'id' => $entry->id,
                    'trip_date' => $entry->entry_date,
                    'vehicle_number' => $entry->vehicle_number,
                    'customer' => $entry->customer,
                    'shipping_address' => $entry->shippingAddress,
                    'transporter' => $entry->cartageBill?->transporter,
                    'round_trip_distance_km' => round($distance, 2),
                    'freight_amount' => round($freight, 2),
                    'vehicle_mileage' => round($mileage, 2),
                    'fuel_consumption_liters' => round($fuelConsumption, 2),
                    'fuel_cost_amount' => round($fuelCost, 2),
                    'profit' => round($freight - $fuelCost, 2),
                ];
            });

        return response()->json($rows);
    }
}
