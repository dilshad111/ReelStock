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
        Schema::create('rm_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('paper_quality_id')->nullable();
            $table->string('unit_type')->default('KG');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('opening_stock', 15, 2)->default(0);
            $table->decimal('min_stock_alert', 15, 2)->default(0);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('paper_quality_id')->references('id')->on('paper_qualities')->onDelete('set null');
        });

        Schema::create('rm_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->date('date');
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('rm_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rm_receipt_id');
            $table->unsignedBigInteger('rm_item_id');
            $table->decimal('quantity', 15, 2);
            $table->string('unit');
            $table->decimal('rate', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('rm_receipt_id')->references('id')->on('rm_receipts')->onDelete('cascade');
            $table->foreign('rm_item_id')->references('id')->on('rm_items');
        });

        Schema::create('rm_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->unsignedBigInteger('job_card_id')->nullable(); // Placeholder for Phase 2
            $table->date('date');
            $table->string('department')->nullable();
            $table->string('issued_to')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('rm_consumption_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rm_consumption_id');
            $table->unsignedBigInteger('rm_item_id');
            $table->decimal('quantity', 15, 2);
            $table->timestamps();

            $table->foreign('rm_consumption_id')->references('id')->on('rm_consumptions')->onDelete('cascade');
            $table->foreign('rm_item_id')->references('id')->on('rm_items');
        });

        Schema::create('rm_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rm_item_id');
            $table->string('transaction_type'); // receipt, consumption, adjustment, opening
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity_in', 15, 2)->default(0);
            $table->decimal('quantity_out', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2);
            $table->date('transaction_date');
            $table->timestamps();

            $table->foreign('rm_item_id')->references('id')->on('rm_items');
            $table->index(['rm_item_id', 'transaction_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rm_stock_ledger');
        Schema::dropIfExists('rm_consumption_items');
        Schema::dropIfExists('rm_consumptions');
        Schema::dropIfExists('rm_receipt_items');
        Schema::dropIfExists('rm_receipts');
        Schema::dropIfExists('rm_items');
    }
};
