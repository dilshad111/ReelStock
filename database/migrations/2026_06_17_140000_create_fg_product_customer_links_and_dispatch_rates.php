<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fg_product_customer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('customer_item_code', 100);
            $table->string('customer_item_name', 255);
            $table->decimal('customer_rate', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('Active');
            $table->timestamps();

            $table->unique(['product_id', 'customer_id', 'customer_item_code'], 'fg_product_customer_links_unique');
            $table->index(['product_id', 'customer_id', 'status'], 'fg_product_customer_links_lookup');
        });

        Schema::table('fg_dispatches', function (Blueprint $table) {
            if (!Schema::hasColumn('fg_dispatches', 'fg_product_customer_link_id')) {
                $table->foreignId('fg_product_customer_link_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('fg_product_customer_links')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('fg_dispatches', 'dispatch_item_code')) {
                $table->string('dispatch_item_code', 100)->nullable()->after('fg_product_customer_link_id');
            }
            if (!Schema::hasColumn('fg_dispatches', 'dispatch_item_name')) {
                $table->string('dispatch_item_name', 255)->nullable()->after('dispatch_item_code');
            }
            if (!Schema::hasColumn('fg_dispatches', 'dispatch_rate')) {
                $table->decimal('dispatch_rate', 12, 2)->default(0)->after('quantity_dispatched');
            }
            if (!Schema::hasColumn('fg_dispatches', 'dispatch_amount')) {
                $table->decimal('dispatch_amount', 14, 2)->default(0)->after('dispatch_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('fg_dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('fg_dispatches', 'fg_product_customer_link_id')) {
                $table->dropConstrainedForeignId('fg_product_customer_link_id');
            }
            foreach (['dispatch_item_code', 'dispatch_item_name', 'dispatch_rate', 'dispatch_amount'] as $column) {
                if (Schema::hasColumn('fg_dispatches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('fg_product_customer_links');
    }
};
