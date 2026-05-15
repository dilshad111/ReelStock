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
        // 1. Add QC criteria fields to paper_qualities
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->decimal('min_gsm', 8, 2)->nullable()->after('gsm_range');
            $table->decimal('min_bursting', 8, 2)->nullable()->after('min_gsm');
            $table->decimal('max_moisture', 8, 2)->nullable()->after('min_bursting');
            $table->decimal('max_cobb', 8, 2)->nullable()->after('max_moisture');
            $table->string('paper_color')->nullable()->after('max_cobb');
        });

        // 2. Add lot_number, po_number, grn_number to reel_receipts
        Schema::table('reel_receipts', function (Blueprint $table) {
            $table->string('lot_number')->nullable()->after('reel_id');
            $table->string('po_number')->nullable()->after('lot_number');
            $table->string('grn_number')->nullable()->after('po_number');
            $table->index('lot_number');
        });

        // 3. Create lot_sequences table for auto-generation
        Schema::create('lot_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('LOT');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        // Seed default lot sequence
        \DB::table('lot_sequences')->insert([
            'prefix' => 'LOT',
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create qc_inspections table (header)
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('lot_number')->index();
            $table->foreignId('paper_quality_id')->constrained('paper_qualities');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('po_number')->nullable();
            $table->string('grn_number')->nullable();
            $table->date('received_date');
            $table->date('inspection_date');
            $table->string('inspector_name');
            $table->enum('qc_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users');
            $table->decimal('avg_gsm', 8, 2)->nullable();
            $table->decimal('avg_bursting', 8, 2)->nullable();
            $table->decimal('avg_moisture', 8, 2)->nullable();
            $table->decimal('avg_cobb', 8, 2)->nullable();
            $table->timestamps();
        });

        // 5. Create qc_inspection_details table (per-reel results)
        Schema::create('qc_inspection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->onDelete('cascade');
            $table->foreignId('reel_id')->constrained('reels');
            $table->string('reel_no')->nullable();
            $table->decimal('gsm', 8, 2)->nullable();
            $table->decimal('bursting', 8, 2)->nullable();
            $table->decimal('moisture', 8, 2)->nullable();
            $table->decimal('ash', 8, 2)->nullable();
            $table->decimal('cobb', 8, 2)->nullable();
            $table->boolean('is_passed')->default(true);
            $table->json('failed_params')->nullable();
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
        Schema::dropIfExists('qc_inspection_details');
        Schema::dropIfExists('qc_inspections');
        Schema::dropIfExists('lot_sequences');

        Schema::table('reel_receipts', function (Blueprint $table) {
            $table->dropIndex(['lot_number']);
            $table->dropColumn(['lot_number', 'po_number', 'grn_number']);
        });

        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->dropColumn(['min_gsm', 'min_bursting', 'max_moisture', 'max_cobb', 'paper_color']);
        });
    }
};
