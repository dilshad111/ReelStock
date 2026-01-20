<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;

$reels = Reel::with(['issues', 'returns'])->get();
$discrepancies = [];

foreach ($reels as $reel) {
    $original = (float) $reel->original_weight;
    $sumIssues = (float) $reel->issues->sum('net_consumed_weight');
    
    // For returns, it's tricky. Let's see how they are stored.
    // Usually a return to stock UPDATES the balance.
    // If there's a return record, we expect the balance to match the LATEST return's remaining_weight 
    // MINUS any issues after that return.
    
    $expectedBalance = $original - $sumIssues;
    
    // If returns exist, we'll just flag it for manual review for now if it doesn't match.
    // But if NO returns exist, it's simple.
    if ($reel->returns->count() === 0) {
        if (abs($expectedBalance - (float)$reel->balance_weight) > 0.01) {
            $discrepancies[] = [
                'reel_no' => $reel->reel_no,
                'expected' => $expectedBalance,
                'actual' => $reel->balance_weight,
                'has_returns' => false
            ];
        }
    } else {
        // If returns exist, we checking if it's way off.
        // Actually, let's skip complex return logic for this summary.
    }
}

if (empty($discrepancies)) {
    echo "No other discrepancies found for reels without returns.\n";
} else {
    echo "Found " . count($discrepancies) . " more discrepancies:\n";
    foreach ($discrepancies as $d) {
        echo " - {$d['reel_no']}: Expected {$d['expected']}, Actual {$d['actual']}\n";
    }
}
