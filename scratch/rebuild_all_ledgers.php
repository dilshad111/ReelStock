<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\FGStockLedger;

echo "Starting Finish Goods Stock Ledger Rebuild...\n";

$products = Product::all();
$successCount = 0;
$failureCount = 0;

foreach ($products as $product) {
    try {
        FGStockLedger::recalculateForProduct($product->id);
        echo "Product ID {$product->id} (Code: {$product->item_code}) rebuilt successfully.\n";
        $successCount++;
    } catch (\Exception $e) {
        echo "FAIL - Product ID {$product->id} (Code: {$product->item_code}): " . $e->getMessage() . "\n";
        $failureCount++;
    }
}

echo "\nRebuild complete.\n";
echo "Successfully rebuilt: $successCount\n";
echo "Failed: $failureCount\n";
