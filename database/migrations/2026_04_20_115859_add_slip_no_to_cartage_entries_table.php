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
        Schema::table('cartage_entries', function (Blueprint $table) {
            $table->string('slip_no')->nullable()->after('dc_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cartage_entries', function (Blueprint $table) {
            $table->dropColumn('slip_no');
        });
    }
};
