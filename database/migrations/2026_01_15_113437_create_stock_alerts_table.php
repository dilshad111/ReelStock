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
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_quality_id')->constrained('paper_qualities')->onDelete('cascade');
            $table->decimal('gsm', 8, 2);
            $table->decimal('reel_size', 8, 2);
            $table->enum('alert_type', ['reels', 'weight']);
            $table->decimal('threshold_value', 12, 2);
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('stock_alerts');
    }
};
