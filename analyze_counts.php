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

$total = $currentStockReels->count();
$full = $currentStockReels->filter(function($r) {
    return $r->status === 'in_stock' || is_null($r->status) || $r->balance_weight == $r->original_weight;
})->count();

$partial = $currentStockReels->filter(function($r) {
    return $r->status === 'partially_used';
})->count();

echo "Total reels currently counted: $total\n";
echo "Full Reels: $full\n";
echo "Partially Used Reels: $partial\n";

// Double check the math
if ($full + $partial !== $total) {
    echo "\nNOTE: There are " . ($total - ($full + $partial)) . " reels with other statuses or mixed conditions.\n";
    $others = $currentStockReels->filter(function($r) use ($full, $partial) {
         return !($r->status === 'in_stock' || is_null($r->status) || $r->balance_weight == $r->original_weight)
                && $r->status !== 'partially_used';
    });
    foreach($others as $ot) {
        echo "ID: {$ot->id} | No: {$ot->reel_no} | Status: {$ot->status} | Bal: {$ot->balance_weight} | Orig: {$ot->original_weight}\n";
    }
}
