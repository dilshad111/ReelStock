<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use App\Models\FGStockLedger;

echo "--- AUDITING FG RECEIPTS VS LEDGER ---\n";
$receipts = FGReceipt::all();
$missingReceiptsCount = 0;
$mismatchedReceiptsCount = 0;

foreach ($receipts as $r) {
    $ledger = FGStockLedger::where('product_id', $r->product_id)
        ->where('transaction_type', 'receipt')
        ->where('reference_id', $r->id)
        ->first();
    
    if (!$ledger) {
        // Try without product_id, maybe it was recorded under wrong product
        $ledgerAny = FGStockLedger::where('transaction_type', 'receipt')
            ->where('reference_id', $r->id)
            ->first();
        if ($ledgerAny) {
            echo "Receipt ID {$r->id} (Product ID {$r->product_id}) exists in ledger but with product ID {$ledgerAny->product_id}!\n";
            $mismatchedReceiptsCount++;
        } else {
            echo "Receipt ID {$r->id} (Product ID {$r->product_id}, Customer ID {$r->customer_id}, Job {$r->job_number}, Date {$r->date}, Qty {$r->quantity_produced}) is MISSING from ledger!\n";
            $missingReceiptsCount++;
        }
    } else {
        if ((float)$ledger->quantity_in !== (float)$r->quantity_produced) {
            echo "Receipt ID {$r->id} quantity mismatch: Receipt Qty = {$r->quantity_produced}, Ledger Qty In = {$ledger->quantity_in}\n";
            $mismatchedReceiptsCount++;
        }
    }
}

echo "Total missing receipts: $missingReceiptsCount\n";
echo "Total mismatched receipts: $mismatchedReceiptsCount\n";

echo "\n--- AUDITING FG DISPATCHES VS LEDGER ---\n";
$dispatches = FGDispatch::all();
$missingDispatchesCount = 0;
$mismatchedDispatchesCount = 0;

foreach ($dispatches as $d) {
    $ledger = FGStockLedger::where('product_id', $d->product_id)
        ->where('transaction_type', 'dispatch')
        ->where('reference_id', $d->id)
        ->first();
        
    if (!$ledger) {
        $ledgerAny = FGStockLedger::where('transaction_type', 'dispatch')
            ->where('reference_id', $d->id)
            ->first();
        if ($ledgerAny) {
            echo "Dispatch ID {$d->id} exists in ledger but with product ID {$ledgerAny->product_id}!\n";
            $mismatchedDispatchesCount++;
        } else {
            echo "Dispatch ID {$d->id} (Product ID {$d->product_id}, Customer ID {$d->customer_id}, Job {$d->job_number}, Date {$d->date}, Qty {$d->quantity_dispatched}) is MISSING from ledger!\n";
            $missingDispatchesCount++;
        }
    } else {
        if ((float)$ledger->quantity_out !== (float)$d->quantity_dispatched) {
            echo "Dispatch ID {$d->id} quantity mismatch: Dispatch Qty = {$d->quantity_dispatched}, Ledger Qty Out = {$ledger->quantity_out}\n";
            $mismatchedDispatchesCount++;
        }
    }
}

echo "Total missing dispatches: $missingDispatchesCount\n";
echo "Total mismatched dispatches: $mismatchedDispatchesCount\n";
