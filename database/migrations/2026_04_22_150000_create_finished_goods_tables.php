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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('item_code');
            $table->string('item_name');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'item_code']);
            $table->index('customer_id');
        });

        Schema::create('fg_receipts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('job_number')->index();
            $table->date('production_date');
            $table->decimal('quantity_produced', 12, 2);
            $table->decimal('wastage', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('product_id');
        });

        Schema::create('fg_dispatches', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('job_number')->nullable()->index();
            $table->string('dc_number');
            $table->decimal('quantity_dispatched', 12, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('product_id');
        });

        Schema::create('fg_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type', 30);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('job_number')->nullable();
            $table->decimal('quantity_in', 12, 2)->default(0);
            $table->decimal('quantity_out', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->date('transaction_date');
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_id');
            $table->index('customer_id');
            $table->index('job_number');
            $table->index('transaction_type');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fg_stock_ledger');
        Schema::dropIfExists('fg_dispatches');
        Schema::dropIfExists('fg_receipts');
        Schema::dropIfExists('products');
    }
};
