<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use App\Models\FGStockLedger;

$product = Product::where('item_code', '30000872')->first();
if (!$product) {
    echo "Product with item code 30000872 not found.\n";
    // Check by item_code containing 000872
    $product = Product::where('item_code', 'like', '%000872%')->first();
}

if (!$product) {
    echo "No product found with code 000872.\n";
    exit;
}

echo "Product ID: " . $product->id . "\n";
echo "Item Code: " . $product->item_code . "\n";
echo "Item Name: " . $product->item_name . "\n";
echo "Opening Balance: " . $product->opening_balance . "\n";

echo "\n--- FG RECEIPTS ---\n";
$receipts = FGReceipt::where('product_id', $product->id)->get();
foreach ($receipts as $r) {
    echo "ID: {$r->id}, Job: {$r->job_number}, Date: {$r->date}, Qty: {$r->quantity_produced}\n";
}

echo "\n--- FG DISPATCHES ---\n";
$dispatches = FGDispatch::where('product_id', $product->id)->get();
foreach ($dispatches as $d) {
    echo "ID: {$d->id}, Job: {$d->job_number}, Date: {$d->date}, Qty: {$d->quantity_dispatched}, DC: {$d->dc_number}\n";
}

echo "\n--- FG STOCK LEDGER ---\n";
$ledgers = FGStockLedger::where('product_id', $product->id)->orderBy('id', 'asc')->get();
foreach ($ledgers as $l) {
    echo "ID: {$l->id}, Date: {$l->transaction_date}, Type: {$l->transaction_type}, In: {$l->quantity_in}, Out: {$l->quantity_out}, Bal: {$l->balance_after}\n";
}
