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
        Schema::create('reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('run_date');
            $table->integer('total_reels_checked')->default(0);
            $table->integer('discrepancies_found')->default(0);
            $table->integer('corrections_made')->default(0);
            $table->json('details')->nullable();
            $table->unsignedBigInteger('run_by')->nullable();
            $table->timestamps();

            $table->foreign('run_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reconciliation_logs');
    }
};
