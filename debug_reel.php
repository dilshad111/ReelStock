<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use Illuminate\Support\Facades\DB;

$reel = Reel::where('reel_no', 'RL111480')->with('receipts')->first();
if ($reel) {
    echo "Reel RL111480 found.\n";
    echo "Original Weight: " . $reel->original_weight . "\n";
    echo "Balance Weight: " . $reel->balance_weight . "\n";
    foreach ($reel->receipts as $r) {
        echo "Receipt ID: " . $r->id . ", Date: " . $r->receiving_date . "\n";
    }
} else {
    echo "Reel RL111480 NOT found.\n";
}

$reelsWithoutReceipts = Reel::doesntHave('receipts')->count();
echo "Reels without receipts: $reelsWithoutReceipts\n";
