<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->dropColumn('gsm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->decimal('gsm', 8, 2)->after('paper_quality_id');
        });
    }
};
