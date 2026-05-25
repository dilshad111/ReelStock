<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- ORPHAN ENTRIES IN cartage_entries ---\n";
$orphans = DB::table('cartage_entries')
    ->leftJoin('cartage_bills', 'cartage_entries.cartage_bill_id', '=', 'cartage_bills.id')
    ->whereNull('cartage_bills.id')
    ->select('cartage_entries.*')
    ->get();

echo "Total orphan entries: " . count($orphans) . "\n";
foreach ($orphans as $entry) {
    echo "ID: {$entry->id} | Bill ID: {$entry->cartage_bill_id} | Date: {$entry->entry_date} | Customer ID: {$entry->customer_id} | Vehicle: {$entry->vehicle_number} | Amount: {$entry->amount}\n";
}

echo "\n--- ALL DISTINCT cartage_bill_id IN cartage_entries ---\n";
$distinctBillIds = DB::table('cartage_entries')
    ->select('cartage_bill_id', DB::raw('count(*) as count'))
    ->groupBy('cartage_bill_id')
    ->get();

foreach ($distinctBillIds as $row) {
    echo "Cartage Bill ID: {$row->cartage_bill_id} | Entries Count: {$row->count}\n";
}
