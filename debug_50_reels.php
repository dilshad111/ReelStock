<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$targetDate = Carbon::now();
$size = 50;

$reels = DB::table('reels')
    ->where('reel_size', $size)
    ->leftJoin('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
    ->select('reels.*', 'paper_qualities.quality', 'paper_qualities.gsm_range')
    ->get();

echo "Checking reels of size $size\n";
echo str_pad("Reel No", 15) . " | " . str_pad("Orig Wt", 10) . " | " . str_pad("Bal Wt", 10) . " | " . str_pad("Calc Bal", 10) . " | " . "Diff\n";
echo str_repeat("-", 60) . "\n";

$totalBalWt = 0;
$totalCalcBal = 0;

foreach ($reels as $reel) {
    if ($reel->balance_weight === null) {
        $reel->balance_weight = $reel->original_weight;
    }

    $issues = DB::table('reel_issues')
        ->where('reel_id', $reel->id)
        ->get();

    $returns = DB::table('reel_returns')
        ->where('reel_id', $reel->id)
        ->get();

    $calcBal = (float)$reel->original_weight;
    
    $transactions = collect();
    foreach ($issues as $issue) {
        $consumed = (float)$issue->quantity_issued;
        if (property_exists($issue, 'net_consumed_weight') && $issue->net_consumed_weight !== null) {
            $consumed = (float)$issue->net_consumed_weight;
        } elseif (property_exists($issue, 'return_to_stock_weight') && (float)$issue->return_to_stock_weight > 0) {
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
    $diff = $calcBal - $reel->balance_weight;

    if (abs($diff) > 0.01) {
        echo str_pad($reel->reel_no, 15) . " | " . 
             str_pad($reel->original_weight, 10) . " | " . 
             str_pad($reel->balance_weight, 10) . " | " . 
             str_pad(round($calcBal, 2), 10) . " | " . 
             round($diff, 2) . "\n";
    }

    $totalBalWt += $reel->balance_weight;
    $totalCalcBal += $calcBal;
}

echo str_repeat("-", 60) . "\n";
echo str_pad("TOTAL", 15) . " | " . str_pad("", 10) . " | " . str_pad(round($totalBalWt, 2), 10) . " | " . str_pad(round($totalCalcBal, 2), 10) . " | " . round($totalCalcBal - $totalBalWt, 2) . "\n";
