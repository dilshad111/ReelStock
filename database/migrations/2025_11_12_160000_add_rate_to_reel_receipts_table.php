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
        Schema::table('reel_receipts', function (Blueprint $table) {
            $table->decimal('rate_per_kg', 10, 2)->nullable()->after('qc_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reel_receipts', function (Blueprint $table) {
            $table->dropColumn('rate_per_kg');
        });
    }
};
