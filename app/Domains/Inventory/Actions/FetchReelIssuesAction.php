<?php

namespace App\Domains\Inventory\Actions;

use App\Models\ReelIssue;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchReelIssuesAction
{
    public function execute(array $filters, int $limit = 50): LengthAwarePaginator
    {
        $query = ReelIssue::with('reel.paperQuality');

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('reel', function ($q) use ($searchTerm) {
                $q->where('reel_no', 'like', "%{$searchTerm}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($limit);
    }
}
