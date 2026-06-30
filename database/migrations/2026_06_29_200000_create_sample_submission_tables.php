<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Master table — one row per sample submission
        Schema::create('sample_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('sample_date');

            // Inner dimensions
            $table->decimal('length', 10, 2);
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->enum('uom', ['mm', 'cm', 'inch'])->default('mm');

            $table->integer('quantity')->default(1);
            $table->enum('print_type', ['printed', 'un-print'])->default('un-print');
            $table->enum('ply', ['3', '5', '7'])->default('3');
            $table->boolean('size_approval_only')->default(false);

            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('sample_date');
        });

        // 2. Add-ons — honeycomb / separator child records
        Schema::create('sample_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_submission_id')
                  ->constrained('sample_submissions')
                  ->cascadeOnDelete();

            $table->enum('type', ['honeycomb', 'separator']);

            // Addon own dimensions
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();

            $table->enum('ply', ['3', '5', '7'])->default('3');
            $table->enum('source', ['in-house', 'outsource'])->default('in-house');

            $table->timestamps();
        });

        // 3. Paper construction layers — polymorphic (parent = submission or addon)
        Schema::create('sample_paper_layers', function (Blueprint $table) {
            $table->id();

            // Polymorphic: parent_type + parent_id  →  SampleSubmission | SampleAddon
            $table->string('parent_type');
            $table->unsignedBigInteger('parent_id');

            $table->unsignedTinyInteger('layer_sequence');
            $table->string('paper_type');       // e.g. "Top Layer", "Flute-B"
            $table->foreignId('paper_quality_id')->nullable()->constrained('paper_qualities')->nullOnDelete();
            $table->unsignedInteger('gsm')->nullable();


            $table->timestamps();

            $table->index(['parent_type', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('sample_paper_layers');
        Schema::dropIfExists('sample_addons');
        Schema::dropIfExists('sample_submissions');
    }
};
