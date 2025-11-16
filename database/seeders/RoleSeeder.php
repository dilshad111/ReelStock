<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Role::updateOrCreate(['name' => 'Admin']);
        \App\Models\Role::updateOrCreate(['name' => 'Storekeeper']);
        \App\Models\Role::updateOrCreate(['name' => 'QC']);
        \App\Models\Role::updateOrCreate(['name' => 'Production']);
    }
}
