<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fg_receipts', function (Blueprint $table) {
            $table->foreignId('job_card_id')->nullable()->after('product_id')->constrained('job_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fg_receipts', function (Blueprint $table) {
            $table->dropForeign(['job_card_id']);
            $table->dropColumn('job_card_id');
        });
    }
};
