<?php
// Since this is in public/, we need to go up one level to reach the root
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

header('Content-Type: text/plain');

$targetDate = Carbon::now();
$sizeFilter = 50.0;

$reels = DB::table('reels')
    ->where('reel_size', $sizeFilter)
    ->get();

$issuesByReel = DB::table('reel_issues')
    ->where('issue_date', '<=', $targetDate->toDateString())
    ->get()
    ->groupBy('reel_id');

$returnsByReel = DB::table('reel_returns')
    ->where('return_date', '<=', $targetDate->toDateString())
    ->get()
    ->groupBy('reel_id');

echo "Reels of size $sizeFilter where (Calculated Balance != Live Balance Weight):\n";
echo "ID | Reel No | Orig Wt | Bal Wt | Status | Calc Bal | Diff\n";
echo "-----------------------------------------------------------\n";

$totalLive = 0;
$totalCalc = 0;

foreach ($reels as $reel) {
    $closingBalance = (float)($reel->original_weight ?? 0);
    $reelIssues = $issuesByReel->get($reel->id, collect());
    $reelReturns = $returnsByReel->get($reel->id, collect());
    
    $transactions = collect();
    foreach ($reelIssues as $issue) {
        $consumed = (float)$issue->quantity_issued;
        if (property_exists($issue, 'net_consumed_weight') && $issue->net_consumed_weight !== null) {
            $consumed = (float)$issue->net_consumed_weight;
        } elseif (property_exists($issue, 'return_to_stock_weight') && (float)$issue->return_to_stock_weight > 0) {
            $consumed = max(0, (float)$issue->quantity_issued - (float)$issue->return_to_stock_weight);
        }
        $transactions->push(['date' => $issue->issue_date, 'id' => $issue->id, 'type' => 'issue', 'weight' => $consumed]);
    }
    foreach ($reelReturns as $return) {
        $transactions->push(['date' => $return->return_date, 'id' => $return->id, 'type' => 'return_' . $return->returned_to, 'weight' => (float)$return->remaining_weight]);
    }
    
    $sorted = $transactions->sort(function($a, $b) {
        if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
        if ($a['type'] !== $b['type']) {
            if ($a['type'] === 'issue') return -1;
            return 1;
        }
        return $a['id'] <=> $b['id'];
    });
    
    foreach ($sorted as $tx) {
        if ($tx['type'] === 'issue') {
            $closingBalance -= $tx['weight'];
        } elseif ($tx['type'] === 'return_stock' || $tx['type'] === 'return_to_stock') {
            $closingBalance = $tx['weight'];
        } elseif ($tx['type'] === 'return_supplier') {
            $closingBalance -= $tx['weight'];
        }
    }
    
    $closingBalance = max(0, $closingBalance);
    $diff = $closingBalance - (float)$reel->balance_weight;
    
    if (abs($diff) > 0.01) {
        echo "{$reel->id} | {$reel->reel_no} | {$reel->original_weight} | {$reel->balance_weight} | {$reel->status} | " . round($closingBalance, 2) . " | " . round($diff, 2) . "\n";
    }
    
    $totalLive += (float)$reel->balance_weight;
    $totalCalc += $closingBalance;
}

echo "-----------------------------------------------------------\n";
echo "TOTAL | | | " . round($totalLive, 2) . " | | " . round($totalCalc, 2) . " | " . round($totalCalc - $totalLive, 2) . "\n";
echo "-----------------------------------------------------------\n";
