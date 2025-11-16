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
        Schema::create('reel_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels');
            $table->date('receiving_date');
            $table->decimal('gsm', 5, 2)->nullable();
            $table->decimal('bursting_strength', 8, 2)->nullable();
            $table->enum('qc_status', ['approved', 'rejected', 'on_hold'])->default('on_hold');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reel_receipts');
    }
};
