<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reels', function (Blueprint $table) {
            if (!Schema::hasColumn('reels', 'current_location')) {
                $table->string('current_location', 30)->default('Warehouse')->after('status');
            }
        });

        Schema::create('reel_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->string('from_location', 30);
            $table->string('to_location', 30);
            $table->string('handled_by')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transfer_date', 'to_location']);
            $table->index(['reel_id', 'transfer_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reel_transfers');

        Schema::table('reels', function (Blueprint $table) {
            if (Schema::hasColumn('reels', 'current_location')) {
                $table->dropColumn('current_location');
            }
        });
    }
};
