<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;

class ReelIssueController extends Controller
{
    public function index(Request $request)
    {
        $query = ReelIssue::with('reel.paperQuality');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('reel', function ($q) use ($searchTerm) {
                $q->where('reel_no', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        $issues = $query->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);
            
        return response()->json($issues);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reel_no' => 'required|string|exists:reels',
            'issue_date' => 'required|date',
            'quantity_issued' => 'required|numeric|min:0.01',
            'return_to_stock_weight' => 'nullable|numeric|min:0',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'issued_to' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $reel = Reel::where('reel_no', $request->reel_no)->lockForUpdate()->first();

            if (!$reel) {
                return response()->json(['error' => 'Reel not found'], 404);
            }

            $returnWeight = (float) ($request->return_to_stock_weight ?? 0);

            if ($returnWeight > $request->quantity_issued) {
                return response()->json(['error' => 'Return to stock weight cannot exceed quantity issued'], 422);
            }

            if ($reel->balance_weight < $request->quantity_issued) {
                return response()->json(['error' => 'Insufficient balance weight'], 400);
            }

            $netConsumed = max((float) $request->quantity_issued - $returnWeight, 0);

            $issue = ReelIssue::create([
                'reel_id' => $reel->id,
                'issue_date' => $request->issue_date,
                'quantity_issued' => $request->quantity_issued,
                'return_to_stock_weight' => $returnWeight,
                'net_consumed_weight' => $netConsumed,
                'return_location' => $request->return_location,
                'issued_to' => $request->issued_to,
                'remarks' => $request->remarks,
            ]);

            $newBalance = max($reel->balance_weight - $netConsumed, 0);
            $reel->balance_weight = $newBalance;
            $this->refreshReelStatus($reel);
            
            // Validate and sync balance with transaction history
            $this->validateAndSyncBalance($reel);
            $reel->save();

            $autoReturnId = null;
            if ($returnWeight > 0) {
                $autoReturn = ReelReturn::create([
                    'reel_id' => $reel->id,
                    'return_date' => $request->issue_date,
                    'remaining_weight' => $newBalance,
                    'returned_to' => 'stock',
                    'return_location' => $request->return_location,
                    'condition' => 'good',
                    'remarks' => $request->remarks ? ($request->remarks . ' (auto return)') : 'Auto return from issue form',
                ]);
                $autoReturnId = $autoReturn->id;
            }

            if ($autoReturnId) {
                $issue->auto_return_id = $autoReturnId;
                $issue->save();
            }

            return response()->json($issue->load('reel'), 201);
        });
    }

