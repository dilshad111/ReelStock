<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index()
    {
        return response()->json(VehicleType::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:vehicle_types,name',
        ]);

        $vehicleType = VehicleType::create($request->all());
        return response()->json($vehicleType, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:vehicle_types,name,' . $id,
        ]);

        $vehicleType = VehicleType::findOrFail($id);
        $vehicleType->update($request->all());
        return response()->json($vehicleType);
    }

    public function destroy($id)
    {
        VehicleType::destroy($id);
        return response()->json(['message' => 'Vehicle type deleted']);
    }
}
