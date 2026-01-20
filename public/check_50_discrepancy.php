<?php
// Since this is in public/, we need to go up one level to reach the root
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

header('Content-Type: text/plain');

$targetDate = Carbon::now();
$size = 50;

$reels = DB::table('reels')
    ->where('reel_size', $size)
    ->get();

echo "Reels of size $size:\n";
echo "ID | Reel No | Orig Wt | Bal Wt | Status | Calc Bal | Diff\n";
echo "-----------------------------------------------------------\n";

foreach ($reels as $reel) {
    $issues = DB::table('reel_issues')->where('reel_id', $reel->id)->get();
    $returns = DB::table('reel_returns')->where('reel_id', $reel->id)->get();

    $calcBal = (float)$reel->original_weight;
    $transactions = collect();
    foreach ($issues as $issue) {
        $consumed = (float)$issue->quantity_issued;
        if (isset($issue->net_consumed_weight) && $issue->net_consumed_weight !== null) {
            $consumed = (float)$issue->net_consumed_weight;
        } elseif (isset($issue->return_to_stock_weight) && (float)$issue->return_to_stock_weight > 0) {
            $consumed = max(0, (float)$issue->quantity_issued - (float)$issue->return_to_stock_weight);
        }
        $transactions->push(['type' => 'issue', 'weight' => $consumed, 'date' => $issue->issue_date, 'id' => $issue->id]);
    }
    foreach ($returns as $return) {
        $transactions->push(['type' => 'return_' . $return->returned_to, 'weight' => (float)$return->remaining_weight, 'date' => $return->return_date, 'id' => $return->id]);
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
            $calcBal -= $tx['weight'];
        } elseif ($tx['type'] === 'return_stock') {
            $calcBal = $tx['weight'];
        } elseif ($tx['type'] === 'return_supplier') {
            $calcBal -= $tx['weight'];
        }
    }
    
    $calcBal = max(0, $calcBal);
    $diff = $calcBal - (float)$reel->balance_weight;

    if (abs($diff) > 0.1) {
        echo "{$reel->id} | {$reel->reel_no} | {$reel->original_weight} | {$reel->balance_weight} | {$reel->status} | " . round($calcBal, 2) . " | " . round($diff, 2) . "\n";
    }
}
