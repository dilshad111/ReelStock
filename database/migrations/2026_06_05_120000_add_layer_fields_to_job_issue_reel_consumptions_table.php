<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_issue_reel_consumptions', function (Blueprint $table) {
            $table->string('layer_label')->nullable()->after('quality_name');
            $table->string('layer_type')->nullable()->after('layer_label');
            $table->decimal('layer_gsm', 10, 2)->nullable()->after('layer_type');
        });
    }

    public function down(): void
    {
        Schema::table('job_issue_reel_consumptions', function (Blueprint $table) {
            $table->dropColumn(['layer_label', 'layer_type', 'layer_gsm']);
        });
    }
};
