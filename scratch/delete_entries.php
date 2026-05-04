<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use Illuminate\Support\Facades\DB;

$ledgerIds = range(1, 18);

$receiptIds = FGStockLedger::whereIn('id', $ledgerIds)
    ->where('transaction_type', 'receipt')
    ->pluck('reference_id')
    ->unique()
    ->toArray();

$dispatchIds = FGStockLedger::whereIn('id', $ledgerIds)
    ->where('transaction_type', 'dispatch')
    ->pluck('reference_id')
    ->unique()
    ->toArray();

// Check if any other ledger entries point to these receipts/dispatches
$others = FGStockLedger::whereNotIn('id', $ledgerIds)
    ->where(function($q) use ($receiptIds, $dispatchIds) {
        $q->where(function($q2) use ($receiptIds) { $q2->where('transaction_type', 'receipt')->whereIn('reference_id', $receiptIds); })
          ->orWhere(function($q2) use ($dispatchIds) { $q2->where('transaction_type', 'dispatch')->whereIn('reference_id', $dispatchIds); })
          ->orWhere(function($q2) use ($receiptIds, $dispatchIds) { 
              $q2->where('transaction_type', 'adjustment')
                 ->where(function($q3) use ($receiptIds, $dispatchIds) {
                     $q3->whereIn('reference_id', $receiptIds)->orWhereIn('reference_id', $dispatchIds);
                 });
          });
    })->get();

if ($others->count() > 0) {
    echo "WARNING: Other ledger entries depend on these transactions:\n";
    foreach ($others as $o) {
        echo "ID: {$o->id} Type: {$o->transaction_type} Ref: {$o->reference_id}\n";
    }
    echo "Aborting deletion.\n";
    exit(1);
}

echo "Ledger IDs to delete: " . implode(',', $ledgerIds) . "\n";
echo "Receipt IDs to delete: " . implode(',', $receiptIds) . "\n";
echo "Dispatch IDs to delete: " . implode(',', $dispatchIds) . "\n";

DB::transaction(function() use ($ledgerIds, $receiptIds, $dispatchIds) {
    FGStockLedger::whereIn('id', $ledgerIds)->delete();
    FGReceipt::whereIn('id', $receiptIds)->delete();
    FGDispatch::whereIn('id', $dispatchIds)->delete();
});

echo "Deletion successful.\n";
