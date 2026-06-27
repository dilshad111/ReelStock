<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;

$ledgers = FGStockLedger::where('reference_id', 36)->get();
echo "Total entries: " . count($ledgers) . "\n";
foreach ($ledgers as $l) {
    print_r($l->toArray());
}
