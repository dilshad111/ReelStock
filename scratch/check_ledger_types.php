<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$types = DB::table('fg_stock_ledger')
    ->select('transaction_type', DB::raw('count(*) as count'), DB::raw('sum(quantity_in) as total_in'), DB::raw('sum(quantity_out) as total_out'))
    ->groupBy('transaction_type')
    ->get();

foreach ($types as $t) {
    echo "Type: {$t->transaction_type} | Count: {$t->count} | Total In: {$t->total_in} | Total Out: {$t->total_out}\n";
}
