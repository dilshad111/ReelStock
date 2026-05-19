<?php

namespace App\Domains\Inventory\Actions;

use App\Models\ReelReturn;
use Illuminate\Database\Eloquent\Collection;

class FetchReelReturnsAction
{
    public function execute(array $filters): Collection
    {
        $query = ReelReturn::with(['reel.paperQuality', 'reel.supplier', 'reel.receipts' => function ($q) {
            $q->orderByDesc('receiving_date');
        }, 'returnToSupplier']);

        if (!empty($filters['returned_to'])) {
            $query->where('returned_to', $filters['returned_to']);
        }

        return $query->orderByDesc('return_date')->orderByDesc('created_at')->get();
    }
}
