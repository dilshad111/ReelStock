<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelReturn;
use App\Models\ReelIssue;
use App\Models\ReconciliationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconciliationController extends Controller
{
    /**
     * Run full stock reconciliation
     */
    public function runFullReconciliation(Request $request)
    {
        try {
            DB::beginTransaction();

            $totalReelsChecked = 0;
            $discrepanciesFound = 0;
            $correctionsMade = 0;
            $details = [];

            // Get all reels
            $reels = Reel::with(['returns', 'issues'])->get();
            $totalReelsChecked = $reels->count();

            foreach ($reels as $reel) {
                $changes = $this->reconcileReel($reel);
                if (!empty($changes)) {
                    $discrepanciesFound++;
                    if ($changes['corrected']) {
                        $correctionsMade++;
                        $details[] = $changes;
                    }
                }
            }

            // Log the reconciliation
            $log = ReconciliationLog::create([
                'run_date' => now(),
                'total_reels_checked' => $totalReelsChecked,
                'discrepancies_found' => $discrepanciesFound,
                'corrections_made' => $correctionsMade,
                'details' => $details,
                'run_by' => $request->user()->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation completed successfully',
                'summary' => [
                    'total_reels_checked' => $totalReelsChecked,
                    'discrepancies_found' => $discrepanciesFound,
                    'corrections_made' => $correctionsMade
                ],
                'details' => $details,
                'log_id' => $log->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reconciliation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Reconciliation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reconcile a single reel
     */
    private function reconcileReel(Reel $reel)
    {
        $changes = [];
        $oldStatus = $reel->status;
        $oldBalance = $reel->balance_weight;
        $corrected = false;

        // Check 1: Reels returned to supplier
        $supplierReturn = $reel->returns()
            ->where('returned_to', 'supplier')
            ->orderBy('return_date', 'desc')
            ->first();

        if ($supplierReturn && $reel->status !== 'returned_to_supplier') {
            $reel->status = 'returned_to_supplier';
            $reel->balance_weight = 0;
            $corrected = true;
            $changes['issue'] = 'Reel returned to supplier but status not updated';
            $changes['old_status'] = $oldStatus;
            $changes['new_status'] = 'returned_to_supplier';
            $changes['old_balance'] = $oldBalance;
            $changes['new_balance'] = 0;
        }

        // Check 2: Recalculate balance from transactions (if not returned to supplier)
        if ($reel->status !== 'returned_to_supplier') {
            $calculatedBalance = $this->calculateBalance($reel);
            
            // Allow small floating point differences (0.01 kg)
            if (abs($calculatedBalance - $reel->balance_weight) > 0.01) {
                $changes['issue'] = 'Balance weight mismatch with transaction history';
                $changes['old_balance'] = $reel->balance_weight;
                $changes['new_balance'] = $calculatedBalance;
                $reel->balance_weight = $calculatedBalance;
                $corrected = true;
            }

            // Check 3: Update status based on balance
            $expectedStatus = $this->getExpectedStatus($reel->balance_weight, $reel->original_weight);
            if ($expectedStatus !== $reel->status) {
                if (!isset($changes['issue'])) {
                    $changes['issue'] = 'Status does not match balance weight';
                } else {
                    $changes['issue'] .= ' & Status mismatch';
                }
                $changes['old_status'] = $reel->status;
                $changes['new_status'] = $expectedStatus;
                $reel->status = $expectedStatus;
                $corrected = true;
            }
        }

        // Save changes if any
        if ($corrected) {
            $reel->save();
            $changes['reel_no'] = $reel->reel_no;
            $changes['corrected'] = true;
            
            // Log changes for audit trail
            Log::info('Reconciliation - Reel corrected', [
                'reel_no' => $reel->reel_no,
                'changes' => $changes,
                'user_id' => auth()->id()
            ]);
        }

        return $changes;
    }

    /**
     * Calculate balance from transactions
     */
    private function calculateBalance(Reel $reel)
    {
        // Use net_consumed_weight which is the official field for weight reduction
        $totalConsumed = $reel->issues()->sum('net_consumed_weight');
        $balance = $reel->original_weight - $totalConsumed;
        
        return max(0, round($balance, 2));
    }

    /**
     * Get expected status based on balance
     */
    private function getExpectedStatus($balance, $original)
    {
        if ($balance <= 0) {
            return 'fully_used';
        } elseif ($balance < $original) {
            return 'partially_used';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Get reconciliation history
     */
    public function getReconciliationHistory(Request $request)
    {
        $logs = ReconciliationLog::with('user')
            ->orderBy('run_date', 'desc')
            ->paginate(20);

        return response()->json($logs);
    }

    /**
     * Get current discrepancies without fixing
     */
    public function getDiscrepancies()
    {
        $discrepancies = [];
        $reels = Reel::with(['returns', 'issues'])->get();

        foreach ($reels as $reel) {
            $issues = [];

            // Check for supplier returns
            $supplierReturn = $reel->returns()
                ->where('returned_to', 'supplier')
                ->orderBy('return_date', 'desc')
                ->first();

            if ($supplierReturn && $reel->status !== 'returned_to_supplier') {
                $issues[] = 'Returned to supplier but status is ' . $reel->status;
            }

            // Check balance calculation
            if ($reel->status !== 'returned_to_supplier') {
                $calculatedBalance = $this->calculateBalance($reel);
                if (abs($calculatedBalance - $reel->balance_weight) > 0.01) {
                    $issues[] = sprintf(
                        'Balance mismatch: Stored=%s kg, Calculated=%s kg',
                        number_format($reel->balance_weight, 2),
                        number_format($calculatedBalance, 2)
                    );
                }

                // Check status
                $expectedStatus = $this->getExpectedStatus($reel->balance_weight, $reel->original_weight);
                if ($expectedStatus !== $reel->status) {
                    $issues[] = sprintf('Status should be %s but is %s', $expectedStatus, $reel->status);
                }
            }

            if (!empty($issues)) {
                $discrepancies[] = [
                    'reel_no' => $reel->reel_no,
                    'current_status' => $reel->status,
                    'current_balance' => $reel->balance_weight,
                    'issues' => $issues
                ];
            }
        }

        return response()->json([
            'total_discrepancies' => count($discrepancies),
            'discrepancies' => $discrepancies
        ]);
    }

    /**
     * Reconcile a specific reel
     */
    public function reconcileSpecificReel(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $reel = Reel::with(['returns', 'issues'])->findOrFail($id);
            $changes = $this->reconcileReel($reel);

            DB::commit();

            if (empty($changes)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No discrepancies found for this reel'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reel reconciled successfully',
                'changes' => $changes
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reconciliation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
