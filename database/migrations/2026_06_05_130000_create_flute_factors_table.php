<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flute_factors', function (Blueprint $table) {
            $table->id();
            $table->string('flute_type', 20)->unique();
            $table->decimal('factor', 8, 4)->default(1);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('flute_factors')->insert([
            [
                'flute_type' => 'Flat',
                'factor' => 1.0000,
                'description' => 'Liner / paper layer factor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'flute_type' => 'B',
                'factor' => 1.3500,
                'description' => 'B flute take-up factor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'flute_type' => 'C',
                'factor' => 1.4500,
                'description' => 'C flute take-up factor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'flute_type' => 'E',
                'factor' => 1.2500,
                'description' => 'E flute take-up factor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('flute_factors');
    }
};
