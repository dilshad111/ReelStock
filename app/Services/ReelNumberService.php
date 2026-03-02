<?php

namespace App\Services;

use App\Models\ReelSequence;
use Illuminate\Support\Facades\DB;

class ReelNumberService
{
    /**
     * Generate the next reel number using row-level locking.
     *
     * MUST be called inside a DB::transaction() to ensure
     * the number rolls back if the outer transaction fails.
     *
     * @return string e.g. "RL112178"
     */
    public static function generateNextNumber(): string
    {
        // Lock the sequence row — any concurrent call will WAIT here
        $sequence = ReelSequence::lockForUpdate()->firstOrFail();

        $reelNo = $sequence->prefix . $sequence->next_number;

        $sequence->next_number = $sequence->next_number + 1;
        $sequence->save();

        return $reelNo;
    }

    /**
     * Generate multiple sequential reel numbers in one locked operation.
     *
     * MUST be called inside a DB::transaction() to ensure
     * the numbers roll back if the outer transaction fails.
     *
     * @param int $count Number of reel numbers to generate
     * @return array Array of reel number strings
     */
    public static function generateNextNumbers(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        // Lock the sequence row — any concurrent call will WAIT here
        $sequence = ReelSequence::lockForUpdate()->firstOrFail();

        $numbers = [];
        $startNumber = $sequence->next_number;

        for ($i = 0; $i < $count; $i++) {
            $numbers[] = $sequence->prefix . ($startNumber + $i);
        }

        $sequence->next_number = $startNumber + $count;
        $sequence->save();

        return $numbers;
    }
}
