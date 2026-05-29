<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_inspections', function (Blueprint $table) {
            $table->string('decision_type', 30)->default('lot_accept')->after('qc_status');
        });
    }

    public function down(): void
    {
        Schema::table('qc_inspections', function (Blueprint $table) {
            $table->dropColumn('decision_type');
        });
    }
};
