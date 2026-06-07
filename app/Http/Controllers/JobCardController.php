<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\JobCardLayer;
use App\Models\JobCardPiece;
use App\Models\Product;
use App\Domains\Production\Actions\CreateJobCardAction;
use App\Domains\Production\Actions\GetProductionDashboardDataAction;
use App\Domains\Production\DTOs\JobCardDTO;
use App\Services\JobCardVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobCardController extends Controller
{
    public function nextNumber()
    {
        $prefix = 'QC-JC-';
        $last = JobCard::where('job_card_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $last) {
            return response()->json(['next_job_card_no' => $prefix . '110001']);
        }

        $number = (int) preg_replace('/\D/', '', substr((string) $last->job_card_no, strlen($prefix)));
        $next = $number > 0 ? $number + 1 : 110001;

        return response()->json(['next_job_card_no' => $prefix . str_pad($next, 6, '0', STR_PAD_LEFT)]);
    }

    public function dashboard(GetProductionDashboardDataAction $action)
    {
        return response()->json($action->execute());
    }

    public function index(Request $request)
    {
        $query = JobCard::with(['customer', 'product', 'steps', 'activeVersion']);

        if ($request->search) {
            $query->where('job_card_no', 'like', "%{$request->search}%");
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->carton_category) {
            $query->where('special_details->carton_category', $request->carton_category);
        }

        return $query->orderBy('id', 'desc')->paginate($request->per_page ?? 15);
    }

    public function show($id)
    {
        return JobCard::with([
            'customer', 
            'product', 
            'items.item', 
            'steps.logs.creator', 
            'pieces.layers',
            'layers',
            'versions.creator',
            'versions.changeLogs.modifier',
            'activeVersion'
        ])->findOrFail($id);
    }

    public function print($id)
    {
        $jobCard = JobCard::with(['customer', 'product', 'pieces.layers', 'layers'])->findOrFail($id);
        return view('print_job_card', compact('jobCard'));
    }

    public function store(Request $request, CreateJobCardAction $action)
    {
        try {
            $dto = JobCardDTO::fromRequest($request);
            $jobCard = $action->execute($dto);
            return response()->json($jobCard, 201);
        } catch (\Exception $e) {
            Log::error('Job Card Creation Failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function versionHistory($id, JobCardVersionService $versionService)
    {
        $jobCard = JobCard::with(['versions.creator', 'versions.changeLogs.modifier'])->findOrFail($id);
        $versionService->ensureInitialVersion($jobCard);

        return response()->json($jobCard->fresh(['versions.creator', 'versions.changeLogs.modifier'])->versions);
    }

    public function showVersion($id, $versionId, JobCardVersionService $versionService)
    {
        $jobCard = JobCard::findOrFail($id);
        $versionService->ensureInitialVersion($jobCard);

        return $jobCard->versions()
            ->with(['creator', 'changeLogs.modifier'])
            ->whereKey($versionId)
            ->firstOrFail();
    }

    public function compareVersions($id, Request $request, JobCardVersionService $versionService)
    {
        $jobCard = JobCard::findOrFail($id);
        $versionService->ensureInitialVersion($jobCard);

        $from = $jobCard->versions()->findOrFail($request->query('from'));
        $to = $jobCard->versions()->findOrFail($request->query('to'));

        return response()->json([
            'from' => $from,
            'to' => $to,
            'changes' => $versionService->diffSnapshots($from->snapshot_data, $to->snapshot_data),
        ]);
    }

    public function printVersion($id, $versionId, JobCardVersionService $versionService)
    {
        $jobCard = JobCard::findOrFail($id);
        $versionService->ensureInitialVersion($jobCard);
        $version = $jobCard->versions()->with('creator')->whereKey($versionId)->firstOrFail();

        return view('print_job_card_version', compact('jobCard', 'version'));
    }

    public function changeRequest(Request $request, $id, JobCardVersionService $versionService)
    {
        $request->validate([
            'change_reason' => ['required', 'string', 'max:1000'],
            'customer_id' => ['required', 'exists:customers,id'],
            'length_mm' => ['nullable', 'numeric'],
            'width_mm' => ['nullable', 'numeric'],
            'height_mm' => ['nullable', 'numeric'],
            'ups' => ['nullable', 'integer', 'min:1'],
            'pieces_count' => ['required', 'integer', 'min:1', 'max:5'],
            'layers' => ['array'],
            'pieces' => ['array'],
        ]);

        try {
            $jobCard = DB::transaction(function () use ($request, $id, $versionService) {
                $jobCard = JobCard::with(['customer', 'product', 'items.item', 'pieces.layers', 'layers', 'versions'])->findOrFail($id);
                $versionService->ensureInitialVersion($jobCard);
                $before = $versionService->snapshot($jobCard);

                $payload = $request->all();
                $specialDetails = $payload['special_details'] ?? [];

                $jobCard->update([
                    'customer_id' => $payload['customer_id'],
                    'planned_qty' => $payload['planned_qty'] ?? $jobCard->planned_qty,
                    'planned_date' => $payload['planned_date'] ?? $jobCard->planned_date,
                    'delivery_date' => $payload['delivery_date'] ?? $jobCard->delivery_date,
                    'specifications' => $payload['specifications'] ?? $jobCard->specifications,
                    'notes' => $payload['notes'] ?? $jobCard->notes,
                    'length_mm' => $payload['length_mm'] ?? null,
                    'width_mm' => $payload['width_mm'] ?? null,
                    'height_mm' => $payload['height_mm'] ?? null,
                    'uom' => $payload['uom'] ?? 'mm',
                    'deckle_size' => $payload['deckle_size'] ?? null,
                    'sheet_length' => $payload['sheet_length'] ?? null,
                    'ups' => $payload['ups'] ?? 1,
                    'carton_type' => $payload['carton_type'] ?? 'Carton',
                    'machine_name' => $payload['machine_name'] ?? null,
                    'target_speed' => $payload['target_speed'] ?? 0,
                    'printing_process' => $payload['printing_process'] ?? null,
                    'pasting_closure' => $payload['pasting_closure'] ?? null,
                    'printing_colors_count' => $payload['printing_colors_count'] ?? 0,
                    'pantone_colors' => $payload['pantone_colors'] ?? [],
                    'special_details' => $specialDetails,
                    'pieces_count' => $payload['pieces_count'] ?? 1,
                    'est_unit_weight' => $payload['est_unit_weight'] ?? 0,
                ]);

                $this->replaceItems($jobCard, $payload['items'] ?? []);
                $this->replaceLayersAndPieces($jobCard, $payload);

                $jobCard->load(['customer', 'product', 'items.item', 'pieces.layers', 'layers']);
                $versionService->createNextVersion($jobCard, $before, $payload['change_reason']);

                return $jobCard->fresh([
                    'customer',
                    'product',
                    'items.item',
                    'pieces.layers',
                    'layers',
                    'versions.creator',
                    'versions.changeLogs.modifier',
                    'activeVersion',
                ]);
            });

            return response()->json($jobCard);
        } catch (\Exception $e) {
            Log::error('Job Card Change Request Failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, $id, JobCardVersionService $versionService)
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'item_code' => ['nullable', 'string', 'max:255'],
            'length_mm' => ['nullable', 'numeric'],
            'width_mm' => ['nullable', 'numeric'],
            'height_mm' => ['nullable', 'numeric'],
            'ups' => ['nullable', 'integer', 'min:1'],
            'pieces_count' => ['required', 'integer', 'min:1', 'max:5'],
            'layers' => ['array'],
            'pieces' => ['array'],
        ]);

        try {
            $jobCard = DB::transaction(function () use ($request, $id, $versionService) {
                $jobCard = JobCard::with(['customer', 'product', 'items.item', 'pieces.layers', 'layers', 'activeVersion'])->findOrFail($id);
                $versionService->ensureInitialVersion($jobCard);

                $payload = $request->all();
                $specialDetails = $payload['special_details'] ?? [];
                $productId = $this->resolveProductId($payload, $jobCard);

                $jobCard->update([
                    'customer_id' => $payload['customer_id'],
                    'fg_product_id' => $productId,
                    'planned_qty' => $payload['planned_qty'] ?? $jobCard->planned_qty,
                    'planned_date' => $payload['planned_date'] ?? $jobCard->planned_date,
                    'delivery_date' => $payload['delivery_date'] ?? $jobCard->delivery_date,
                    'specifications' => $payload['specifications'] ?? $jobCard->specifications,
                    'notes' => $payload['notes'] ?? $jobCard->notes,
                    'length_mm' => $payload['length_mm'] ?? null,
                    'width_mm' => $payload['width_mm'] ?? null,
                    'height_mm' => $payload['height_mm'] ?? null,
                    'uom' => $payload['uom'] ?? 'mm',
                    'deckle_size' => $payload['deckle_size'] ?? null,
                    'sheet_length' => $payload['sheet_length'] ?? null,
                    'ups' => $payload['ups'] ?? 1,
                    'carton_type' => $payload['carton_type'] ?? 'Carton',
                    'machine_name' => $payload['machine_name'] ?? null,
                    'target_speed' => $payload['target_speed'] ?? 0,
                    'printing_process' => $payload['printing_process'] ?? null,
                    'pasting_closure' => $payload['pasting_closure'] ?? null,
                    'printing_colors_count' => $payload['printing_colors_count'] ?? 0,
                    'pantone_colors' => $payload['pantone_colors'] ?? [],
                    'special_details' => $specialDetails,
                    'pieces_count' => $payload['pieces_count'] ?? 1,
                    'est_unit_weight' => $payload['est_unit_weight'] ?? 0,
                ]);

                $this->replaceItems($jobCard, $payload['items'] ?? []);
                $this->replaceLayersAndPieces($jobCard, $payload);

                $jobCard->load(['customer', 'product', 'items.item', 'pieces.layers', 'layers', 'activeVersion']);
                if ($jobCard->activeVersion) {
                    $jobCard->activeVersion->update([
                        'snapshot_data' => $versionService->snapshot($jobCard),
                        'change_reason' => $jobCard->activeVersion->version_no === 1
                            ? 'Initial Version'
                            : $jobCard->activeVersion->change_reason,
                    ]);
                }

                return $jobCard->fresh([
                    'customer',
                    'product',
                    'items.item',
                    'pieces.layers',
                    'layers',
                    'versions.creator',
                    'versions.changeLogs.modifier',
                    'activeVersion',
                ]);
            });

            return response()->json($jobCard);
        } catch (\Exception $e) {
            Log::error('Job Card Update Failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Open,In-Progress,Completed,Cancelled']);
        $jobCard = JobCard::findOrFail($id);
        $jobCard->status = $request->status;
        $jobCard->save();

        return response()->json($jobCard);
    }

    public function destroy($id)
    {
        $jobCard = JobCard::withCount(['jobIssues', 'productionLogs'])->findOrFail($id);
        $fgReceiptsCount = DB::table('fg_receipts')->where('job_card_id', $jobCard->id)->count();

        if ($jobCard->job_issues_count || $jobCard->production_logs_count || $fgReceiptsCount) {
            return response()->json([
                'error' => 'This Job Card cannot be deleted because it is already used in production, job issue, or FG receipt records.'
            ], 422);
        }

        $jobCard->delete();

        return response()->json(['message' => 'Job Card deleted successfully.']);
    }

    public function activeJobCards()
    {
        return JobCard::with(['customer', 'product'])
            ->whereIn('status', ['Open', 'In-Progress'])
            ->orderBy('job_card_no')
            ->get();
    }

    private function replaceItems(JobCard $jobCard, array $items): void
    {
        $jobCard->items()->delete();
        foreach ($items as $item) {
            if (empty($item['rm_item_id'])) {
                continue;
            }
            JobCardItem::create([
                'job_card_id' => $jobCard->id,
                'rm_item_id' => $item['rm_item_id'],
                'required_qty' => $item['required_qty'] ?? 0,
                'unit' => $item['unit'] ?? 'KG',
            ]);
        }
    }

    private function resolveProductId(array $payload, JobCard $jobCard): int
    {
        $itemName = trim((string) ($payload['item_name'] ?? ''));
        if ($itemName === '') {
            throw new \InvalidArgumentException('Item name is required.');
        }

        $itemCode = trim((string) ($payload['item_code'] ?? ''));
        if ($itemCode === '') {
            $itemCode = $jobCard->product?->item_code ?: ('JCITEM-' . $payload['customer_id'] . '-' . str_pad((string) $jobCard->id, 4, '0', STR_PAD_LEFT));
        }

        $product = Product::firstOrCreate(
            [
                'customer_id' => $payload['customer_id'],
                'item_code' => $itemCode,
            ],
            [
                'item_name' => $itemName,
                'rate' => null,
                'opening_balance' => 0,
            ]
        );

        if ($product->item_name !== $itemName) {
            $product->update(['item_name' => $itemName]);
        }

        return $product->id;
    }

    private function replaceLayersAndPieces(JobCard $jobCard, array $payload): void
    {
        $jobCard->layers()->delete();
        $jobCard->pieces()->each(function (JobCardPiece $piece) {
            $piece->layers()->delete();
            $piece->delete();
        });

        if ((int) ($payload['pieces_count'] ?? 1) === 1) {
            foreach (($payload['layers'] ?? []) as $index => $layer) {
                JobCardLayer::create([
                    'job_card_id' => $jobCard->id,
                    'job_card_piece_id' => null,
                    'layer_type' => $layer['layer_type'] ?? '',
                    'paper_name' => $layer['paper_name'] ?? '',
                    'gsm' => (int) ($layer['gsm'] ?? 0),
                    'flute_profile' => $layer['flute_profile'] ?? 'Flat',
                    'sequence' => $index + 1,
                ]);
            }
            return;
        }

        foreach (($payload['pieces'] ?? []) as $pieceData) {
            $piece = JobCardPiece::create([
                'job_card_id' => $jobCard->id,
                'piece_name' => $pieceData['piece_name'] ?? 'Component',
                'length_mm' => $pieceData['length_mm'] ?? null,
                'width_mm' => $pieceData['width_mm'] ?? null,
                'height_mm' => $pieceData['height_mm'] ?? null,
                'deckle_size' => $pieceData['deckle_size'] ?? null,
                'sheet_length' => $pieceData['sheet_length'] ?? null,
                'ups' => $pieceData['ups'] ?? 1,
                'machine_name' => $pieceData['machine_name'] ?? null,
                'target_speed' => $pieceData['target_speed'] ?? 0,
                'est_unit_weight' => $pieceData['est_unit_weight'] ?? 0,
                'instructions' => $pieceData['instructions'] ?? null,
            ]);

            foreach (($pieceData['layers'] ?? []) as $index => $layer) {
                JobCardLayer::create([
                    'job_card_id' => null,
                    'job_card_piece_id' => $piece->id,
                    'layer_type' => $layer['layer_type'] ?? '',
                    'paper_name' => $layer['paper_name'] ?? '',
                    'gsm' => (int) ($layer['gsm'] ?? 0),
                    'flute_profile' => $layer['flute_profile'] ?? 'Flat',
                    'sequence' => $index + 1,
                ]);
            }
        }
    }
}
