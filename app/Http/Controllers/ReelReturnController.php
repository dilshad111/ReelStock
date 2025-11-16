<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelReturn;

class ReelReturnController extends Controller
{
    public function index()
    {
        return response()->json(ReelReturn::with('reel.paperQuality')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'reel_no' => 'required|string|exists:reels',
            'return_date' => 'required|date',
            'remaining_weight' => 'required|numeric|min:0',
            'condition' => 'required|in:good,damaged,qc_required',
            'remarks' => 'nullable|string',
        ]);

        $reel = Reel::where('reel_no', $request->reel_no)->first();

        if (!$reel) {
            return response()->json(['error' => 'Reel not found'], 404);
        }

        if ($request->remaining_weight > $reel->original_weight) {
            return response()->json(['error' => 'Remaining weight cannot exceed original weight'], 400);
        }

        $return = ReelReturn::create([
            'reel_id' => $reel->id,
            'return_date' => $request->return_date,
            'remaining_weight' => $request->remaining_weight,
            'condition' => $request->condition,
            'remarks' => $request->remarks,
        ]);

        // Update reel balance
        $reel->update(['balance_weight' => $request->remaining_weight]);

        if ($reel->balance_weight <= 0) {
            $reel->update(['status' => 'fully_used']);
        } elseif ($reel->balance_weight < $reel->original_weight) {
            $reel->update(['status' => 'partially_used']);
        } else {
            $reel->update(['status' => 'in_stock']);
        }

        return response()->json($return->load('reel'), 201);
    }

    public function show($id)
    {
        $return = ReelReturn::with('reel')->findOrFail($id);
        return response()->json($return);
    }

    public function fetchReel($reel_no)
    {
        $reel = Reel::with('paperQuality', 'supplier')->where('reel_no', $reel_no)->first();
        if (!$reel) {
            return response()->json(['message' => 'Reel not found'], 404);
        }
        return response()->json($reel);
    }
}
