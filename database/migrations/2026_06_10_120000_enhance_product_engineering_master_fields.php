<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engineering_products', function (Blueprint $table) {
            if (!Schema::hasColumn('engineering_products', 'product_number')) {
                $table->string('product_number', 40)->nullable()->after('id');
            }
            if (!Schema::hasColumn('engineering_products', 'product_created_date')) {
                $table->date('product_created_date')->nullable()->after('product_number');
            }
        });

        DB::table('engineering_products')
            ->whereNull('product_number')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($product) {
                DB::table('engineering_products')
                    ->where('id', $product->id)
                    ->update([
                        'product_number' => 'QC-PM-' . str_pad((string) (110000 + (int) $product->id), 6, '0', STR_PAD_LEFT),
                        'product_created_date' => $product->created_at
                            ? substr((string) $product->created_at, 0, 10)
                            : now()->toDateString(),
                    ]);
            });

        Schema::table('engineering_products', function (Blueprint $table) {
            try {
                $table->unique('product_number', 'engineering_products_product_number_unique');
            } catch (Throwable $e) {
                // Index may already exist in manually migrated environments.
            }
        });

        Schema::table('engineering_component_bom_layers', function (Blueprint $table) {
            if (!Schema::hasColumn('engineering_component_bom_layers', 'layer_label')) {
                $table->string('layer_label', 120)->nullable()->after('layer_sequence');
            }
            if (!Schema::hasColumn('engineering_component_bom_layers', 'paper_quality_id')) {
                $table->foreignId('paper_quality_id')
                    ->nullable()
                    ->after('paper_type')
                    ->constrained('paper_qualities')
                    ->nullOnDelete();
            }
        });

        DB::table('engineering_component_bom_layers')
            ->whereNull('layer_label')
            ->update(['layer_label' => DB::raw('paper_type')]);

        Schema::table('engineering_component_specifications', function (Blueprint $table) {
            if (!Schema::hasColumn('engineering_component_specifications', 'printing_color_codes')) {
                $table->json('printing_color_codes')->nullable()->after('printing_colors');
            }
        });
    }

    public function down(): void
    {
        Schema::table('engineering_component_specifications', function (Blueprint $table) {
            if (Schema::hasColumn('engineering_component_specifications', 'printing_color_codes')) {
                $table->dropColumn('printing_color_codes');
            }
        });

        Schema::table('engineering_component_bom_layers', function (Blueprint $table) {
            if (Schema::hasColumn('engineering_component_bom_layers', 'paper_quality_id')) {
                $table->dropConstrainedForeignId('paper_quality_id');
            }
            if (Schema::hasColumn('engineering_component_bom_layers', 'layer_label')) {
                $table->dropColumn('layer_label');
            }
        });

        Schema::table('engineering_products', function (Blueprint $table) {
            try {
                $table->dropUnique('engineering_products_product_number_unique');
            } catch (Throwable $e) {
                // Index may not exist in partially migrated environments.
            }
            if (Schema::hasColumn('engineering_products', 'product_created_date')) {
                $table->dropColumn('product_created_date');
            }
            if (Schema::hasColumn('engineering_products', 'product_number')) {
                $table->dropColumn('product_number');
            }
        });
    }
};
