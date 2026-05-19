<?php
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$defaultUoms = ['KG', 'METER', 'PIECE', 'DRUM', 'BAG', 'ROLL', 'PACK', 'TON'];

foreach ($defaultUoms as $uom) {
    DB::table('unit_of_measures')->updateOrInsert(
        ['name' => $uom],
        [
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]
    );
}

echo "Default UoMs seeded successfully.\n";
