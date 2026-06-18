<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'dispatch_policy')) {
                $table->string('dispatch_policy', 30)
                    ->default('customer_restricted')
                    ->after('opening_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'dispatch_policy')) {
                $table->dropColumn('dispatch_policy');
            }
        });
    }
};
