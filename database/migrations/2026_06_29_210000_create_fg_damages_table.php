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
        Schema::create('fg_damages', function (Blueprint $table) {
            $table->id();
            $table->string('damage_number')->unique();
            $table->date('date');
            
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->onDelete('set null');
            $table->string('job_number')->nullable()->index();
            
            $table->decimal('quantity', 12, 2);
            $table->string('reason'); // Water Damage, Compression, Printing Defect, Pest Damage, Expired, Handling Damage, Other
            $table->text('remarks')->nullable();
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->string('status', 30)->default('posted'); // posted, reversed
            
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reversal_reason')->nullable();
            
            $table->timestamps();

            $table->index('customer_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fg_damages');
    }
};
