<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Job Cards Table
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('job_card_no')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('fg_product_id')->constrained('products');
            $table->decimal('planned_qty', 15, 2);
            $table->date('planned_date');
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['Open', 'In-Progress', 'Completed', 'Cancelled'])->default('Open');
            $table->text('specifications')->nullable(); // Size, Flute, Ply, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Job Card RM Requirements (BOM for this specific job)
        Schema::create('job_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->onDelete('cascade');
            $table->foreignId('rm_item_id')->constrained('rm_items');
            $table->decimal('required_qty', 15, 2);
            $table->decimal('consumed_qty', 15, 2)->default(0);
            $table->string('unit');
            $table->timestamps();
        });

        // 3. Job Card Process Steps
        Schema::create('job_card_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->onDelete('cascade');
            $table->string('step_name'); // Corrugation, Pasting, Printing, etc.
            $table->integer('sequence')->default(0);
            $table->enum('status', ['Pending', 'In-Progress', 'Completed'])->default('Pending');
            $table->decimal('produced_qty', 15, 2)->default(0);
            $table->decimal('wastage_qty', 15, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 4. Daily Production Logs
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards');
            $table->foreignId('job_card_step_id')->constrained('job_card_steps');
            $table->date('date');
            $table->string('shift')->nullable(); // Morning, Night
            $table->string('machine_no')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('wastage', 15, 2)->default(0);
            $table->string('operator_name')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_logs');
        Schema::dropIfExists('job_card_steps');
        Schema::dropIfExists('job_card_items');
        Schema::dropIfExists('job_cards');
    }
};
