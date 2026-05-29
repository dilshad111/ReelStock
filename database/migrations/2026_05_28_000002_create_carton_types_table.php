<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carton_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('standard_code', 50)->unique();
            $table->string('preview_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('carton_types')->insert([
            [
                'name' => 'Regular Slotted Carton',
                'standard_code' => '0201',
                'preview_image' => '/images/fefco/0201.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Slotted Carton',
                'standard_code' => '0200',
                'preview_image' => '/images/fefco/0200.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Folder Type Carton',
                'standard_code' => '0427',
                'preview_image' => '/images/fefco/0427.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Half Slotted Carton',
                'standard_code' => '0201-HSC',
                'preview_image' => '/images/fefco/0201-HSC.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_types');
    }
};
