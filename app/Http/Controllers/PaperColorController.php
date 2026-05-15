<?php

namespace App\Http\Controllers;



use App\Models\PaperColor;
use Illuminate\Http\Request;

class PaperColorController extends Controller
{
    public function index()
    {
        return response()->json(PaperColor::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:paper_colors,name']);
        return PaperColor::create($request->all());
    }

    public function show($id)
    {
        return PaperColor::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $color = PaperColor::findOrFail($id);
        $request->validate(['name' => 'required|unique:paper_colors,name,' . $id]);
        $color->update($request->all());
        return $color;
    }

    public function destroy($id)
    {
        $color = PaperColor::findOrFail($id);
        $color->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
