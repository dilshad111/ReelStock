<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartageEntry;
use App\Models\CartageBill;
use App\Models\Customer;
use App\Models\Transporter;
use App\Models\Vehicle;

class CartageReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CartageEntry::with(['cartageBill.transporter', 'customer', 'shippingAddress']);

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
        ]);
    }
}
