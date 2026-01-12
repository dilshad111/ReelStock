<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OldReelsReportController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        $sections = [
            [
                'title' => 'Reels available in inventory for more than 1 year but less than 2 years',
                'from' => $now->copy()->subYears(2),
                'to' => $now->copy()->subYears(1),
            ],
            [
                'title' => 'Reels available in inventory for more than 2 years but less than 3 years',
                'from' => $now->copy()->subYears(3),
                'to' => $now->copy()->subYears(2),
            ],
            [
                'title' => 'Reels available in inventory for more than 3 years but less than 5 years',
                'from' => $now->copy()->subYears(5),
                'to' => $now->copy()->subYears(3),
            ],
            [
                'title' => 'Reels available in inventory for more than 5 years (all reels)',
                'from' => null,
                'to' => $now->copy()->subYears(5),
            ],
        ];

        $results = [];

        foreach ($sections as $section) {
            $query = Reel::with(['paperQuality', 'supplier', 'receipts'])
                ->where('balance_weight', '>', 0)
                ->whereHas('receipts', function ($q) use ($section) {
                    if ($section['from']) {
                        $q->where('receiving_date', '>=', $section['from']->toDateString());
                    }
                    if ($section['to']) {
                        $q->where('receiving_date', '<', $section['to']->toDateString());
                    }
                });

            $reels = $query->get()->map(function ($reel) {
                $latestReceipt = $reel->receipts->sortByDesc('receiving_date')->first();
                $receivingDate = $latestReceipt ? $latestReceipt->receiving_date : null;
                $ratePerKg = $latestReceipt ? $latestReceipt->rate_per_kg : 0;
                $balanceWeight = $reel->balance_weight;
                $amount = $balanceWeight * $ratePerKg;
                
                $status = ($reel->balance_weight >= $reel->original_weight - 0.01) ? 'Complete' : 'Partial';

                return [
                    'id' => $reel->id,
                    'reel_no' => $reel->reel_no,
                    'receiving_date' => $receivingDate,
                    'supplier_name' => $reel->supplier ? $reel->supplier->name : 'N/A',
                    'quality' => $reel->paperQuality ? ($reel->paperQuality->quality . ' (' . $reel->paperQuality->gsm_range . ')') : 'N/A',
                    'balance_weight' => $balanceWeight,
                    'status' => $status,
                    'amount' => $amount,
                ];
            });

            $results[] = [
                'title' => $section['title'],
                'reels' => $reels,
                'subtotal_reels' => $reels->count(),
                'total_weight' => $reels->sum('balance_weight'),
                'total_amount' => $reels->sum('amount'),
            ];
        }

        return response()->json($results);
    }
}
