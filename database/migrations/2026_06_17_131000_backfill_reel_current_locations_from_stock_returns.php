<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('reels', 'current_location')) {
            return;
        }

        $latestReturns = DB::table('reel_returns as rr')
            ->join(DB::raw("(SELECT reel_id, MAX(id) as latest_id FROM reel_returns WHERE returned_to = 'stock' GROUP BY reel_id) latest"), function ($join) {
                $join->on('rr.id', '=', 'latest.latest_id');
            })
            ->where('rr.returned_to', 'stock')
            ->whereNotNull('rr.return_location')
            ->select('rr.reel_id', 'rr.return_location')
            ->get();

        foreach ($latestReturns as $return) {
            $location = $return->return_location === 'Factory' ? 'Factory' : 'Warehouse';

            DB::table('reels')
                ->where('id', $return->reel_id)
                ->update(['current_location' => $location]);
        }
    }

    public function down()
    {
        // Historical backfill is intentionally not reversed.
    }
};
