<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FGReceipt;
use App\Models\FGStockLedger;
use App\Models\FluteFactor;
use App\Models\JobCard;
use App\Models\JobIssue;
use App\Models\JobIssueBreakdown;
use App\Models\JobIssueReelConsumption;
use App\Models\JobIssueStageEntry;
use App\Models\JobIssueWastage;
use App\Models\MachineOperator;
use App\Models\ProductionMachine;
use App\Models\Reel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobIssueController extends Controller
{
    private const STAGES = ['Corrugation', 'Printing', 'Die-Cutting', 'Bundling'];

    private const ROUTES = [
        'print_only' => 'Print Only',
        'die_cut_only' => 'Die-Cut Only',
        'print_die_cut' => 'Print → Die-Cut',
        'die_cut_print' => 'Die-Cut → Print',
        'corrugation_only' => 'Corrugation → Bundling',
    ];

    private const BREAKDOWN_REASONS = [
        'Paper Break',
        'Mechanical Failure',
        'Electrical Fault',
        'Setup Adjustment',
        'Material Issue',
        'Other',
    ];

    private const WASTAGE_TYPES = [
        'Paper Waste',
        'Sheet Waste',
        'Carton Waste',
        'Ink Waste',
        'Setup Waste',
        'Other',
    ];

    private const WASTAGE_REASONS = [
        'Cut Waste',
        'Paper Break',
        'Torn Paper',
        'Quality Rejection',
        'Setup Adjustment',
        'Other',
    ];

    public function lookups()
    {
        return response()->json([
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'machines' => ProductionMachine::with('department')->orderBy('machine_name')->get(),
            'operators' => MachineOperator::with('machine')->orderBy('operator_name')->get(),
            'production_routes' => collect(self::ROUTES)->map(fn ($label, $value) => compact('label', 'value'))->values(),
            'breakdown_reasons' => self::BREAKDOWN_REASONS,
            'wastage_types' => self::WASTAGE_TYPES,
            'wastage_reasons' => self::WASTAGE_REASONS,
            'stages' => self::STAGES,
            'flute_factors' => FluteFactor::where('is_active', true)->orderBy('flute_type')->get(),
        ]);
    }

    public function jobCardsByCustomer($customerId)
    {
        return response()->json(
            JobCard::with(['product', 'pieces.layers', 'layers'])
                ->where('customer_id', $customerId)
                ->orderBy('id', 'desc')
                ->get()
        );
    }

    public function index(Request $request)
    {
        $query = JobIssue::with(['customer', 'product', 'jobCard.product'])
            ->withSum('stageEntries as total_quantity_produced', 'quantity_produced')
            ->withSum('wastages as total_wastage_qty', 'quantity')
            ->withSum('reelConsumptions as total_reel_weight', 'consumed_weight');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                    ->orWhere('purchase_order_no', 'like', "%{$search}%")
                    ->orWhereHas('jobCard', fn ($jc) => $jc->where('job_card_no', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stage')) {
            $query->where('current_stage', $request->stage);
        }

        return response()->json($query->orderByDesc('id')->paginate($request->per_page ?? 15));
    }

    public function show(JobIssue $jobIssue)
    {
        return response()->json($this->loadIssue($jobIssue));
    }

    public function print(JobIssue $jobIssue)
    {
        $jobIssue = $this->loadIssue($jobIssue);

        return view('print_job_issue', compact('jobIssue'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'job_card_id' => ['required', 'exists:job_cards,id'],
            'purchase_order_no' => ['nullable', 'string', 'max:100'],
            'required_carton_qty' => ['required', 'numeric', 'min:1'],
            'issued_date' => ['nullable', 'date'],
            'production_route' => ['required', Rule::in(array_keys(self::ROUTES))],
        ]);

        $jobCard = JobCard::with('product')->findOrFail($data['job_card_id']);

        if ((int) $jobCard->customer_id !== (int) $data['customer_id']) {
            return response()->json(['message' => 'Selected Job Card does not belong to this customer.'], 422);
        }

        return DB::transaction(function () use ($data, $jobCard, $request) {
            $issue = JobIssue::create([
                'job_no' => $this->generateJobNo(),
                'job_card_id' => $jobCard->id,
                'customer_id' => $data['customer_id'],
                'product_id' => $jobCard->fg_product_id,
                'purchase_order_no' => $data['purchase_order_no'] ?? null,
                'required_carton_qty' => $data['required_carton_qty'],
                'issued_date' => $data['issued_date'] ?? now()->toDateString(),
                'production_route' => $data['production_route'],
                'current_stage' => 'Corrugation',
                'status' => 'Issued',
                'created_by' => optional($request->user())->id,
                'updated_by' => optional($request->user())->id,
            ]);

            if ($jobCard->status === 'Open') {
                $jobCard->update(['status' => 'In-Progress']);
            }

            return response()->json($this->loadIssue($issue), 201);
        });
    }

    public function start(Request $request, JobIssue $jobIssue)
    {
        if ($jobIssue->status === 'Completed') {
            return response()->json(['message' => 'Completed jobs cannot be restarted.'], 422);
        }

        $jobIssue->update([
            'status' => 'In-Progress',
            'current_stage' => $jobIssue->current_stage ?: 'Corrugation',
            'started_at' => $jobIssue->started_at ?: now(),
            'updated_by' => optional($request->user())->id,
        ]);

        $jobIssue->jobCard?->update(['status' => 'In-Progress']);

        return response()->json($this->loadIssue($jobIssue));
    }

    public function storeEntry(Request $request, JobIssue $jobIssue)
    {
        if ($jobIssue->status === 'Completed') {
            return response()->json(['message' => 'This job is already completed.'], 422);
        }

        $data = $request->validate([
            'stage' => ['required', Rule::in(self::STAGES)],
            'machine_id' => ['nullable', 'exists:production_machines,id'],
            'operator_id' => ['nullable', 'exists:machine_operators,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'quantity_produced' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'breakdowns' => ['nullable', 'array'],
            'breakdowns.*.breakdown_start_at' => ['required_with:breakdowns', 'date'],
            'breakdowns.*.breakdown_end_at' => ['nullable', 'date'],
            'breakdowns.*.reason' => ['required_with:breakdowns', 'string', 'max:100'],
            'breakdowns.*.remarks' => ['nullable', 'string'],
            'wastages' => ['nullable', 'array'],
            'wastages.*.wastage_type' => ['required_with:wastages', 'string', 'max:100'],
            'wastages.*.quantity' => ['required_with:wastages', 'numeric', 'min:0'],
            'wastages.*.reason' => ['nullable', 'string', 'max:100'],
            'wastages.*.remarks' => ['nullable', 'string'],
            'reels' => ['nullable', 'array'],
            'reels.*.reel_no' => ['required_with:reels', 'string', 'max:100'],
            'reels.*.layer_label' => ['nullable', 'string', 'max:100'],
            'reels.*.layer_type' => ['nullable', 'string', 'max:100'],
            'reels.*.layer_gsm' => ['nullable', 'numeric', 'min:0'],
            'reels.*.consumed_weight' => ['required_with:reels', 'numeric', 'min:0.01'],
        ]);

        if ($data['stage'] !== $jobIssue->current_stage) {
            return response()->json(['message' => "Current job stage is {$jobIssue->current_stage}. Complete it before recording {$data['stage']}."], 422);
        }

        return DB::transaction(function () use ($data, $request, $jobIssue) {
            $machine = !empty($data['machine_id']) ? ProductionMachine::find($data['machine_id']) : null;
            $operator = !empty($data['operator_id']) ? MachineOperator::find($data['operator_id']) : null;

            $entry = JobIssueStageEntry::create([
                'job_issue_id' => $jobIssue->id,
                'stage' => $data['stage'],
                'machine_id' => $data['machine_id'] ?? null,
                'machine_name' => $machine?->machine_name,
                'operator_id' => $data['operator_id'] ?? null,
                'operator_name' => $operator?->operator_name,
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'] ?? null,
                'quantity_produced' => $data['quantity_produced'] ?? 0,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => optional($request->user())->id,
            ]);

            foreach ($data['breakdowns'] ?? [] as $breakdown) {
                JobIssueBreakdown::create([
                    'job_issue_id' => $jobIssue->id,
                    'stage_entry_id' => $entry->id,
                    'stage' => $data['stage'],
                    'breakdown_start_at' => $breakdown['breakdown_start_at'],
                    'breakdown_end_at' => $breakdown['breakdown_end_at'] ?? null,
                    'reason' => $breakdown['reason'],
                    'remarks' => $breakdown['remarks'] ?? null,
                ]);
            }

            foreach ($data['wastages'] ?? [] as $wastage) {
                JobIssueWastage::create([
                    'job_issue_id' => $jobIssue->id,
                    'stage_entry_id' => $entry->id,
                    'stage' => $data['stage'],
                    'wastage_type' => $wastage['wastage_type'],
                    'quantity' => $wastage['quantity'],
                    'reason' => $wastage['reason'] ?? null,
                    'remarks' => $wastage['remarks'] ?? null,
                ]);
            }

            if ($data['stage'] === 'Corrugation') {
                foreach ($data['reels'] ?? [] as $reelRow) {
                    $reelNo = $this->normalizeReelNo($reelRow['reel_no']);
                    $reel = Reel::with('paperQuality')->where('reel_no', $reelNo)->first();

                    JobIssueReelConsumption::create([
                        'job_issue_id' => $jobIssue->id,
                        'stage_entry_id' => $entry->id,
                        'reel_id' => $reel?->id,
                        'reel_no' => $reelNo,
                        'paper_quality_id' => $reel?->paper_quality_id,
                        'quality_name' => $reel?->paperQuality
                            ? trim($reel->paperQuality->quality . ' ' . $reel->paperQuality->gsm_range)
                            : ($reelRow['quality_name'] ?? null),
                        'layer_label' => $reelRow['layer_label'] ?? null,
                        'layer_type' => $reelRow['layer_type'] ?? null,
                        'layer_gsm' => $reelRow['layer_gsm'] ?? null,
                        'consumed_weight' => $reelRow['consumed_weight'],
                    ]);
                }
            }

            $jobIssue->update([
                'status' => 'In-Progress',
                'updated_by' => optional($request->user())->id,
            ]);

            return response()->json($entry->load(['breakdowns', 'wastages', 'reelConsumptions']), 201);
        });
    }

    public function completeStage(Request $request, JobIssue $jobIssue)
    {
        $request->validate([
            'stage' => ['required', Rule::in(self::STAGES)],
        ]);

        if ($jobIssue->status === 'Completed') {
            return response()->json(['message' => 'This job is already completed.'], 422);
        }

        if ($request->stage !== $jobIssue->current_stage) {
            return response()->json(['message' => "Current job stage is {$jobIssue->current_stage}."], 422);
        }

        if ($jobIssue->current_stage === 'Bundling') {
            return response()->json(['message' => 'Use Complete Job after bundling and finishing verification.'], 422);
        }

        $nextStage = $this->nextStage($jobIssue->current_stage, $jobIssue->production_route);

        $jobIssue->update([
            'current_stage' => $nextStage,
            'status' => $nextStage,
            'updated_by' => optional($request->user())->id,
        ]);

        return response()->json($this->loadIssue($jobIssue));
    }

    public function completeJob(Request $request, JobIssue $jobIssue)
    {
        $data = $request->validate([
            'final_finished_qty' => ['required', 'numeric', 'min:0.01'],
            'rejected_cartons_qty' => ['nullable', 'numeric', 'min:0'],
            'final_wastage_reason' => ['nullable', 'string', 'max:255'],
            'completion_remarks' => ['nullable', 'string'],
        ]);

        if ($jobIssue->status === 'Completed') {
            return response()->json(['message' => 'This job is already completed.'], 422);
        }

        return DB::transaction(function () use ($request, $jobIssue, $data) {
            $jobIssue->load(['product', 'customer']);

            $receipt = FGReceipt::create([
                'date' => now()->toDateString(),
                'customer_id' => $jobIssue->customer_id,
                'product_id' => $jobIssue->product_id,
                'warehouse_id' => 1,
                'job_card_id' => $jobIssue->job_card_id,
                'job_number' => $jobIssue->job_no,
                'production_date' => now()->toDateString(),
                'quantity_produced' => $data['final_finished_qty'],
                'carton_price' => $jobIssue->product?->rate,
                'wastage' => $data['rejected_cartons_qty'] ?? 0,
                'remarks' => $data['completion_remarks'] ?? 'Auto receipt from Job Issue completion',
                'created_by' => optional($request->user())->id ?? 1,
            ]);

            FGStockLedger::recalculateForProduct($jobIssue->product_id);

            $jobIssue->update([
                'current_stage' => 'Completed',
                'status' => 'Completed',
                'final_finished_qty' => $data['final_finished_qty'],
                'rejected_cartons_qty' => $data['rejected_cartons_qty'] ?? 0,
                'final_wastage_reason' => $data['final_wastage_reason'] ?? null,
                'completion_remarks' => $data['completion_remarks'] ?? null,
                'completed_at' => now(),
                'updated_by' => optional($request->user())->id,
            ]);

            $jobIssue->jobCard?->update(['status' => 'Completed']);

            return response()->json($this->loadIssue($jobIssue));
        });
    }

    public function report(Request $request)
    {
        $query = JobIssue::with([
            'customer',
            'product',
            'jobCard.layers',
            'stageEntries',
            'breakdowns',
            'wastages',
            'reelConsumptions',
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('issued_date', [$request->date_from, $request->date_to]);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $issues = $query->orderByDesc('id')->get();
        $rows = $issues->map(fn (JobIssue $issue) => $this->reportRow($issue))->values();

        return response()->json([
            'summary' => [
                'total_jobs' => $issues->count(),
                'completed_jobs' => $issues->where('status', 'Completed')->count(),
                'active_jobs' => $issues->where('status', '!=', 'Completed')->count(),
                'sheets_produced' => round($rows->sum('sheets_produced'), 2),
                'cartons_produced' => round($rows->sum('cartons_produced'), 2),
                'total_downtime_minutes' => round($rows->sum('downtime_minutes'), 2),
                'total_wastage' => round($rows->sum('wastage_qty'), 2),
                'reel_weight_consumed' => round($rows->sum('reel_weight_consumed'), 2),
                'estimated_paper_consumed' => round($rows->sum('estimated_paper_consumed'), 2),
                'paper_consumption_variance' => round($rows->sum('paper_consumption_variance'), 2),
            ],
            'jobs' => $rows,
            'wastage_by_reason' => $this->sumBy($issues->flatMap->wastages, 'reason', 'quantity'),
            'reel_quality_usage' => $this->sumBy($issues->flatMap->reelConsumptions, 'quality_name', 'consumed_weight'),
            'production_by_operator' => $this->sumBy($issues->flatMap->stageEntries, 'operator_name', 'quantity_produced'),
            'production_by_machine' => $this->sumBy($issues->flatMap->stageEntries, 'machine_name', 'quantity_produced'),
        ]);
    }

    private function loadIssue(JobIssue $issue): JobIssue
    {
        $issue->load([
            'customer',
            'product',
            'jobCard.product',
            'jobCard.pieces.layers',
            'jobCard.layers',
            'stageEntries.breakdowns',
            'stageEntries.wastages',
            'stageEntries.reelConsumptions',
            'breakdowns',
            'wastages',
            'reelConsumptions',
            'creator',
        ]);

        $issue->setAttribute('paper_consumption_comparison', $this->paperConsumptionComparison($issue));

        return $issue;
    }

    private function generateJobNo(): string
    {
        $prefix = 'QC-JOB-';
        $last = JobIssue::where('job_no', 'like', $prefix . '%')->orderByDesc('id')->first();
        $next = 1001;

        if ($last && preg_match('/(\d+)$/', $last->job_no, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . $next;
    }

    private function normalizeReelNo(?string $reelNo): string
    {
        $value = strtoupper(trim((string) $reelNo));

        if ($value !== '' && !str_starts_with($value, 'RL')) {
            $value = 'RL' . $value;
        }

        return $value;
    }

    private function nextStage(string $stage, string $route): string
    {
        if ($stage === 'Corrugation') {
            return match ($route) {
                'print_only', 'print_die_cut' => 'Printing',
                'die_cut_only', 'die_cut_print' => 'Die-Cutting',
                default => 'Bundling',
            };
        }

        if ($stage === 'Printing') {
            return $route === 'print_die_cut' ? 'Die-Cutting' : 'Bundling';
        }

        if ($stage === 'Die-Cutting') {
            return $route === 'die_cut_print' ? 'Printing' : 'Bundling';
        }

        return 'Bundling';
    }

    private function reportRow(JobIssue $issue): array
    {
        $duration = $issue->stageEntries->sum(function ($entry) {
            if (!$entry->end_at) {
                return 0;
            }

            return Carbon::parse($entry->start_at)->diffInMinutes(Carbon::parse($entry->end_at));
        });

        $downtime = $issue->breakdowns->sum(function ($row) {
            if (!$row->breakdown_end_at) {
                return 0;
            }

            return Carbon::parse($row->breakdown_start_at)->diffInMinutes(Carbon::parse($row->breakdown_end_at));
        });

        $sheets = $issue->stageEntries->where('stage', 'Corrugation')->sum('quantity_produced');
        $cartons = $issue->final_finished_qty ?: $issue->stageEntries->where('stage', 'Bundling')->sum('quantity_produced');
        $productionMinutes = max($duration - $downtime, 0);
        $speed = $productionMinutes > 0 ? $issue->stageEntries->sum('quantity_produced') / $productionMinutes : 0;
        $comparison = $this->paperConsumptionComparison($issue, (float) $sheets);

        return [
            'job_no' => $issue->job_no,
            'customer' => $issue->customer?->name,
            'product' => $issue->product?->item_name,
            'status' => $issue->status,
            'current_stage' => $issue->current_stage,
            'required_carton_qty' => $issue->required_carton_qty,
            'sheets_produced' => round($sheets, 2),
            'cartons_produced' => round($cartons, 2),
            'duration_minutes' => round($duration, 2),
            'downtime_minutes' => round($downtime, 2),
            'production_minutes' => round($productionMinutes, 2),
            'machine_speed' => round($speed, 2),
            'efficiency_percent' => $duration > 0 ? round(($productionMinutes / $duration) * 100, 2) : 0,
            'wastage_qty' => round($issue->wastages->sum('quantity'), 2),
            'reel_weight_consumed' => round($issue->reelConsumptions->sum('consumed_weight'), 2),
            'estimated_paper_consumed' => $comparison['totals']['estimated_kg'],
            'paper_consumption_variance' => $comparison['totals']['variance_kg'],
            'paper_variance_percent' => $comparison['totals']['variance_percent'],
            'material_efficiency_percent' => $comparison['totals']['efficiency_percent'],
            'consumption_status' => $comparison['totals']['status'],
        ];
    }

    private function paperConsumptionComparison(JobIssue $issue, ?float $sheetsProduced = null): array
    {
        $jobCard = $issue->jobCard;
        $layers = $jobCard?->layers ?? collect();
        $sheets = $sheetsProduced ?? (float) $issue->stageEntries->where('stage', 'Corrugation')->sum('quantity_produced');
        $areaM2 = $this->sheetAreaM2($jobCard);
        $factorMap = $this->fluteFactorMap();

        $actualByLayer = $issue->reelConsumptions
            ->groupBy(fn ($row) => $row->layer_label ?: 'Unassigned')
            ->map(fn ($rows) => round($rows->sum('consumed_weight'), 2));

        $rows = $layers->map(function ($layer, $index) use ($layers, $sheets, $areaM2, $factorMap, $actualByLayer) {
            $label = $this->productionLayerLabel($layer, $index, $layers);
            $factor = $this->factorForLayer($layer, $factorMap);
            $gsm = (float) ($layer->gsm ?? 0);
            $estimated = round(($sheets * $areaM2 * $gsm * $factor) / 1000, 2);
            $actual = (float) ($actualByLayer[$label] ?? 0);
            $variance = round($actual - $estimated, 2);
            $variancePercent = $estimated > 0 ? round(($variance / $estimated) * 100, 2) : 0;

            return [
                'layer' => $label,
                'paper_name' => $layer->paper_name,
                'gsm' => $gsm,
                'flute_profile' => $layer->flute_profile,
                'flute_factor' => $factor,
                'estimated_kg' => $estimated,
                'actual_kg' => round($actual, 2),
                'variance_kg' => $variance,
                'variance_percent' => $variancePercent,
                'status' => $variance > 0 ? 'Over' : ($variance < 0 ? 'Under' : 'On Target'),
            ];
        })->values();

        $knownLayers = $rows->pluck('layer')->all();
        $unassignedRows = $actualByLayer
            ->filter(fn ($value, $layer) => !in_array($layer, $knownLayers, true))
            ->map(fn ($actual, $layer) => [
                'layer' => $layer,
                'paper_name' => null,
                'gsm' => null,
                'flute_profile' => null,
                'flute_factor' => null,
                'estimated_kg' => 0,
                'actual_kg' => round((float) $actual, 2),
                'variance_kg' => round((float) $actual, 2),
                'variance_percent' => 0,
                'status' => 'Over',
            ])
            ->values();

        $rows = $rows->concat($unassignedRows)->values();
        $estimatedTotal = round($rows->sum('estimated_kg'), 2);
        $actualTotal = round($rows->sum('actual_kg'), 2);
        $varianceTotal = round($actualTotal - $estimatedTotal, 2);
        $variancePercent = $estimatedTotal > 0 ? round(($varianceTotal / $estimatedTotal) * 100, 2) : 0;

        return [
            'sheets_produced' => round($sheets, 2),
            'sheet_area_m2' => round($areaM2, 6),
            'rows' => $rows,
            'totals' => [
                'estimated_kg' => $estimatedTotal,
                'actual_kg' => $actualTotal,
                'variance_kg' => $varianceTotal,
                'variance_percent' => $variancePercent,
                'efficiency_percent' => $actualTotal > 0 ? round(($estimatedTotal / $actualTotal) * 100, 2) : 0,
                'status' => $varianceTotal > 0 ? 'Over Consumption' : ($varianceTotal < 0 ? 'Under Consumption' : 'On Target'),
            ],
        ];
    }

    private function sheetAreaM2(?JobCard $jobCard): float
    {
        if (!$jobCard) {
            return 0;
        }

        $deckleIn = (float) ($jobCard->deckle_size ?? 0);
        $sheetLengthIn = (float) ($jobCard->sheet_length ?? 0);

        return max($deckleIn, 0) * max($sheetLengthIn, 0) * 0.00064516;
    }

    private function fluteFactorMap(): array
    {
        return FluteFactor::where('is_active', true)
            ->pluck('factor', 'flute_type')
            ->mapWithKeys(fn ($factor, $type) => [strtoupper((string) $type) => (float) $factor])
            ->all();
    }

    private function factorForLayer($layer, array $factorMap): float
    {
        $profile = strtoupper((string) ($layer->flute_profile ?: 'Flat'));
        $isFlute = str_contains(strtolower((string) $layer->layer_type), 'flute');

        if (!$isFlute) {
            return (float) ($factorMap['FLAT'] ?? 1);
        }

        return (float) ($factorMap[$profile] ?? 1);
    }

    private function productionLayerLabel($layer, int $index, $layers): string
    {
        $type = strtolower((string) $layer->layer_type);
        $middleNumber = collect($layers)
            ->take($index + 1)
            ->filter(fn ($item) => str_contains(strtolower((string) $item->layer_type), 'middle'))
            ->count();

        if (str_contains($type, 'top') || str_contains($type, 'outer')) {
            return 'Outer Paper';
        }

        if (str_contains($type, 'inner') || str_contains($type, 'bottom')) {
            return 'Inner Paper';
        }

        if (str_contains($type, 'middle')) {
            return $middleNumber > 1 ? "Middle Paper {$middleNumber}" : 'Middle Paper';
        }

        if (str_contains($type, 'flute')) {
            $profile = trim((string) $layer->flute_profile);
            return $profile && $profile !== 'Flat' ? "Flute-{$profile}" : 'Flute';
        }

        return $layer->layer_type ?: 'Layer ' . ($index + 1);
    }

    private function sumBy($collection, string $groupKey, string $sumKey)
    {
        return $collection
            ->filter(fn ($row) => filled($row->{$groupKey}))
            ->groupBy($groupKey)
            ->map(fn ($rows, $key) => [
                'name' => $key,
                'value' => round($rows->sum($sumKey), 2),
            ])
            ->values();
    }
}
