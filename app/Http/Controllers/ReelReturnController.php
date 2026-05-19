<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelReturn;
use Carbon\Carbon;
use App\Http\Requests\Inventory\StoreReelReturnRequest;
use App\Domains\Inventory\DTOs\ReelReturnDTO;
use App\Domains\Inventory\Actions\CreateReelReturnAction;
use App\Domains\Inventory\Actions\FetchReelReturnsAction;
use App\Domains\Inventory\Actions\UpdateReelReturnAction;
use App\Domains\Inventory\Actions\DeleteReelReturnAction;
use App\Http\Resources\Inventory\ReelReturnResource;

class ReelReturnController extends Controller
{
    public function index(Request $request, FetchReelReturnsAction $action)
    {
        try {
            $returns = $action->execute($request->all());
            return response()->json($returns);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreReelReturnRequest $request, CreateReelReturnAction $action)
    {
        try {
            $dto = ReelReturnDTO::fromRequest($request);
            $return = $action->execute($dto);
            
            return $this->success(
                new ReelReturnResource($return->load('reel.paperQuality', 'reel.supplier')),
                'Reel return created successfully.',
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
            $return = ReelReturn::with('reel')->findOrFail($id);
            return $this->success(new ReelReturnResource($return));
        } catch (\Exception $e) {
            return $this->error('Reel return not found', 404);
        }
    }

    public function update(Request $request, $id, UpdateReelReturnAction $action)
    {
        $request->validate([
            'return_date' => 'required|date',
            'remaining_weight' => 'required|numeric|min:0',
            'returned_to' => 'required|in:supplier',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'condition' => 'required|in:good,damaged,qc_required',
            'vehicle_number' => 'nullable|string|max:50',
            'return_to_supplier_id' => 'nullable|exists:suppliers,id',
            'remarks' => 'nullable|string',
        ]);

        try {
            $return = ReelReturn::with('reel')->lockForUpdate()->findOrFail($id);
            $updatedReturn = $action->execute($return, $request->all());

            return $this->success(
                new ReelReturnResource($updatedReturn),
                'Reel return updated successfully.'
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            return $this->error($e->getMessage(), $statusCode);
        }
    }

    public function destroy($id, DeleteReelReturnAction $action)
    {
        try {
            $action->execute($id);
            return $this->success(null, 'Return deleted successfully.');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 400;
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 400;
            }
            return $this->error($e->getMessage(), $statusCode);
        }
    }

    public function fetchReel($reel_no)
    {
        $reel = Reel::with([
            'paperQuality',
            'supplier',
            'receipts' => function ($query) {
                $query->orderByDesc('receiving_date')->limit(1);
            }
        ])->where('reel_no', $reel_no)->first();
        if (!$reel) {
            return $this->error('Reel not found', 404);
        }
        $latestReceipt = $reel->receipts->first();
        $reel->setRelation('latest_receipt', $latestReceipt);

        $latestIssue = $reel->issues()->orderByDesc('issue_date')->orderByDesc('created_at')->first();
        $latestStockReturn = $reel->returns()->where('returned_to', 'stock')->orderByDesc('return_date')->orderByDesc('created_at')->first();

        $hasIssueHistory = $latestIssue !== null;
        $alreadyInStock = !$hasIssueHistory;

        if ($hasIssueHistory && $latestStockReturn) {
            $issueDate = $latestIssue->issue_date ? Carbon::parse($latestIssue->issue_date) : null;
            $issueCreated = $latestIssue->created_at;
            $returnDate = $latestStockReturn->return_date ? Carbon::parse($latestStockReturn->return_date) : null;
            $returnCreated = $latestStockReturn->created_at;

            if ($issueDate && $returnDate) {
                if ($returnDate->gt($issueDate)) {
                    $alreadyInStock = true;
                } elseif ($returnDate->eq($issueDate)) {
                    if ($returnCreated && $issueCreated && $returnCreated->gte($issueCreated)) {
                        $alreadyInStock = true;
                    }
                } else {
                    $alreadyInStock = false;
                }
            } elseif (!$issueDate && $returnDate) {
                $alreadyInStock = true;
            } elseif ($returnCreated && $issueCreated) {
                $alreadyInStock = $returnCreated->gte($issueCreated);
            }

            if (!$alreadyInStock) {
                $hasNewIssueAfterReturn = $reel->issues()
                    ->where(function ($query) use ($latestStockReturn) {
                        $query->where('issue_date', '>', $latestStockReturn->return_date)
                              ->orWhere(function ($inner) use ($latestStockReturn) {
                                  $inner->where('issue_date', $latestStockReturn->return_date)
                                        ->where('created_at', '>', $latestStockReturn->created_at);
                              });
                    })
                    ->exists();
                $alreadyInStock = !$hasNewIssueAfterReturn;
            }
        }

        $reel->setAttribute('has_issue_history', $hasIssueHistory);
        $reel->setAttribute('already_in_stock', $alreadyInStock);
        return $this->success($reel);
    }
}
