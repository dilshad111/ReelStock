<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use App\Models\ReelIssue;

$reelNo = 'RL111480';
$reel = Reel::where('reel_no', $reelNo)->with(['issues', 'returns'])->first();

if (!$reel) {
    echo "Reel $reelNo NOT found in database.\n";
    
    // Search for just 111480 in case prefix is different
    $others = Reel::where('reel_no', 'LIKE', '%111480%')->get();
    if ($others->count() > 0) {
        echo "Found similar reels:\n";
        foreach ($others as $o) {
            echo " - " . $o->reel_no . " (ID: " . $o->id . ")\n";
        }
    }
    exit;
}

echo "Reel: " . $reel->reel_no . " (ID: " . $reel->id . ")\n";
echo "Original Weight: " . $reel->original_weight . "\n";
echo "Current Balance (Live): " . $reel->balance_weight . "\n";
echo "Status: " . $reel->status . "\n";

echo "\nIssues:\n";
foreach ($reel->issues as $issue) {
    echo " - Date: " . $issue->issue_date . ", Issued: " . $issue->quantity_issued . ", Net: " . $issue->net_consumed_weight . ", ID: " . $issue->id . "\n";
}

echo "\nReturns:\n";
foreach ($reel->returns as $return) {
    echo " - Date: " . $return->return_date . ", Weight: " . $return->remaining_weight . ", To: " . $return->returned_to . ", ID: " . $return->id . "\n";
}

// Check for all issues on 05/01/2026
echo "\nChecking all issues on 05/01/2026:\n";
$issuesOnDate = ReelIssue::where('issue_date', '2026-01-05')->with('reel')->get();
foreach ($issuesOnDate as $i) {
    echo " - Reel: " . ($i->reel->reel_no ?? 'Unknown') . ", Issued: " . $i->quantity_issued . ", Net: " . $i->net_consumed_weight . "\n";
}
