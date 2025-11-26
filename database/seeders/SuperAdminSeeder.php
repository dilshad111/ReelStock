<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        User::updateOrCreate(
            ['email' => 'superadmin@qc.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@Super?'),
                'role_id' => $role->id,
            ]
        );
    }
}
