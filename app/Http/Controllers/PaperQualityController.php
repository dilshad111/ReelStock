<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperQuality;
use App\Models\RMCategory;
use App\Models\RMItem;
use App\Models\RMStockLedger;
use Illuminate\Support\Facades\DB;

class PaperQualityController extends Controller
{
    /**
     * Auto-calculate standards from min/max values.
     * Example: min 100, max 115 => standard 108
     */
    private function applyStandardValues(array $data): array
    {
        $pairs = [
            ['min' => 'min_gsm', 'max' => 'max_gsm', 'standard' => 'standard_gsm'],
            ['min' => 'min_bursting', 'max' => 'max_bursting', 'standard' => 'standard_bursting'],
            ['min' => 'min_moisture', 'max' => 'max_moisture', 'standard' => 'standard_moisture'],
            ['min' => 'min_cobb', 'max' => 'max_cobb', 'standard' => 'standard_cobb'],
        ];

        foreach ($pairs as $pair) {
            $minKey = $pair['min'];
            $maxKey = $pair['max'];
            $standardKey = $pair['standard'];

            if (
                array_key_exists($minKey, $data) &&
                array_key_exists($maxKey, $data) &&
                $data[$minKey] !== null &&
                $data[$minKey] !== '' &&
                $data[$maxKey] !== null &&
                $data[$maxKey] !== ''
            ) {
                $min = (float) $data[$minKey];
                $max = (float) $data[$maxKey];
                $data[$standardKey] = round(($min + $max) / 2, 0);
            }
        }

        return $data;
    }

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

        $validated = $request->validate([
            'quality' => 'required|string',
            'gsm_range' => 'required|string',
            'min_gsm' => 'nullable|numeric|min:0',
            'standard_gsm' => 'nullable|numeric|min:0',
            'max_gsm' => 'nullable|numeric|min:0',
            'min_bursting' => 'nullable|numeric|min:0',
            'standard_bursting' => 'nullable|numeric|min:0',
            'max_bursting' => 'nullable|numeric|min:0',
            'min_moisture' => 'nullable|numeric|min:0',
            'standard_moisture' => 'nullable|numeric|min:0',
            'max_moisture' => 'nullable|numeric|min:0',
            'min_cobb' => 'nullable|numeric|min:0',
            'standard_cobb' => 'nullable|numeric|min:0',
            'max_cobb' => 'nullable|numeric|min:0',
            'paper_color' => 'nullable|string',
            'paper_color_id' => 'nullable|exists:paper_colors,id',
        ]);

        // Always generate item_code from quality name
        $itemCode = $this->generateItemCode($request->quality);

        $payload = $this->applyStandardValues($validated);
        $quality = PaperQuality::create(array_merge($payload, ['item_code' => $itemCode]));
        $this->syncPaperQualityRawMaterial($quality);

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

        $validated = $request->validate([
            'quality' => 'sometimes|string',
            'gsm_range' => 'sometimes|string',
            'min_gsm' => 'nullable|numeric|min:0',
            'standard_gsm' => 'nullable|numeric|min:0',
            'max_gsm' => 'nullable|numeric|min:0',
            'min_bursting' => 'nullable|numeric|min:0',
            'standard_bursting' => 'nullable|numeric|min:0',
            'max_bursting' => 'nullable|numeric|min:0',
            'min_moisture' => 'nullable|numeric|min:0',
            'standard_moisture' => 'nullable|numeric|min:0',
            'max_moisture' => 'nullable|numeric|min:0',
            'min_cobb' => 'nullable|numeric|min:0',
            'standard_cobb' => 'nullable|numeric|min:0',
            'max_cobb' => 'nullable|numeric|min:0',
            'paper_color' => 'nullable|string',
            'paper_color_id' => 'nullable|exists:paper_colors,id',
        ]);

        $quality = PaperQuality::findOrFail($id);
        $data = $this->applyStandardValues($validated);

        // Always regenerate item_code when quality name changes
        if ($request->has('quality') && $request->quality !== $quality->quality) {
            $data['item_code'] = $this->generateItemCode($request->quality, $quality->id);
        }

        $quality->update($data);
        $this->syncPaperQualityRawMaterial($quality->fresh());

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

    private function syncPaperQualityRawMaterial(PaperQuality $quality): void
    {
        $paperBoardCategory = RMCategory::where('name', 'Paper & Board')->first();

        if (! $paperBoardCategory) {
            return;
        }

        DB::transaction(function () use ($quality, $paperBoardCategory) {
            $item = RMItem::firstOrNew(['paper_quality_id' => $quality->id]);
            $isNew = ! $item->exists;

            if ($isNew) {
                $item->code = $this->nextRawMaterialCode();
                $item->opening_stock = 0;
                $item->min_stock_alert = 0;
                $item->reorder_level = 0;
                $item->minimum_stock = 0;
                $item->maximum_stock = 0;
                $item->status = 'Active';
            }

            $item->fill([
                'name' => trim($quality->quality . ' ' . $quality->gsm_range),
                'rm_category_id' => $paperBoardCategory->id,
                'rm_subcategory_id' => null,
                'unit_type' => 'Kg',
                'material_type' => 'Direct Material',
                'cost_price' => $item->cost_price ?? 0,
                'remarks' => 'Auto-created from Reels Inventory paper quality.',
            ]);

            $item->save();

            if ($isNew) {
                RMStockLedger::create([
                    'rm_item_id' => $item->id,
                    'transaction_type' => 'opening',
                    'reference_id' => $item->id,
                    'quantity_in' => 0,
                    'quantity_out' => 0,
                    'balance_after' => 0,
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        });
    }

    private function nextRawMaterialCode(): string
    {
        $lastItem = RMItem::orderBy('id', 'desc')->first();
        $nextId = $lastItem ? $lastItem->id + 1 : 1;

        do {
            $code = 'RM-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (RMItem::where('code', $code)->exists());

        return $code;
    }
}

