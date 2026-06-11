<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Inventory\StoreReelIssueRequest;
use App\Domains\Inventory\DTOs\ReelIssueDTO;
use App\Domains\Inventory\Actions\CreateReelIssueAction;
use App\Domains\Inventory\Actions\FetchReelIssuesAction;
use App\Domains\Inventory\Actions\UpdateReelIssueAction;
use App\Domains\Inventory\Actions\DeleteReelIssueAction;
use App\Http\Resources\Inventory\ReelIssueResource;

class ReelIssueController extends Controller
{
    public function index(Request $request, FetchReelIssuesAction $action)
    {
        try {
            $issues = $action->execute($request->all(), 50);
            return response()->json($issues);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreReelIssueRequest $request, CreateReelIssueAction $action)
    {
        try {
            $dto = ReelIssueDTO::fromRequest($request);
            $issue = $action->execute($dto);
            
            return $this->success(
                new ReelIssueResource($issue->load('reel.paperQuality', 'reel.supplier')),
                'Reel issue created successfully.',
                201
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            return $this->error($e->getMessage(), $statusCode);
        }
    }

    public function show($id)
    {
        try {
            $issue = ReelIssue::with(['reel.paperQuality', 'reel.supplier'])->findOrFail($id);
            return $this->success(new ReelIssueResource($issue));
        } catch (\Exception $e) {
            return $this->error('Reel issue not found', 404);
        }
    }

    public function update(Request $request, $id, UpdateReelIssueAction $action)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'quantity_issued' => 'required|numeric|min:0.01',
            'return_to_stock_weight' => 'nullable|numeric|min:0',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'issued_to' => 'required|string',
            'remarks' => 'nullable|string',
            'reel_no' => 'nullable|string'
        ]);

        try {
            $issue = ReelIssue::with(['reel.paperQuality', 'reel.supplier'])->lockForUpdate()->findOrFail($id);
            $updatedIssue = $action->execute($issue, $request->all());

            return $this->success(
                new ReelIssueResource($updatedIssue),
                'Reel issue updated successfully.'
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            return $this->error($e->getMessage(), $statusCode);
        }
    }

    public function destroy($id, DeleteReelIssueAction $action)
    {
        try {
            $action->execute($id);
            return $this->success(null, 'Issue deleted successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function fetchReel($reel_no)
    {
        $reel = Reel::with('paperQuality', 'supplier')->where('reel_no', $reel_no)->first();
        if (!$reel) {
            return $this->error('Reel not found', 404);
        }
        return $this->success($reel);
    }
}

