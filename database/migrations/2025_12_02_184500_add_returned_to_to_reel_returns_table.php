<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_returns', 'returned_to')) {
                $table->enum('returned_to', ['stock', 'supplier'])->default('stock')->after('remaining_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            if (Schema::hasColumn('reel_returns', 'returned_to')) {
                $table->dropColumn('returned_to');
            }
        });
    }
};