    public function show($id)
    {
        $issue = ReelIssue::with('reel')->findOrFail($id);
        return response()->json($issue);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'quantity_issued' => 'required|numeric|min:0.01',
            'return_to_stock_weight' => 'nullable|numeric|min:0',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'issued_to' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $issue = ReelIssue::with(['reel'])->lockForUpdate()->findOrFail($id);
            $reel = $issue->reel;

            if (!$reel) {
                return response()->json(['error' => 'Associated reel not found'], 404);
            }

            if ($request->filled('reel_no') && strtoupper(trim($request->reel_no)) !== strtoupper($reel->reel_no)) {
                return response()->json(['error' => 'Changing the reel for an existing issue is not supported'], 422);
            }

            $returnWeight = (float) ($request->return_to_stock_weight ?? 0);
            if ($returnWeight > $request->quantity_issued) {
                return response()->json(['error' => 'Return to stock weight cannot exceed quantity issued'], 422);
            }

            $oldNet = (float) $issue->net_consumed_weight;
            $newNet = max((float) $request->quantity_issued - $returnWeight, 0);

            $balanceBeforeIssue = $reel->balance_weight + $oldNet;
            if ($newNet > $balanceBeforeIssue + 1e-6) {
                return response()->json(['error' => 'Insufficient balance weight'], 400);
            }

            $newBalance = max($balanceBeforeIssue - $newNet, 0);

            $issue->issue_date = $request->issue_date;
            $issue->quantity_issued = $request->quantity_issued;
            $issue->return_to_stock_weight = $returnWeight;
            $issue->net_consumed_weight = $newNet;
            $issue->return_location = $request->return_location;
            $issue->issued_to = $request->issued_to;
            $issue->remarks = $request->remarks;
            $issue->save();

            $reel->balance_weight = $newBalance;
            $this->refreshReelStatus($reel);
            
            // Validate and sync balance with transaction history
            $this->validateAndSyncBalance($reel);
            $reel->save();

            $autoReturn = $issue->auto_return_id ? ReelReturn::lockForUpdate()->find($issue->auto_return_id) : null;

            if ($returnWeight > 0) {
                if ($autoReturn) {
                    $autoReturn->update([
                        'return_date' => $request->issue_date,
                        'remaining_weight' => $newBalance,
                        'return_location' => $request->return_location,
                        'remarks' => $request->remarks ? ($request->remarks . ' (auto return)') : 'Auto return from issue form',
                    ]);
                } else {
                    $autoReturn = ReelReturn::create([
                        'reel_id' => $reel->id,
                        'return_date' => $request->issue_date,
                        'remaining_weight' => $newBalance,
                        'returned_to' => 'stock',
                        'return_location' => $request->return_location,
                        'condition' => 'good',
                        'remarks' => $request->remarks ? ($request->remarks . ' (auto return)') : 'Auto return from issue form',
                    ]);
                    $issue->auto_return_id = $autoReturn->id;
                    $issue->save();
                }
            } elseif ($autoReturn) {
                $autoReturn->delete();
                $issue->auto_return_id = null;
                $issue->save();
            }

            return response()->json($issue->load('reel'));
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $issue = ReelIssue::with(['reel'])->lockForUpdate()->findOrFail($id);
            $reel = $issue->reel;

            if (!$reel) {
                return response()->json(['error' => 'Associated reel not found'], 404);
            }

            $oldNet = (float) $issue->net_consumed_weight;
            $newBalance = min($reel->balance_weight + $oldNet, $reel->original_weight);
            $reel->balance_weight = $newBalance;
            $this->refreshReelStatus($reel);
            
            // Validate and sync balance with transaction history
            $this->validateAndSyncBalance($reel);
            $reel->save();

            if ($issue->auto_return_id) {
                $autoReturn = ReelReturn::lockForUpdate()->find($issue->auto_return_id);
                if ($autoReturn) {
                    $autoReturn->delete();
                }
            }

            $issue->delete();

            return response()->json(['message' => 'Issue deleted successfully.']);
        });
    }

    public function fetchReel($reel_no)
    {
        $reel = Reel::with('paperQuality', 'supplier')->where('reel_no', $reel_no)->first();
        if (!$reel) {
            return response()->json(['message' => 'Reel not found'], 404);
        }
        return response()->json($reel);
    }

    protected function refreshReelStatus(Reel $reel): void
    {
        if ($reel->balance_weight <= 0) {
            $reel->status = 'fully_used';
        } elseif ($reel->balance_weight < $reel->original_weight) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
    }

    /**
     * Validate and synchronize balance_weight with transaction history
     * This ensures the stored balance matches the calculated balance from all transactions
     *
     * @param Reel $reel
     * @return bool Returns true if balance is synchronized, false if discrepancy detected
     */
    protected function validateAndSyncBalance(Reel $reel): bool
    {
        // Calculate balance from transaction history
        $totalConsumed = $reel->issues()->sum('net_consumed_weight');
        $calculatedBalance = $reel->original_weight - $totalConsumed;
        
        // Allow small floating-point differences (0.01 kg tolerance)
        $difference = abs($calculatedBalance - $reel->balance_weight);
        
        if ($difference > 0.01) {
            // Log the discrepancy
            \Log::warning('Balance weight discrepancy detected', [
                'reel_no' => $reel->reel_no,
                'stored_balance' => $reel->balance_weight,
                'calculated_balance' => $calculatedBalance,
                'difference' => $difference,
                'action' => 'auto_correcting'
            ]);
            
            // Auto-correct the balance
            $reel->balance_weight = max($calculatedBalance, 0);
            
            return false; // Indicates a discrepancy was found and fixed
        }
        
        return true; // Balance is synchronized
    }
}

