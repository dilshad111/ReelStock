<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sample_submissions', function (Blueprint $table) {
            $table->string('sample_made_by')->nullable()->after('quantity');
            $table->string('joinery_technique')->nullable()->after('sample_made_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sample_submissions', function (Blueprint $table) {
            $table->dropColumn(['sample_made_by', 'joinery_technique']);
        });
    }
};
