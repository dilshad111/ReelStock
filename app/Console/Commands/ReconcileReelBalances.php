<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reel;
use App\Models\ReelIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReconcileReelBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reels:reconcile {--auto-fix : Automatically fix discrepancies} {--report-only : Only generate report without fixing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile reel balance_weight with transaction history and auto-correct discrepancies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Reel Balance Reconciliation...');
        $this->info('Timestamp: ' . Carbon::now()->toDateTimeString());
        $this->newLine();

        $autoFix = $this->option('auto-fix');
        $reportOnly = $this->option('report-only');

        if ($autoFix && $reportOnly) {
            $this->error('Cannot use both --auto-fix and --report-only options together.');
            return 1;
        }

        // Get all reels with their transaction-based balance
        $discrepancies = $this->findDiscrepancies();

        if ($discrepancies->isEmpty()) {
            $this->info('✓ No discrepancies found. All reel balances are synchronized.');
            Log::info('Reel reconciliation completed: No discrepancies found');
            return 0;
        }

        $this->warn('Found ' . $discrepancies->count() . ' reel(s) with discrepancies:');
        $this->newLine();

        // Display discrepancies in table format
        $tableData = [];
        foreach ($discrepancies as $disc) {
            $tableData[] = [
                $disc->reel_no,
                number_format($disc->original_weight, 2) . ' kg',
                number_format($disc->current_balance, 2) . ' kg',
                number_format($disc->correct_balance, 2) . ' kg',
                number_format($disc->difference, 2) . ' kg',
                $disc->current_status,
                $disc->correct_status,
            ];
        }

        $this->table(
            ['Reel No', 'Original', 'Current Balance', 'Correct Balance', 'Difference', 'Current Status', 'Correct Status'],
            $tableData
        );

        if ($reportOnly) {
            $this->info('Report-only mode. No changes made.');
            Log::info('Reel reconciliation report generated', ['discrepancies_count' => $discrepancies->count()]);
            return 0;
        }

        // Ask for confirmation unless auto-fix is enabled
        if (!$autoFix) {
            if (!$this->confirm('Do you want to fix these discrepancies?')) {
                $this->info('Reconciliation cancelled.');
                return 0;
            }
        }

        // Fix discrepancies
        $fixed = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($discrepancies as $disc) {
                $reel = Reel::where('reel_no', $disc->reel_no)->first();
                
                if ($reel) {
                    $oldBalance = $reel->balance_weight;
                    $oldStatus = $reel->status;
                    
                    $reel->balance_weight = $disc->correct_balance;
                    $reel->status = $disc->correct_status;
                    $reel->save();

                    $fixed++;
                    
                    Log::info('Reel balance corrected', [
                        'reel_no' => $disc->reel_no,
                        'old_balance' => $oldBalance,
                        'new_balance' => $disc->correct_balance,
                        'old_status' => $oldStatus,
                        'new_status' => $disc->correct_status,
                        'difference' => $disc->difference,
                    ]);
                } else {
                    $failed++;
                    $this->error('Failed to find reel: ' . $disc->reel_no);
                }
            }

            DB::commit();
            $this->newLine();
            $this->info('✓ Successfully fixed ' . $fixed . ' reel(s).');
            
            if ($failed > 0) {
                $this->warn('! Failed to fix ' . $failed . ' reel(s).');
            }

            Log::info('Reel reconciliation completed', [
                'fixed' => $fixed,
                'failed' => $failed,
            ]);

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during reconciliation: ' . $e->getMessage());
            Log::error('Reel reconciliation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Find all reels with balance discrepancies
     *
     * @return \Illuminate\Support\Collection
     */
    protected function findDiscrepancies()
    {
        return DB::table('reels as r')
            ->select([
                'r.id',
                'r.reel_no',
                'r.original_weight',
                'r.balance_weight as current_balance',
                'r.status as current_status',
                DB::raw("(r.original_weight - 
                    COALESCE((SELECT SUM(net_consumed_weight) FROM reel_issues WHERE reel_id = r.id), 0) - 
                    COALESCE((SELECT SUM(remaining_weight) FROM reel_returns WHERE reel_id = r.id AND returned_to = 'supplier'), 0)
                ) as correct_balance"),
                DB::raw("CASE 
                    WHEN (r.original_weight - 
                        COALESCE((SELECT SUM(net_consumed_weight) FROM reel_issues WHERE reel_id = r.id), 0) - 
                        COALESCE((SELECT SUM(remaining_weight) FROM reel_returns WHERE reel_id = r.id AND returned_to = 'supplier'), 0)
                    ) <= 0 THEN 'fully_used'
                    WHEN (r.original_weight - 
                        COALESCE((SELECT SUM(net_consumed_weight) FROM reel_issues WHERE reel_id = r.id), 0) - 
                        COALESCE((SELECT SUM(remaining_weight) FROM reel_returns WHERE reel_id = r.id AND returned_to = 'supplier'), 0)
                    ) < r.original_weight THEN 'partially_used'
                    ELSE 'in_stock'
                END as correct_status"),
            ])
            ->havingRaw('ABS(r.balance_weight - correct_balance) > 0.01 OR r.status != correct_status')
            ->get()
            ->map(function ($item) {
                $item->difference = $item->correct_balance - $item->current_balance;
                return $item;
            });
    }
}
