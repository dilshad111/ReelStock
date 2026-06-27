<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;
use Illuminate\Support\Facades\DB;

$ids = [72, 576, 616];
foreach ($ids as $id) {
    $l = FGStockLedger::find($id);
    if ($l) {
        echo "Ledger ID: {$l->id} | Type: {$l->transaction_type} | Ref ID: {$l->reference_id} | Product: {$l->product_id} | Job: {$l->job_number} | In: {$l->quantity_in} | Out: {$l->quantity_out} | Date: {$l->transaction_date}\n";
    } else {
        echo "Ledger ID {$id} not found.\n";
    }
}
