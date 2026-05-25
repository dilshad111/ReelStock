<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- REGISTERED USERS AND ROLES ---\n";
$users = DB::table('users')
    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
    ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name')
    ->get();

foreach ($users as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: " . ($u->role_name ?? 'N/A') . "\n";
}
