<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PaperQuality;
use App\Models\Supplier;

class ReelReceiptSeeder extends Seeder
{
    public function run()
    {
        $qualities = PaperQuality::all();
        $suppliers = Supplier::all();

        $startDate = strtotime('2025-09-01');
        $endDate = strtotime('2025-10-31');

        $lastReelNo = DB::table('reels')->max('reel_no') ?? 'RL2026000000';
        $lastNum = (int) substr($lastReelNo, 6);
        $startNum = $lastNum + 1;

        for ($i = 0; $i < 200; $i++) {
            $num = $startNum + $i;
            $reelNo = 'RL2026' . str_pad($num, 6, '0', STR_PAD_LEFT);

            $randomQuality = $qualities->random();
            $randomSupplier = $suppliers->random();

            $randomDate = date('Y-m-d', rand($startDate, $endDate));
            $randomWeight = rand(800, 1500);
            $randomSize = rand(28, 50);
            $qcStatus = (($i + 1) % 10 == 0) ? 'on_hold' : 'approved'; // 10% on_hold

            $reelId = DB::table('reels')->insertGetId([
                'reel_no' => $reelNo,
                'paper_quality_id' => $randomQuality->id,
                'supplier_id' => $randomSupplier->id,
                'reel_size' => $randomSize,
                'original_weight' => $randomWeight,
                'balance_weight' => $randomWeight,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('reel_receipts')->insert([
                'reel_id' => $reelId,
                'receiving_date' => $randomDate,
                'gsm' => null,
                'bursting_strength' => null,
                'qc_status' => $qcStatus,
                'remarks' => 'Sample data',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
