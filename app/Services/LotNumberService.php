<?php

namespace App\Services;

use App\Models\LotSequence;

class LotNumberService
{
    /**
     * Generate the next lot number using row-level locking.
     *
     * MUST be called inside a DB::transaction() to ensure
     * the number rolls back if the outer transaction fails.
     *
     * @return string e.g. "LOT-20260512-0001"
     */
    public static function generateNextNumber(): string
    {
        // Lock the sequence row — any concurrent call will WAIT here
        $sequence = LotSequence::lockForUpdate()->firstOrFail();

        $dateStr = date('Ymd');
        $lotNo = $sequence->prefix . '-' . $dateStr . '-' . str_pad($sequence->next_number, 4, '0', STR_PAD_LEFT);

        $sequence->next_number = $sequence->next_number + 1;
        $sequence->save();

        return $lotNo;
    }

    /**
     * Generate multiple sequential lot numbers in one locked operation.
     *
     * @param int $count Number of lot numbers to generate
     * @return array Array of lot number strings
     */
    public static function generateNextNumbers(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $sequence = LotSequence::lockForUpdate()->firstOrFail();

        $dateStr = date('Ymd');
        $numbers = [];
        $startNumber = $sequence->next_number;

        for ($i = 0; $i < $count; $i++) {
            $numbers[] = $sequence->prefix . '-' . $dateStr . '-' . str_pad($startNumber + $i, 4, '0', STR_PAD_LEFT);
        }

        $sequence->next_number = $startNumber + $count;
        $sequence->save();

        return $numbers;
    }
}
