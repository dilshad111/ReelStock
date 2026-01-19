<?php

namespace App\Http\Controllers;

use App\Models\StockAlert;
use App\Models\Reel;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function index()
    {
        $alerts = StockAlert::with('paperQuality')->get();
        
        foreach ($alerts as $alert) {
            $alert->current_value = $this->calculateCurrentStock($alert);
        }

        return $alerts;
    }

    private function calculateCurrentStock($alert)
    {
        $reelsQuery = Reel::query()
            ->join('reel_receipts', 'reels.id', '=', 'reel_receipts.reel_id')
            ->where('reels.paper_quality_id', $alert->paper_quality_id)
            ->where('reels.reel_size', $alert->reel_size)
            ->where('reels.status', '!=', 'fully_used');
        
        if ($alert->alert_type === 'reels') {
            return $reelsQuery->count();
        } else {
            return round($reelsQuery->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(balance_weight, original_weight)')), 2);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'paper_quality_id' => 'required|exists:paper_qualities,id',
            'reel_size' => 'required|numeric',
            'alert_type' => 'required|in:reels,weight',
            'threshold_value' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $alert = StockAlert::create($validated);
        return response()->json($alert, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'paper_quality_id' => 'required|exists:paper_qualities,id',
            'reel_size' => 'required|numeric',
            'alert_type' => 'required|in:reels,weight',
            'threshold_value' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $alert = StockAlert::findOrFail($id);
        $alert->update($validated);
        return response()->json($alert);
    }

    public function destroy($id)
    {
        StockAlert::destroy($id);
        return response()->json(null, 204);
    }

    public function toggle($id)
    {
        $alert = StockAlert::findOrFail($id);
        $alert->is_active = !$alert->is_active;
        $alert->save();
        return response()->json($alert);
    }

    public function getTriggeredAlerts()
    {
        $alerts = StockAlert::with('paperQuality')->where('is_active', true)->get();
        $triggered = [];

        foreach ($alerts as $alert) {
            $currentValue = $this->calculateCurrentStock($alert);

            if ($currentValue < $alert->threshold_value) {
                $triggered[] = [
                    'id' => $alert->id,
                    'quality_name' => $alert->paperQuality->quality,
                    'gsm_range' => $alert->paperQuality->gsm_range,
                    'reel_size' => $alert->reel_size,
                    'alert_type' => $alert->alert_type,
                    'threshold_value' => $alert->threshold_value,
                    'current_value' => $currentValue,
                    'shortage' => $alert->threshold_value - $currentValue
                ];
            }
        }

        return response()->json($triggered);
    }
}
