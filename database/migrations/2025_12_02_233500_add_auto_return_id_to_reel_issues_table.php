<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->foreignId('auto_return_id')->nullable()->constrained('reel_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reel_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auto_return_id');
        });
    }
};
