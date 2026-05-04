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
        Schema::table('fg_receipts', function (Blueprint $table) {
            $table->decimal('carton_price', 15, 2)->nullable()->after('quantity_produced');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fg_receipts', function (Blueprint $table) {
            $table->dropColumn('carton_price');
        });
    }
};
