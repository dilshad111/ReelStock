<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to job_cards table
        Schema::table('job_cards', function (Blueprint $table) {
            $table->decimal('length_mm', 10, 2)->nullable()->after('planned_qty');
            $table->decimal('width_mm', 10, 2)->nullable()->after('length_mm');
            $table->decimal('height_mm', 10, 2)->nullable()->after('width_mm');
            $table->string('uom', 10)->default('mm')->after('height_mm');
            $table->decimal('deckle_size', 10, 2)->nullable()->after('uom');
            $table->decimal('sheet_length', 10, 2)->nullable()->after('deckle_size');
            $table->integer('ups')->default(1)->after('sheet_length');
            $table->string('carton_type', 50)->default('FEFCO 0201')->after('ups');
            $table->string('machine_name', 100)->nullable()->after('carton_type');
            $table->integer('target_speed')->default(0)->after('machine_name');
            $table->string('printing_process', 50)->nullable()->after('target_speed');
            $table->string('pasting_closure', 50)->nullable()->after('printing_process');
            $table->integer('printing_colors_count')->default(0)->after('pasting_closure');
            $table->text('pantone_colors')->nullable()->after('printing_colors_count'); // Store JSON/string array
            $table->text('special_details')->nullable()->after('pantone_colors'); // Store JSON for Honeycomb/Separators
            $table->integer('pieces_count')->default(1)->after('special_details');
            $table->decimal('est_unit_weight', 12, 4)->default(0)->after('pieces_count');
        });

        // 2. Create job_card_pieces table for multi-piece cardboard configurations
        Schema::create('job_card_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->onDelete('cascade');
            $table->string('piece_name', 100);
            $table->decimal('length_mm', 10, 2)->nullable();
            $table->decimal('width_mm', 10, 2)->nullable();
            $table->decimal('height_mm', 10, 2)->nullable();
            $table->decimal('deckle_size', 10, 2)->nullable();
            $table->decimal('sheet_length', 10, 2)->nullable();
            $table->integer('ups')->default(1);
            $table->string('machine_name', 100)->nullable();
            $table->integer('target_speed')->default(0);
            $table->decimal('est_unit_weight', 12, 4)->default(0);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        // 3. Create job_card_layers table for 3/5/7-ply layer structures
        Schema::create('job_card_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->onDelete('cascade');
            $table->foreignId('job_card_piece_id')->nullable()->constrained('job_card_pieces')->onDelete('cascade');
            $table->string('layer_type', 50); // e.g. Liner 1, Fluting 1, Liner 2, etc.
            $table->string('paper_name', 100)->nullable();
            $table->integer('gsm')->default(0);
            $table->string('flute_profile', 20)->default('Flat'); // e.g. Flat, B, C, E
            $table->integer('sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_layers');
        Schema::dropIfExists('job_card_pieces');
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn([
                'length_mm', 'width_mm', 'height_mm', 'uom', 'deckle_size', 'sheet_length', 'ups',
                'carton_type', 'machine_name', 'target_speed', 'printing_process', 'pasting_closure',
                'printing_colors_count', 'pantone_colors', 'special_details', 'pieces_count', 'est_unit_weight'
            ]);
        });
    }
};
