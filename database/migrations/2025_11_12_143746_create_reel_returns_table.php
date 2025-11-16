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
        Schema::create('reel_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels');
            $table->date('return_date');
            $table->decimal('remaining_weight', 10, 2);
            $table->enum('condition', ['good', 'damaged', 'qc_required'])->default('good');
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
        Schema::dropIfExists('reel_returns');
    }
};
