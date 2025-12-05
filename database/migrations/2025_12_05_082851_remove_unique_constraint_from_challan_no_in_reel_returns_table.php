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
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->dropUnique('reel_returns_challan_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->unique('challan_no', 'reel_returns_challan_no_unique');
        });
    }
};
