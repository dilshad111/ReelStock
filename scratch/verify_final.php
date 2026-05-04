<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;
use App\Models\Product;

$productIds = [10, 21, 24];

foreach ($productIds as $pid) {
    $product = Product::find($pid);
    $count = FGStockLedger::where('product_id', $pid)->count();
    $last = FGStockLedger::where('product_id', $pid)->latest('id')->first();
    $balance = $last ? $last->balance_after : $product->opening_balance;
    echo "Product: {$product->item_code} | Remaining Entries: $count | Current Balance: $balance\n";
}
