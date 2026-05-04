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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Seed some defaults
        DB::table('vehicle_types')->insert([
            ['name' => 'Suzuki', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shehzore', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mazda', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '1x17 Container', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '1x20 Container', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
