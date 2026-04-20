<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transporter;

class TransporterController extends Controller
{
    public function index()
    {
        return response()->json(Transporter::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('transporters/logos', 'public');
            $data['logo'] = $path;
        }

        $transporter = Transporter::create($data);
        return response()->json($transporter, 201);
    }

    public function update(Request $request, $id)
    {
        $transporter = Transporter::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($transporter->logo) {
                \Storage::disk('public')->delete($transporter->logo);
            }
            $path = $request->file('logo')->store('transporters/logos', 'public');
            $data['logo'] = $path;
        }

        $transporter->update($data);
        return response()->json($transporter);
    }

    public function destroy($id)
    {
        Transporter::destroy($id);
        return response()->json(['message' => 'Transporter deleted']);
    }
}
