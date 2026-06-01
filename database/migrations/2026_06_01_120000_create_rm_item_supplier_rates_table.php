<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rm_item_supplier_rates')) {
            Schema::create('rm_item_supplier_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rm_item_id')->constrained('rm_items')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->decimal('rate', 12, 2);
                $table->date('effective_from')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('remarks', 255)->nullable();
                $table->timestamps();

                $table->unique(['rm_item_id', 'supplier_id', 'effective_from'], 'rm_item_supplier_rates_unique');
                $table->index(['rm_item_id', 'supplier_id', 'is_active'], 'rm_item_supplier_rates_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_item_supplier_rates');
    }
};
