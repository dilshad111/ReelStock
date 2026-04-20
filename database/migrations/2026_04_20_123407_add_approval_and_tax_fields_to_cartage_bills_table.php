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
        Schema::table('cartage_bills', function (Blueprint $table) {
            $table->string('status')->default('Pending')->after('total_amount');
            $table->string('tax_type')->nullable()->after('status');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('tax_type');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_percentage');
            $table->decimal('net_amount', 12, 2)->nullable()->after('tax_amount');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('net_amount');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down()
    {
        Schema::table('cartage_bills', function (Blueprint $table) {
            $table->dropColumn(['status', 'tax_type', 'tax_percentage', 'tax_amount', 'net_amount', 'approved_by', 'approved_at']);
        });
    }
};
