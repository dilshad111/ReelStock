<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;
use App\Models\ReelIssue;

$issue = ReelIssue::find(160);
if ($issue) {
    echo "Issue 160 Created At: " . $issue->created_at . "\n";
    echo "Issue 160 Updated At: " . $issue->updated_at . "\n";
    
    $reel = Reel::find($issue->reel_id);
    if ($reel) {
        echo "Reel {$reel->reel_no} Updated At: " . $reel->updated_at . "\n";
    }
}
