<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->decimal('standard_gsm', 10, 2)->nullable()->after('min_gsm');
            $table->decimal('standard_bursting', 10, 2)->nullable()->after('min_bursting');
            $table->decimal('standard_moisture', 10, 2)->nullable()->after('min_moisture');
            $table->decimal('standard_cobb', 10, 2)->nullable()->after('min_cobb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->dropColumn([
                'standard_gsm',
                'standard_bursting',
                'standard_moisture',
                'standard_cobb',
            ]);
        });
    }
};
