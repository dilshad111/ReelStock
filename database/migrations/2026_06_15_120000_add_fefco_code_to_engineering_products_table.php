<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engineering_products', function (Blueprint $table) {
            if (!Schema::hasColumn('engineering_products', 'fefco_code')) {
                $table->string('fefco_code', 20)->nullable()->after('product_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('engineering_products', function (Blueprint $table) {
            if (Schema::hasColumn('engineering_products', 'fefco_code')) {
                $table->dropColumn('fefco_code');
            }
        });
    }
};
