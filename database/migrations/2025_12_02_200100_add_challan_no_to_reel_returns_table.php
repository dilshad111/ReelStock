<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->string('challan_no', 20)->nullable()->after('reel_id');
        });

        DB::transaction(function () {
            $prefix = 'RT';
            $padding = 3;
            $sequence = 0;

            $returns = DB::table('reel_returns')
                ->where('returned_to', 'supplier')
                ->orderBy('return_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            foreach ($returns as $return) {
                $sequence++;
                $generated = $prefix . str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
                DB::table('reel_returns')->where('id', $return->id)->update(['challan_no' => $generated]);
            }
        });

        Schema::table('reel_returns', function (Blueprint $table) {
            $table->unique('challan_no', 'reel_returns_challan_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->dropUnique('reel_returns_challan_no_unique');
            $table->dropColumn('challan_no');
        });
    }
};
