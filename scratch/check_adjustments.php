<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;
use App\Models\FGReceipt;
use App\Models\FGDispatch;

$adjustments = FGStockLedger::where('transaction_type', 'adjustment')->get();
$receiptsLinked = 0;
$dispatchesLinked = 0;
$others = 0;

foreach ($adjustments as $a) {
    // Check if it's linked to a receipt or dispatch
    $hasReceipt = FGReceipt::find($a->reference_id);
    $hasDispatch = FGDispatch::find($a->reference_id);
    
    // In our code:
    // Receipts Controller creates adjustment entries with reference_id = receipt->id
    // Dispatches Controller creates adjustment entries with reference_id = dispatch->id
    // Let's see if either exists
    if ($hasReceipt && $hasDispatch) {
        echo "Adjustment ID {$a->id} has BOTH receipt and dispatch with ID {$a->reference_id}!\n";
    } elseif ($hasReceipt) {
        $receiptsLinked++;
    } elseif ($hasDispatch) {
        $dispatchesLinked++;
    } else {
        echo "Orphan adjustment: ID {$a->id}, Reference: {$a->reference_id}, Product: {$a->product_id}, Job: {$a->job_number}, In: {$a->quantity_in}, Out: {$a->quantity_out}\n";
        $others++;
    }
}

echo "Linked to receipts: $receiptsLinked\n";
echo "Linked to dispatches: $dispatchesLinked\n";
echo "Other / Orphan: $others\n";
