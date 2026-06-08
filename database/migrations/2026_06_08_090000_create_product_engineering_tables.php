<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineering_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 100)->unique();
            $table->string('product_name');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('product_category', 120)->nullable();
            $table->unsignedInteger('revision_number')->default(1);
            $table->date('revision_date')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('engineering_product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_product_id');
            $table->string('component_code', 100);
            $table->string('component_name');
            $table->decimal('quantity_per_product', 12, 4)->default(1);
            $table->string('component_type', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['engineering_product_id', 'component_code'], 'eng_product_component_code_unique');
            $table->foreign('engineering_product_id', 'eng_component_product_fk')
                ->references('id')
                ->on('engineering_products')
                ->cascadeOnDelete();
        });

        Schema::create('engineering_component_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_component_id');
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('height', 12, 3)->nullable();
            $table->string('uom', 20)->default('mm');
            $table->string('ply_type', 40)->nullable();
            $table->string('flute_type', 80)->nullable();
            $table->string('board_grade', 160)->nullable();
            $table->string('joint_type', 120)->nullable();
            $table->boolean('is_printed')->default(false);
            $table->unsignedInteger('printing_colors')->default(0);
            $table->unsignedInteger('bundle_quantity')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
            $table->unique('engineering_component_id', 'eng_spec_component_unique');
            $table->foreign('engineering_component_id', 'eng_spec_component_fk')
                ->references('id')
                ->on('engineering_product_components')
                ->cascadeOnDelete();
        });

        Schema::create('engineering_component_bom_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_component_id');
            $table->unsignedInteger('layer_sequence')->default(1);
            $table->string('paper_type');
            $table->unsignedInteger('gsm')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestamps();

            $table->index(['engineering_component_id', 'layer_sequence'], 'eng_component_bom_sequence_index');
            $table->foreign('engineering_component_id', 'eng_bom_component_fk')
                ->references('id')
                ->on('engineering_product_components')
                ->cascadeOnDelete();
        });

        Schema::create('engineering_component_routings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_component_id');
            $table->unsignedInteger('sequence_no')->default(1);
            $table->string('process_name', 160);
            $table->unsignedInteger('process_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('process_instructions')->nullable();
            $table->json('parameters')->nullable();
            $table->timestamps();

            $table->index(['engineering_component_id', 'sequence_no'], 'eng_component_routing_sequence_index');
            $table->foreign('engineering_component_id', 'eng_routing_component_fk')
                ->references('id')
                ->on('engineering_product_components')
                ->cascadeOnDelete();
        });

        Schema::create('engineering_product_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineering_product_id');
            $table->unsignedInteger('revision_number');
            $table->date('revision_date')->nullable();
            $table->text('change_notes')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['engineering_product_id', 'revision_number'], 'eng_product_revision_unique');
            $table->foreign('engineering_product_id', 'eng_revision_product_fk')
                ->references('id')
                ->on('engineering_products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineering_product_revisions');
        Schema::dropIfExists('engineering_component_routings');
        Schema::dropIfExists('engineering_component_bom_layers');
        Schema::dropIfExists('engineering_component_specifications');
        Schema::dropIfExists('engineering_product_components');
        Schema::dropIfExists('engineering_products');
    }
};
