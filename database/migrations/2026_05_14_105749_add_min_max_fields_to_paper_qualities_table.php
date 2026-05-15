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
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->decimal('max_gsm', 10, 2)->nullable()->after('min_gsm');
            $table->decimal('max_bursting', 10, 2)->nullable()->after('min_bursting');
            $table->decimal('min_moisture', 10, 2)->nullable()->after('max_moisture');
            $table->decimal('min_cobb', 10, 2)->nullable()->after('max_cobb');
            $table->unsignedBigInteger('paper_color_id')->nullable()->after('paper_color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->dropColumn(['max_gsm', 'max_bursting', 'min_moisture', 'min_cobb', 'paper_color_id']);
        });
    }
};
