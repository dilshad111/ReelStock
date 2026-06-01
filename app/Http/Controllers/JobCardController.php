<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Domains\Production\Actions\CreateJobCardAction;
use App\Domains\Production\Actions\RecordProductionAction;
use App\Domains\Production\Actions\GetProductionDashboardDataAction;
use App\Domains\Production\DTOs\JobCardDTO;
use Illuminate\Http\Request;
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
        $query = JobCard::with(['customer', 'product', 'steps']);

        if ($request->search) {
            $query->where('job_card_no', 'like', "%{$request->search}%");
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
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
            'productionLogs.creator',
            'pieces.layers',
            'layers'
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

    public function recordProduction(Request $request, RecordProductionAction $action)
    {
        $request->validate([
            'job_card_id' => 'required|exists:job_cards,id',
            'job_card_step_id' => 'required|exists:job_card_steps,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0',
        ]);

        try {
            $log = $action->execute($request->all());
            return response()->json($log, 201);
        } catch (\Exception $e) {
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

    public function activeJobCards()
    {
        return JobCard::with(['customer', 'product'])
            ->whereIn('status', ['Open', 'In-Progress'])
            ->orderBy('job_card_no')
            ->get();
    }
}
