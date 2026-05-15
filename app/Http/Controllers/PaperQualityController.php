<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperQuality;

class PaperQualityController extends Controller
{
    public function index()
    {
        return response()->json(PaperQuality::with('paperColor')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'quality' => 'required|string',
            'gsm_range' => 'required|string',
            'min_gsm' => 'nullable|numeric|min:0',
            'max_gsm' => 'nullable|numeric|min:0',
            'min_bursting' => 'nullable|numeric|min:0',
            'max_bursting' => 'nullable|numeric|min:0',
            'min_moisture' => 'nullable|numeric|min:0',
            'max_moisture' => 'nullable|numeric|min:0',
            'min_cobb' => 'nullable|numeric|min:0',
            'max_cobb' => 'nullable|numeric|min:0',
            'paper_color' => 'nullable|string',
            'paper_color_id' => 'nullable|exists:paper_colors,id',
        ]);

        // Generate item_code
        $words = explode(' ', $request->quality);
        $prefix = '';
        for ($i = 0; $i < min(3, count($words)); $i++) {
            $prefix .= strtoupper(substr($words[$i], 0, 1));
        }

        $lastCode = PaperQuality::where('item_code', 'like', $prefix . '%')->orderBy('item_code', 'desc')->first();
        $nextNum = $lastCode ? intval(substr($lastCode->item_code, strlen($prefix))) + 1 : 1;
        $itemCode = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $quality = PaperQuality::create(array_merge($request->all(), ['item_code' => $itemCode]));
        return response()->json($quality, 201);
    }

    public function show($id)
    {
        $quality = PaperQuality::findOrFail($id);
        return response()->json($quality);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quality' => 'sometimes|string',
            'gsm_range' => 'sometimes|string',
            'min_gsm' => 'nullable|numeric|min:0',
            'max_gsm' => 'nullable|numeric|min:0',
            'min_bursting' => 'nullable|numeric|min:0',
            'max_bursting' => 'nullable|numeric|min:0',
            'min_moisture' => 'nullable|numeric|min:0',
            'max_moisture' => 'nullable|numeric|min:0',
            'min_cobb' => 'nullable|numeric|min:0',
            'max_cobb' => 'nullable|numeric|min:0',
            'paper_color' => 'nullable|string',
            'paper_color_id' => 'nullable|exists:paper_colors,id',
        ]);

        $quality = PaperQuality::findOrFail($id);
        $quality->update($request->all());
        return response()->json($quality);
    }

    public function destroy($id)
    {
        $quality = PaperQuality::findOrFail($id);
        $quality->delete();
        return response()->json(['message' => 'Paper quality deleted']);
    }
}
