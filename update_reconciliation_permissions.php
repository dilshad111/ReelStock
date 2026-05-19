<?php
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = DB::table('users')->pluck('id');

foreach ($users as $userId) {
    DB::table('user_permissions')->updateOrInsert(
        ['user_id' => $userId, 'menu' => 'reconciliation'],
        [
            'can_view' => 1, 
            'can_add' => 1, 
            'can_edit' => 1, 
            'can_delete' => 1, 
            'can_see_amounts' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]
    );
}

echo "Reconciliation permissions updated successfully.\n";
