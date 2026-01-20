<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use App\Models\ReelIssue;

$reels = Reel::with(['issues', 'returns'])->get();
$fixedCount = 0;

foreach ($reels as $reel) {
    if ($reel->returns->count() > 0) continue; // Skip complex cases

    $original = (float) $reel->original_weight;
    $sumIssues = (float) $reel->issues->sum('net_consumed_weight');
    $expectedBalance = max($original - $sumIssues, 0);
    
    if (abs($expectedBalance - (float)$reel->balance_weight) > 0.01) {
        echo "Fixing {$reel->reel_no}: {$reel->balance_weight} -> {$expectedBalance}\n";
        $reel->balance_weight = $expectedBalance;
        
        if ($expectedBalance <= 0) {
            $reel->status = 'fully_used';
        } elseif ($expectedBalance < $original) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
        
        $reel->save();
        $fixedCount++;
    }
}

echo "\nTotal reels fixed: $fixedCount\n";
