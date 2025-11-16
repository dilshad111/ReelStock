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
        Schema::create('reels', function (Blueprint $table) {
            $table->id();
            $table->string('reel_no')->unique();
            $table->foreignId('paper_quality_id')->constrained('paper_qualities');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->decimal('reel_size', 8, 2);
            $table->decimal('original_weight', 10, 2);
            $table->decimal('balance_weight', 10, 2);
            $table->enum('status', ['in_stock', 'partially_used', 'fully_used'])->default('in_stock');
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
        Schema::dropIfExists('reels');
    }
};
