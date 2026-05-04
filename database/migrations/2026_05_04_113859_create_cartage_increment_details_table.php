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
        Schema::create('cartage_increment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartage_increment_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipping_address_id')->constrained();
            $table->decimal('old_rate', 10, 2);
            $table->decimal('new_rate', 10, 2);
            $table->decimal('amount_increase', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartage_increment_details');
    }
};
