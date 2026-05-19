<?php

namespace App\Domains\Production\Actions;

use App\Models\JobCard;
use App\Models\JobCardStep;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RecordProductionAction
{
    public function execute(array $data): ProductionLog
    {
        return DB::transaction(function () use ($data) {
            $step = JobCardStep::findOrFail($data['job_card_step_id']);
            $jobCard = JobCard::findOrFail($data['job_card_id']);

            // 1. Create Log
            $log = ProductionLog::create([
                'job_card_id' => $jobCard->id,
                'job_card_step_id' => $step->id,
                'date' => $data['date'],
                'shift' => $data['shift'] ?? null,
                'machine_no' => $data['machine_no'] ?? null,
                'quantity' => $data['quantity'],
                'wastage' => $data['wastage'] ?? 0,
                'operator_name' => $data['operator_name'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id() ?? 1,
            ]);

            // 2. Update Step Progress
            $step->produced_qty += $data['quantity'];
            $step->wastage_qty += ($data['wastage'] ?? 0);
            
            if ($step->status === 'Pending') {
                $step->status = 'In-Progress';
                $step->started_at = now();
            }

            // Check if step is completed (optional logic based on planned qty)
            if ($step->produced_qty >= $jobCard->planned_qty) {
                $step->status = 'Completed';
                $step->completed_at = now();
            }

            $step->save();

            // 3. Update Job Card status if it was Open
            if ($jobCard->status === 'Open') {
                $jobCard->status = 'In-Progress';
                $jobCard->save();
            }

            return $log;
        });
    }
}
