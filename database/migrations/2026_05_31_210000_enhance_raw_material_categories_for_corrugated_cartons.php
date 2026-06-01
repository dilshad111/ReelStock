<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('rm_categories')) {
            Schema::create('rm_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->enum('status', ['Active', 'Inactive'])->default('Active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rm_subcategories')) {
            Schema::create('rm_subcategories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rm_category_id')->constrained('rm_categories')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_system')->default(false);
                $table->enum('status', ['Active', 'Inactive'])->default('Active');
                $table->timestamps();

                $table->unique(['rm_category_id', 'name']);
                $table->unique(['rm_category_id', 'slug']);
            });
        }

        $now = now();
        $categories = [
            'Paper & Board' => 'Paper and board grades used by reels inventory and production jobs.',
            'Adhesives & Chemicals' => 'Starch, caustic, borax, and adhesive process chemicals.',
            'Inks & Coatings' => 'Printing inks, varnishes, and coating materials.',
            'Packaging Consumables' => 'Bundling, labeling, wrapping, and dispatch consumables.',
            'Production Consumables' => 'Consumables directly used on converting and finishing lines.',
            'Maintenance Consumables (MRO)' => 'Maintenance, repair, operations, and cleaning consumables.',
        ];

        foreach ($categories as $name => $description) {
            DB::table('rm_categories')->updateOrInsert(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => $description,
                    'is_system' => true,
                    'status' => 'Active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $subcategories = [
            'Adhesives & Chemicals' => [
                'Corn Starch',
                'Tapioca Starch',
                'Borax',
                'Caustic Soda',
                'Adhesive Additives',
                'Preservatives',
            ],
            'Inks & Coatings' => [
                'Water-Based Color Ink',
                'Flexographic Ink',
                'Varnish',
                'Coating Chemicals',
            ],
            'Packaging Consumables' => [
                'PP Straps',
                'Stretch Film',
                'Labels',
                'Stickers',
                'Corner Protectors',
            ],
            'Production Consumables' => [
                'Stitching Wire',
                'Printing Plates',
                'Cutting Dies',
                'Glue Consumables',
            ],
            'Maintenance Consumables (MRO)' => [
                'Lubricants',
                'Grease',
                'Machine Oils',
                'Cleaning Chemicals',
                'Maintenance Tools',
            ],
        ];

        foreach ($subcategories as $categoryName => $names) {
            $categoryId = DB::table('rm_categories')->where('name', $categoryName)->value('id');
            foreach ($names as $name) {
                DB::table('rm_subcategories')->updateOrInsert(
                    ['rm_category_id' => $categoryId, 'name' => $name],
                    [
                        'slug' => Str::slug($name),
                        'is_system' => true,
                        'status' => 'Active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        Schema::table('rm_items', function (Blueprint $table) {
            if (! Schema::hasColumn('rm_items', 'rm_category_id')) {
                $table->foreignId('rm_category_id')->nullable()->after('paper_quality_id')->constrained('rm_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('rm_items', 'rm_subcategory_id')) {
                $table->foreignId('rm_subcategory_id')->nullable()->after('rm_category_id')->constrained('rm_subcategories')->nullOnDelete();
            }
            if (! Schema::hasColumn('rm_items', 'material_type')) {
                $table->enum('material_type', ['Direct Material', 'Indirect Material', 'Consumable'])->default('Direct Material')->after('unit_type');
            }
            if (! Schema::hasColumn('rm_items', 'reorder_level')) {
                $table->decimal('reorder_level', 15, 2)->default(0)->after('min_stock_alert');
            }
            if (! Schema::hasColumn('rm_items', 'minimum_stock')) {
                $table->decimal('minimum_stock', 15, 2)->default(0)->after('reorder_level');
            }
            if (! Schema::hasColumn('rm_items', 'maximum_stock')) {
                $table->decimal('maximum_stock', 15, 2)->default(0)->after('minimum_stock');
            }
            if (! Schema::hasColumn('rm_items', 'preferred_supplier_id')) {
                $table->foreignId('preferred_supplier_id')->nullable()->after('maximum_stock')->constrained('suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('rm_items', 'gst_tax_code')) {
                $table->string('gst_tax_code', 50)->nullable()->after('preferred_supplier_id');
            }
        });

        try {
            Schema::table('rm_items', function (Blueprint $table) {
                $table->index(['rm_category_id', 'rm_subcategory_id'], 'rm_items_category_subcategory_index');
            });
        } catch (\Throwable $e) {
            // Index already exists on databases where this migration partially ran.
        }

        try {
            Schema::table('rm_items', function (Blueprint $table) {
                $table->index('preferred_supplier_id', 'rm_items_preferred_supplier_id_index');
            });
        } catch (\Throwable $e) {
            // Index already exists on databases where this migration partially ran.
        }

        $paperBoardId = DB::table('rm_categories')->where('name', 'Paper & Board')->value('id');
        $productionConsumablesId = DB::table('rm_categories')->where('name', 'Production Consumables')->value('id');

        DB::table('rm_items')
            ->whereNull('rm_category_id')
            ->whereNotNull('paper_quality_id')
            ->update([
                'rm_category_id' => $paperBoardId,
                'material_type' => 'Direct Material',
                'updated_at' => $now,
            ]);

        DB::table('rm_items')
            ->whereNull('rm_category_id')
            ->update([
                'rm_category_id' => $productionConsumablesId ?: $paperBoardId,
                'material_type' => 'Consumable',
                'updated_at' => $now,
            ]);

        if (Schema::hasTable('paper_qualities')) {
            $nextRmNumber = (int) DB::table('rm_items')
                ->where('code', 'like', 'RM-%')
                ->pluck('code')
                ->map(fn ($code) => (int) preg_replace('/\D/', '', $code))
                ->max();

            DB::table('paper_qualities')
                ->orderBy('id')
                ->get()
                ->each(function ($quality) use (&$nextRmNumber, $paperBoardId, $now) {
                    $exists = DB::table('rm_items')->where('paper_quality_id', $quality->id)->exists();

                    if ($exists) {
                        return;
                    }

                    do {
                        $nextRmNumber++;
                        $code = 'RM-' . str_pad($nextRmNumber, 4, '0', STR_PAD_LEFT);
                    } while (DB::table('rm_items')->where('code', $code)->exists());

                    $rmItemId = DB::table('rm_items')->insertGetId([
                        'name' => trim($quality->quality . ' ' . $quality->gsm_range),
                        'code' => $code,
                        'paper_quality_id' => $quality->id,
                        'rm_category_id' => $paperBoardId,
                        'rm_subcategory_id' => null,
                        'unit_type' => 'Kg',
                        'material_type' => 'Direct Material',
                        'cost_price' => 0,
                        'opening_stock' => 0,
                        'min_stock_alert' => 0,
                        'reorder_level' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 0,
                        'preferred_supplier_id' => null,
                        'gst_tax_code' => null,
                        'status' => 'Active',
                        'remarks' => 'Auto-created from existing Reels Inventory paper quality.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('rm_stock_ledger')->insert([
                        'rm_item_id' => $rmItemId,
                        'transaction_type' => 'opening',
                        'reference_id' => $rmItemId,
                        'quantity_in' => 0,
                        'quantity_out' => 0,
                        'balance_after' => 0,
                        'transaction_date' => $now->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }

        if (Schema::hasTable('unit_of_measures')) {
            foreach (['Kg', 'Ton', 'Roll', 'Liter', 'Piece', 'Meter', 'Sqr. Meter'] as $uom) {
                DB::table('unit_of_measures')->updateOrInsert(
                    ['name' => $uom],
                    ['status' => 'active', 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down()
    {
        Schema::table('rm_items', function (Blueprint $table) {
            $table->dropForeign(['rm_category_id']);
            $table->dropForeign(['rm_subcategory_id']);
            $table->dropForeign(['preferred_supplier_id']);
            $table->dropIndex(['rm_category_id', 'rm_subcategory_id']);
            $table->dropIndex(['preferred_supplier_id']);
            $table->dropColumn([
                'rm_category_id',
                'rm_subcategory_id',
                'material_type',
                'reorder_level',
                'minimum_stock',
                'maximum_stock',
                'preferred_supplier_id',
                'gst_tax_code',
            ]);
        });

        Schema::dropIfExists('rm_subcategories');
        Schema::dropIfExists('rm_categories');
    }
};
