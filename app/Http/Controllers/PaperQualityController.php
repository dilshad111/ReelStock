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
        if ($request->has('paper_color') && !is_string($request->paper_color)) {
            $paperColorData = $request->paper_color;
            if (is_array($paperColorData) && isset($paperColorData['name'])) {
                $request->merge(['paper_color' => $paperColorData['name']]);
            } else {
                $request->request->remove('paper_color');
            }
        }

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

        // Always generate item_code from quality name
        $itemCode = $this->generateItemCode($request->quality);

        $quality = PaperQuality::create(array_merge($request->all(), ['item_code' => $itemCode]));
        return response()->json($quality->load('paperColor'), 201);
    }

    public function show($id)
    {
        $quality = PaperQuality::findOrFail($id);
        return response()->json($quality);
    }

    public function update(Request $request, $id)
    {
        if ($request->has('paper_color') && !is_string($request->paper_color)) {
            $paperColorData = $request->paper_color;
            if (is_array($paperColorData) && isset($paperColorData['name'])) {
                $request->merge(['paper_color' => $paperColorData['name']]);
            } else {
                $request->request->remove('paper_color');
            }
        }

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
        $data = $request->all();

        // Always regenerate item_code when quality name changes
        if ($request->has('quality') && $request->quality !== $quality->quality) {
            $data['item_code'] = $this->generateItemCode($request->quality, $quality->id);
        }

        $quality->update($data);
        return response()->json($quality->load('paperColor'));
    }

    public function destroy($id)
    {
        $quality = PaperQuality::findOrFail($id);
        $quality->delete();
        return response()->json(['message' => 'Paper quality deleted']);
    }

    /**
     * Generate a unique item_code from the quality name.
     * Takes first letter of each word (up to 3 words) as prefix,
     * then appends a 3-digit sequence number.
     *
     * @param string $qualityName  The quality/paper name
     * @param int|null $excludeId  ID to exclude from duplicate check (for updates)
     * @return string
     */
    private function generateItemCode(string $qualityName, ?int $excludeId = null): string
    {
        $words = explode(' ', $qualityName);
        $prefix = '';
        $wordCount = 0;
        foreach ($words as $word) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $word);
            if ($clean !== '') {
                $prefix .= strtoupper(substr($clean, 0, 1));
                $wordCount++;
                if ($wordCount >= 3) break;
            }
        }

        $query = PaperQuality::where('item_code', 'like', $prefix . '%');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        $lastCode = $query->orderBy('item_code', 'desc')->first();

        $nextNum = $lastCode ? intval(substr($lastCode->item_code, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}

