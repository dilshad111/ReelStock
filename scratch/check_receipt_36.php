<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use App\Models\Product;

$receipt = FGReceipt::with('product')->find(36);
echo "--- RECEIPT 36 ---\n";
print_r($receipt->toArray());

echo "\n--- LEDGER FOR RECEIPT 36 ---\n";
$ledgers = FGStockLedger::where('transaction_type', 'receipt')
    ->where('reference_id', 36)
    ->get();
foreach ($ledgers as $l) {
    print_r($l->toArray());
    $p = Product::find($l->product_id);
    echo "Ledger Product ID {$l->product_id} Code: " . ($p->item_code ?? 'None') . ", Name: " . ($p->item_name ?? 'None') . "\n";
}
