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
            $table->unsignedBigInteger('parent_entry_id')->nullable()->after('cartage_bill_id');
            $table->boolean('is_return')->default(false)->after('amount');
            $table->boolean('is_second_location')->default(false)->after('is_return');
            $table->string('remarks')->nullable()->after('is_second_location');
            
            $table->foreign('parent_entry_id')->references('id')->on('cartage_entries')->onDelete('cascade');
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
            $table->dropForeign(['parent_entry_id']);
            $table->dropColumn(['parent_entry_id', 'is_return', 'is_second_location', 'remarks']);
        });
    }
};
