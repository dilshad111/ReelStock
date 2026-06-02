<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function index()
    {
        return response()->json(Vehicle::with('transporter')->orderBy('vehicle_number')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_number' => 'required|string|unique:vehicles',
            'vehicle_type' => 'required|string',
            'mileage' => 'nullable|numeric|min:0.01',
            'transporter_id' => 'required|exists:transporters,id',
        ]);

        $vehicle = Vehicle::create($request->all());
        return response()->json($vehicle->load('transporter'), 201);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $request->validate([
            'vehicle_number' => 'required|string|unique:vehicles,vehicle_number,' . $vehicle->id,
            'vehicle_type' => 'required|string',
            'mileage' => 'nullable|numeric|min:0.01',
            'transporter_id' => 'required|exists:transporters,id',
        ]);

        $vehicle->update($request->all());
        return response()->json($vehicle->load('transporter'));
    }

    public function destroy($id)
    {
        Vehicle::destroy($id);
        return response()->json(['message' => 'Vehicle deleted']);
    }
}
