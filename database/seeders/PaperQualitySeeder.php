<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaperQualitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $qualities = [
            ['quality' => 'Special Fluting', 'gsm_range' => '105–110 gsm', 'item_code' => 'SF001'],
            ['quality' => 'Special Fluting', 'gsm_range' => '120–125 gsm', 'item_code' => 'SF002'],
            ['quality' => 'Ultra Fluting', 'gsm_range' => '110–115 gsm', 'item_code' => 'UF001'],
            ['quality' => 'Ultra Fluting', 'gsm_range' => '130–140 gsm', 'item_code' => 'UF002'],
            ['quality' => 'Local Dubai Liner', 'gsm_range' => '110–115 gsm', 'item_code' => 'LDL001'],
            ['quality' => 'Local Dubai Liner', 'gsm_range' => '130–140 gsm', 'item_code' => 'LDL002'],
            ['quality' => 'Local Kraft Liner', 'gsm_range' => '110–115 gsm', 'item_code' => 'LKL001'],
            ['quality' => 'Local Kraft Liner', 'gsm_range' => '155–160 gsm', 'item_code' => 'LKL002'],
            ['quality' => 'WDL', 'gsm_range' => '140–150 gsm', 'item_code' => 'WDL001'],
            ['quality' => 'Box Board 3#', 'gsm_range' => '170–180 gsm', 'item_code' => 'BB3001'],
            ['quality' => 'Box Board 2.5#', 'gsm_range' => '170–180 gsm', 'item_code' => 'BB2001'],
            ['quality' => 'Local Kraft Liner', 'gsm_range' => '225–230 gsm', 'item_code' => 'LKL003'],
            ['quality' => 'Local Kraft Liner', 'gsm_range' => '245–250 gsm', 'item_code' => 'LKL004'],
            ['quality' => 'Imported Kraft Liner', 'gsm_range' => '100–110 gsm', 'item_code' => 'IKL001'],
            ['quality' => 'Imported Kraft Liner', 'gsm_range' => '160–170 gsm', 'item_code' => 'IKL002'],
            ['quality' => 'Imported Kraft Liner', 'gsm_range' => '220–225 gsm', 'item_code' => 'IKL003'],
            ['quality' => 'Yellow Liner', 'gsm_range' => '130–140 gsm', 'item_code' => 'YL001'],
        ];

        foreach ($qualities as $quality) {
            \App\Models\PaperQuality::updateOrCreate(
                ['item_code' => $quality['item_code']],
                $quality
            );
        }
    }
}
