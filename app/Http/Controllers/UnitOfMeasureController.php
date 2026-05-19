<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class UnitOfMeasureController extends Controller
{
    public function index()
    {
        return response()->json(UnitOfMeasure::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:unit_of_measures',
            'status' => 'required|in:active,inactive'
        ]);

        $uom = UnitOfMeasure::create($request->all());

        return response()->json([
            'message' => 'Unit of measure added successfully',
            'data' => $uom
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:unit_of_measures,name,' . $id,
            'status' => 'required|in:active,inactive'
        ]);

        $uom = UnitOfMeasure::findOrFail($id);
        $uom->update($request->all());

        return response()->json([
            'message' => 'Unit of measure updated successfully',
            'data' => $uom
        ]);
    }

    public function destroy($id)
    {
        $uom = UnitOfMeasure::findOrFail($id);
        $uom->delete();

        return response()->json([
            'message' => 'Unit of measure deleted successfully'
        ]);
    }
}
