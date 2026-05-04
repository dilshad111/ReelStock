<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cartage_increment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type');
            $table->date('effective_date');
            $table->string('increment_type')->nullable(); // percentage, fixed
            $table->decimal('increment_value', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartage_increment_logs');
    }
};
