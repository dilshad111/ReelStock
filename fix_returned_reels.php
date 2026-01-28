<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "Fixing reels returned to supplier...\n";
echo str_repeat("=", 60) . "\n\n";

// Get all reel IDs that have supplier returns
$reelIds = \App\Models\ReelReturn::where('returned_to', 'supplier')
    ->pluck('reel_id')
    ->unique();

echo "Found {$reelIds->count()} reels with supplier returns\n\n";

$fixed = 0;
$alreadyCorrect = 0;

foreach ($reelIds as $reelId) {
    $reel = \App\Models\Reel::find($reelId);
    if (!$reel) continue;
    
    $oldStatus = $reel->status;
    
    if ($reel->status !== 'returned_to_supplier') {
        echo "Fixing {$reel->reel_no}: '{$oldStatus}' -> 'returned_to_supplier'\n";
        $reel->status = 'returned_to_supplier';
        $reel->balance_weight = 0;
        $reel->save();
        $fixed++;
    } else {
        $alreadyCorrect++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Summary:\n";
echo "  ✅ Fixed: {$fixed} reels\n";
echo "  ✅ Already correct: {$alreadyCorrect} reels\n";
echo "  Total: " . ($fixed + $alreadyCorrect) . " reels\n";
echo str_repeat("=", 60) . "\n";
