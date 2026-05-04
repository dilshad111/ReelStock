<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;

$ledgers = FGStockLedger::whereIn('id', range(1, 18))->get();
foreach ($ledgers as $l) {
    echo "ID: {$l->id} | Type: {$l->transaction_type} | Ref: {$l->reference_id} | Job: {$l->job_number} | Prod: {$l->product_id} | In: {$l->quantity_in} | Out: {$l->quantity_out} | Bal: {$l->balance_after} | Date: {$l->transaction_date}\n";
}
