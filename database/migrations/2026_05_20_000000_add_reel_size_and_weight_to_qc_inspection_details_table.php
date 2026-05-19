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
        Schema::table('qc_inspection_details', function (Blueprint $table) {
            $table->decimal('reel_size', 8, 2)->nullable()->after('reel_no');
            $table->decimal('reel_weight', 10, 2)->nullable()->after('reel_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qc_inspection_details', function (Blueprint $table) {
            $table->dropColumn(['reel_size', 'reel_weight']);
        });
    }
};
