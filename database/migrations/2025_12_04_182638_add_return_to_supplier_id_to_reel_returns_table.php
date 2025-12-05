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
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('return_to_supplier_id')->nullable()->after('vehicle_number');
            $table->foreign('return_to_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reel_returns', function (Blueprint $table) {
            $table->dropForeign(['return_to_supplier_id']);
            $table->dropColumn('return_to_supplier_id');
        });
    }
};
