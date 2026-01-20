<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;

function fixReel($reelNo) {
    echo "Fixing Reel: $reelNo\n";
    $reel = Reel::where('reel_no', $reelNo)->first();
    if (!$reel) {
        echo "Error: Reel $reelNo not found.\n";
        return;
    }

    $originalWeight = (float) $reel->original_weight;
    $totalNetConsumed = (float) ReelIssue::where('reel_id', $reel->id)->sum('net_consumed_weight');
    $totalReturnedToStock = (float) ReelReturn::where('reel_id', $reel->id)
        ->where('returned_to', 'stock')
        ->sum('remaining_weight');
    
    // Note: This simple sum logic might be too simple if multiple segments are returned.
    // However, for RL111480, it has 1 issue and 0 returns.
    
    // Let's use the logic: balance = original - sum(issues) + returns (if returns are additions)
    // Actually, in this system, returns to stock usually represent a reel being partially put back with a NEW balance.
    
    // Let's just recalculate based on the issues for now if no returns.
    if ($reel->returns->count() === 0) {
        $newBalance = max($originalWeight - $totalNetConsumed, 0);
    } else {
        // More complex logic needed if returns exist, but let's handle the simple case first.
        $newBalance = $reel->balance_weight; // Skip complex ones for now unless specified
        echo "Reel has returns, skipping auto-fix for safety. Manual check needed.\n";
        return;
    }

    if (abs($newBalance - $reel->balance_weight) > 0.01) {
        echo "Updating balance from {$reel->balance_weight} to {$newBalance}\n";
        $reel->balance_weight = $newBalance;
        
        if ($newBalance <= 0) {
            $reel->status = 'fully_used';
        } elseif ($newBalance < $originalWeight) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
        
        $reel->save();
        echo "Fixed!\n";
    } else {
        echo "Balance is already correct ({$reel->balance_weight}).\n";
    }
}

fixReel('RL111480');
