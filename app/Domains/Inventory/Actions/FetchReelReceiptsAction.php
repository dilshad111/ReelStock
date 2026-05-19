<?php

namespace App\Domains\Inventory\Actions;

use App\Models\ReelReceipt;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchReelReceiptsAction
{
    public function execute(array $filters, int $limit = 50): LengthAwarePaginator
    {
        $query = ReelReceipt::with(['reel.paperQuality', 'reel.supplier']);

        if (!empty($filters['reel_no'])) {
            $query->whereHas('reel', function ($q) use ($filters) {
                $q->where('reel_no', 'like', '%' . $filters['reel_no'] . '%');
            });
        }

        if (!empty($filters['supplier_id'])) {
            $query->whereHas('reel', function ($q) use ($filters) {
                $q->where('supplier_id', $filters['supplier_id']);
            });
        }

        if (!empty($filters['supplier'])) {
            $query->whereHas('reel.supplier', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['supplier'] . '%');
            });
        }

        if (!empty($filters['quality_id'])) {
            $query->whereHas('reel', function ($q) use ($filters) {
                $q->where('paper_quality_id', $filters['quality_id']);
            });
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('receiving_date', [$filters['date_from'], $filters['date_to']]);
        }

        $query->orderBy('id', 'desc');

        return $query->paginate($limit);
    }
}
