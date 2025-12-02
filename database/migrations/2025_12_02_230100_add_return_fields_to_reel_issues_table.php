<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->decimal('return_to_stock_weight', 10, 2)->default(0)->after('quantity_issued');
            $table->decimal('net_consumed_weight', 10, 2)->default(0)->after('return_to_stock_weight');
        });
    }

    public function down(): void
    {
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->dropColumn(['return_to_stock_weight', 'net_consumed_weight']);
        });
    }
};
