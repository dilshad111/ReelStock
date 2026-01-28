<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking reels returned to supplier...\n";
echo str_repeat("=", 60) . "\n\n";

// Get all reel_returns where returned_to = 'supplier'
$supplierReturns = \App\Models\ReelReturn::where('returned_to', 'supplier')
    ->with('reel')
    ->get();

echo "Total supplier returns found: " . $supplierReturns->count() . "\n\n";

foreach ($supplierReturns as $return) {
    $reel = $return->reel;
    if (!$reel) continue;
    
    echo "Reel: {$reel->reel_no}\n";
    echo "  Current Status: {$reel->status}\n";
    echo "  Balance: {$reel->balance_weight} kg\n";
    echo "  Return Date: {$return->return_date}\n";
    echo "  Returned Weight: {$return->remaining_weight} kg\n";
    
    if ($reel->status !== 'returned_to_supplier') {
        echo "  ❌ PROBLEM: Status should be 'returned_to_supplier' but is '{$reel->status}'\n";
    } else {
        echo "  ✅ Status is correct\n";
    }
    echo "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Stock Report Filter Test\n";
echo str_repeat("=", 60) . "\n\n";

// Test the stock report filter
$stockQuery = \App\Models\Reel::query();
$stockQuery->where('status', '!=', 'returned_to_supplier')
      ->where(function ($q) {
          $q->where('balance_weight', '>', 0)
            ->orWhere(function ($inner) {
                $inner->whereNull('balance_weight')
                      ->where('original_weight', '>', 0);
            });
      });

$stockReels = $stockQuery->get(['reel_no', 'status', 'balance_weight']);

echo "Total reels in stock report: " . $stockReels->count() . "\n\n";

// Check if any returned reels are in the stock report
$returnedReelNos = $supplierReturns->pluck('reel.reel_no')->filter();
$inStockButReturned = $stockReels->whereIn('reel_no', $returnedReelNos);

if ($inStockButReturned->count() > 0) {
    echo "❌ PROBLEM: {$inStockButReturned->count()} returned reels are showing in stock report:\n";
    foreach ($inStockButReturned as $reel) {
        echo "  - {$reel->reel_no} (status: {$reel->status})\n";
    }
} else {
    echo "✅ GOOD: No returned reels in stock report\n";
}
