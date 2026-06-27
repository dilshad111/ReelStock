<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use App\Models\FGStockLedger;

$products = Product::with('customer')->get();
$discrepancies = [];

echo sprintf("%-10s %-15s %-30s %-15s %-15s %-15s\n", "ID", "Code", "Name", "Current Bal", "Rebuilt Bal", "Diff");
echo str_repeat("-", 105) . "\n";

foreach ($products as $product) {
    // Current balance in ledger
    $lastLedger = FGStockLedger::where('product_id', $product->id)->latest('id')->first();
    $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : (float)$product->opening_balance;
    
    // Rebuilt balance: opening + receipts - dispatches
    $receiptsSum = (float)FGReceipt::where('product_id', $product->id)->sum('quantity_produced');
    $dispatchesSum = (float)FGDispatch::where('product_id', $product->id)->sum('quantity_dispatched');
    $rebuiltBalance = (float)$product->opening_balance + $receiptsSum - $dispatchesSum;
    
    $diff = $rebuiltBalance - $currentBalance;
    
    if (abs($diff) > 0.001) {
        $discrepancies[] = [
            'product' => $product,
            'current' => $currentBalance,
            'rebuilt' => $rebuiltBalance,
            'diff' => $diff
        ];
        echo sprintf("%-10d %-15s %-30s %-15.2f %-15.2f %-15.2f\n", 
            $product->id, 
            $product->item_code, 
            substr($product->item_name, 0, 30), 
            $currentBalance, 
            $rebuiltBalance, 
            $diff
        );
    }
}

echo "\nTotal products with discrepancies: " . count($discrepancies) . "\n";
