<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reel;

$currentStockReels = Reel::where('status', '!=', 'returned_to_supplier')
    ->where(function ($query) {
        $query->whereNull('status')
              ->orWhere('status', '!=', 'fully_used');
    })
    ->where(function ($query) {
        $query->where(function ($q) {
            $q->whereNotNull('balance_weight')
              ->where('balance_weight', '>', 0);
        })->orWhere(function ($q) {
            $q->whereNull('balance_weight')
              ->where('original_weight', '>', 0);
        });
    })
    ->get();

echo "STOUS BREAKDOWN OF ALL REELS WITH BALANCE > 0:\n";
foreach($currentStockReels->groupBy('status') as $status => $group) {
    $s = $status ?: 'NULL';
    echo "$s: " . $group->count() . "\n";
}
