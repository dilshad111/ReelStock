<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('paper_qualities')
            ->select([
                'id',
                'min_gsm', 'max_gsm',
                'min_bursting', 'max_bursting',
                'min_moisture', 'max_moisture',
                'min_cobb', 'max_cobb',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($row->min_gsm !== null && $row->max_gsm !== null) {
                        $updates['standard_gsm'] = round((((float) $row->min_gsm) + ((float) $row->max_gsm)) / 2, 0);
                    }
                    if ($row->min_bursting !== null && $row->max_bursting !== null) {
                        $updates['standard_bursting'] = round((((float) $row->min_bursting) + ((float) $row->max_bursting)) / 2, 0);
                    }
                    if ($row->min_moisture !== null && $row->max_moisture !== null) {
                        $updates['standard_moisture'] = round((((float) $row->min_moisture) + ((float) $row->max_moisture)) / 2, 0);
                    }
                    if ($row->min_cobb !== null && $row->max_cobb !== null) {
                        $updates['standard_cobb'] = round((((float) $row->min_cobb) + ((float) $row->max_cobb)) / 2, 0);
                    }

                    if (!empty($updates)) {
                        DB::table('paper_qualities')->where('id', $row->id)->update($updates);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not clear standards in rollback to avoid data loss.
    }
};
