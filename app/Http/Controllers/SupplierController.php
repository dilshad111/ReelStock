<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(Supplier::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'contact_person' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate supplier_id
        $lastSupplier = Supplier::orderBy('id', 'desc')->first();
        $nextId = $lastSupplier ? intval(substr($lastSupplier->supplier_id, 3)) + 1 : 1;
        $supplierId = 'SUP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $supplier = Supplier::create(array_merge($request->all(), ['supplier_id' => $supplierId]));
        return response()->json($supplier, 201);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());
        return response()->json($supplier);
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted']);
    }
}
