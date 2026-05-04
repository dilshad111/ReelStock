<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGStockLedger;

$entries = [
    // Date, PID, Job, Type, In, Out, Bal
    ['2026-04-28', 10, '1300', 'adjustment', 0, 15000, 0],
    ['2026-04-28', 10, '1300', 'adjustment', 2500, 0, 15000],
    ['2026-04-28', 10, '1300', 'dispatch', 0, 2500, 12500],
    ['2026-04-28', 10, '1300', 'receipt', 15000, 0, 15000],
    ['2026-04-27', 24, '1300', 'adjustment', 0, 15000, 0],
    ['2026-04-27', 24, '1300', 'adjustment', 4000, 0, 15000],
    ['2026-04-27', 24, '1300', 'dispatch', 0, 4000, 11000],
    ['2026-04-27', 24, '1300', 'receipt', 15000, 0, 15000],
    ['2026-04-27', 21, '1301', 'adjustment', 0, 2500, 0],
    ['2026-04-27', 10, '1300', 'adjustment', 0, 5000, 0],
    ['2026-04-27', 21, '1301', 'adjustment', 1130, 0, 2500],
    ['2026-04-27', 21, '1301', 'adjustment', 1370, 0, 1370],
    ['2026-04-27', 10, '1300', 'adjustment', 2000, 0, 5000],
    ['2026-04-27', 21, '1301', 'dispatch', 0, 1130, 0],
    ['2026-04-27', 21, '1301', 'dispatch', 0, 1370, 1130],
    ['2026-04-27', 21, '1301', 'receipt', 2500, 0, 2500],
    ['2026-04-27', 10, '1300', 'dispatch', 0, 2000, 3000],
    ['2026-04-27', 10, '1300', 'receipt', 5000, 0, 5000],
];

$foundIds = [];

foreach ($entries as $e) {
    $found = FGStockLedger::where('transaction_date', $e[0])
        ->where('product_id', $e[1])
        ->where('job_number', $e[2])
        ->where('transaction_type', $e[3])
        ->where('quantity_in', $e[4])
        ->where('quantity_out', $e[5])
        ->where('balance_after', $e[6])
        ->get();
    
    if ($found->count() > 0) {
        foreach ($found as $f) {
            $foundIds[] = $f->id;
            echo "Found ID: {$f->id} | Date: {$f->transaction_date} | PID: {$f->product_id} | Job: {$f->job_number} | Type: {$f->transaction_type} | In: {$f->quantity_in} | Out: {$f->quantity_out} | Bal: {$f->balance_after}\n";
        }
    } else {
        echo "NOT FOUND: Date: {$e[0]} | PID: {$e[1]} | Job: {$e[2]} | Type: {$e[3]} | In: {$e[4]} | Out: {$e[5]} | Bal: {$e[6]}\n";
    }
}

echo "\nTotal IDs to delete: " . count($foundIds) . "\n";
echo "IDs: " . implode(',', $foundIds) . "\n";
