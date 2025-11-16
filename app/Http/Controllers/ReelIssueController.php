<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelIssue;

class ReelIssueController extends Controller
{
    public function index()
    {
        return response()->json(ReelIssue::with('reel.paperQuality')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'reel_no' => 'required|string|exists:reels',
            'issue_date' => 'required|date',
            'quantity_issued' => 'required|numeric|min:0.01',
            'issued_to' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        $reel = Reel::where('reel_no', $request->reel_no)->first();

        if (!$reel) {
            return response()->json(['error' => 'Reel not found'], 404);
        }

        if ($reel->balance_weight < $request->quantity_issued) {
            return response()->json(['error' => 'Insufficient balance weight'], 400);
        }

        $issue = ReelIssue::create([
            'reel_id' => $reel->id,
            'issue_date' => $request->issue_date,
            'quantity_issued' => $request->quantity_issued,
            'issued_to' => $request->issued_to,
            'remarks' => $request->remarks,
        ]);

        // Update reel balance
        $reel->update(['balance_weight' => $reel->balance_weight - $request->quantity_issued]);

        if ($reel->balance_weight <= 0) {
            $reel->update(['status' => 'fully_used']);
        } else {
            $reel->update(['status' => 'partially_used']);
        }

        return response()->json($issue->load('reel'), 201);
    }

    public function show($id)
    {
        $issue = ReelIssue::with('reel')->findOrFail($id);
        return response()->json($issue);
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
