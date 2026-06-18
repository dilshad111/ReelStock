<?php

namespace App\Http\Controllers;

use App\Models\Reel;
use App\Models\ReelTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReelTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = ReelTransfer::with(['reel.paperQuality', 'reel.supplier', 'creator'])
            ->latest('transfer_date')
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('reel', function ($q) use ($search) {
                $q->where('reel_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_location', $request->location)
                  ->orWhere('to_location', $request->location);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        return response()->json($query->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reel_no' => ['required', 'string', 'max:50'],
            'transfer_date' => ['required', 'date'],
            'to_location' => ['required', Rule::in($this->locations())],
            'handled_by' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = DB::transaction(function () use ($data, $request) {
                $reelNo = $this->normalizeReelNo($data['reel_no']);
                $reel = Reel::where('reel_no', $reelNo)->lockForUpdate()->firstOrFail();

                $fromLocation = $reel->current_location ?: ReelTransfer::LOCATION_WAREHOUSE;
                $toLocation = $data['to_location'];

                if ($fromLocation === $toLocation) {
                    abort(422, "This reel is already in {$toLocation}.");
                }

                if ($reel->status === 'returned_to_supplier') {
                    abort(422, 'This reel has already been returned to supplier.');
                }

                if ((float) ($reel->balance_weight ?? 0) <= 0) {
                    abort(422, 'Fully consumed reels cannot be transferred.');
                }

                $transfer = ReelTransfer::create([
                    'reel_id' => $reel->id,
                    'transfer_date' => $data['transfer_date'],
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'handled_by' => $data['handled_by'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => $request->user()?->id,
                ]);

                $reel->current_location = $toLocation;
                $reel->save();

                return $transfer->load(['reel.paperQuality', 'reel.supplier', 'creator']);
            });

            return $this->success($transfer, 'Reel location transferred successfully.', 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Reel not found.', 404);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function fetchReel($reelNo)
    {
        $reel = Reel::with(['paperQuality', 'supplier', 'transfers' => function ($query) {
            $query->latest('transfer_date')->latest('id')->limit(8);
        }])->where('reel_no', $this->normalizeReelNo($reelNo))->first();

        if (!$reel) {
            return $this->error('Reel not found.', 404);
        }

        return $this->success($reel);
    }

    private function locations(): array
    {
        return [
            ReelTransfer::LOCATION_FACTORY,
            ReelTransfer::LOCATION_WAREHOUSE,
        ];
    }

    private function normalizeReelNo(string $value): string
    {
        $reelNo = strtoupper(preg_replace('/\s+/', '', trim($value)));

        if ($reelNo === '') {
            return '';
        }

        return str_starts_with($reelNo, 'RL') ? $reelNo : 'RL' . $reelNo;
    }
}
