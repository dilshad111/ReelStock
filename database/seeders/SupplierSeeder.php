<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $suppliers = [
            ['name' => 'ABC Paper Mills', 'contact_person' => 'John Doe', 'address' => '123 Paper St, City', 'phone' => '123-456-7890', 'email' => 'john@abc.com', 'notes' => 'Reliable supplier'],
            ['name' => 'XYZ Packaging', 'contact_person' => 'Jane Smith', 'address' => '456 Box Ave, Town', 'phone' => '987-654-3210', 'email' => 'jane@xyz.com', 'notes' => 'Bulk orders'],
            ['name' => 'Global Kraft', 'contact_person' => 'Mike Johnson', 'address' => '789 Kraft Rd, Village', 'phone' => '555-123-4567', 'email' => 'mike@global.com', 'notes' => 'International supplier'],
        ];

        foreach ($suppliers as $index => $supplier) {
            $supplierId = 'SUP' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            \App\Models\Supplier::create(array_merge($supplier, ['supplier_id' => $supplierId]));
        }
    }
}
