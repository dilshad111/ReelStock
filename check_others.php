<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;

$reels = ['RL111166', 'RL111484'];
foreach ($reels as $reelNo) {
    $reel = Reel::where('reel_no', $reelNo)->with('issues')->first();
    if ($reel) {
        echo "Reel: $reelNo, Original: {$reel->original_weight}, Balance: {$reel->balance_weight}, Issues Count: " . $reel->issues->count() . "\n";
        foreach ($reel->issues as $issue) {
            echo " - Issue Date: {$issue->issue_date}, Net: {$issue->net_consumed_weight}\n";
        }
    } else {
        echo "Reel: $reelNo Not Found\n";
    }
}
