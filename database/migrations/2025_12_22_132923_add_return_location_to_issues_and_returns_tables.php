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
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->string('return_location')->nullable()->after('return_to_stock_weight');
        });

        Schema::table('reel_returns', function (Blueprint $table) {
            $table->string('return_location')->nullable()->after('returned_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->dropColumn('return_location');
        });

        Schema::table('reel_returns', function (Blueprint $table) {
            $table->dropColumn('return_location');
        });
    }
};
