<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reel_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10)->default('RL');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        // Auto-seed from existing data
        $prefix = DB::table('settings')->where('key', 'reel_no_prefix')->value('value') ?? 'RL';
        $prefixLength = strlen($prefix);

        // Get next_number from settings (if it exists)
        $settingsNext = (int) DB::table('settings')
            ->where('key', 'reel_next_number')
            ->value('value') ?: 0;

        // Also get the numeric part of the most recently created reel (by ID)
        $latestReelNo = DB::table('reels')
            ->orderByDesc('id')
            ->value('reel_no');

        $latestNumber = 0;
        if ($latestReelNo && str_starts_with($latestReelNo, $prefix)) {
            $latestNumber = (int) substr($latestReelNo, $prefixLength);
        }

        // Use the higher of: settings next_number, or latest reel number + 1
        $nextNumber = max($settingsNext, $latestNumber + 1, 1);

        DB::table('reel_sequences')->insert([
            'prefix' => $prefix,
            'next_number' => $nextNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('reel_sequences');
    }
};
