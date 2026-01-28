<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$reelNo = 'RL111613';

$reel = \App\Models\Reel::where('reel_no', $reelNo)->first();

if ($reel) {
    echo "Reel found: {$reel->reel_no}\n";
    echo "Reel Size: " . ($reel->reel_size ?? 'NULL') . "\n";
    echo "Original Weight: {$reel->original_weight}\n";
    echo "Quality ID: {$reel->paper_quality_id}\n";
    echo "Supplier ID: {$reel->supplier_id}\n";
    
    echo "\n--- Calling getReelHistory API logic ---\n";
    
    // Simulate what the controller returns
    $controller = new \App\Http\Controllers\ReelStockReportController();
    $response = $controller->getReelHistory($reelNo);
    $data = $response->getData(true);
    
    echo "\nAPI Response reel data:\n";
    print_r($data['reel']);
} else {
    echo "Reel not found: {$reelNo}\n";
}
