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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('address_name');
            $table->text('full_address');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_type'); // Suzuki, Shehzore, Mazda, 1x17 Container, 1x20 Container
            $table->foreignId('transporter_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('cartage_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_address_id')->constrained()->onDelete('cascade');
            $table->string('vehicle_type');
            $table->decimal('rate', 10, 2);
            $table->timestamps();
        });

        Schema::create('cartage_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained();
            $table->string('bill_to')->default('M/S QUALITY CARTONS (Pvt.) LTD.');
            $table->date('bill_date');
            $table->decimal('total_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('cartage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartage_bill_id')->constrained()->onDelete('cascade'); // This might need to be nullable if we save entries before finalizing bill, but requirement says "After saving, user can print the bill" and "Selections: selects Customer, Address, Transporter... dynamic table". Usually we save the bill and entries together.
            $table->date('entry_date');
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('shipping_address_id')->constrained();
            $table->string('vehicle_number');
            $table->string('dc_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cartage_entries');
        Schema::dropIfExists('cartage_bills');
        Schema::dropIfExists('cartage_rates');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('transporters');
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('customers');
    }
};
