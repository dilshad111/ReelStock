<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReelReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReelReturn::with(['reel.paperQuality', 'reel.supplier', 'returnToSupplier']);

        if ($request->filled('returned_to')) {
            $query->where('returned_to', $request->input('returned_to'));
        }

        return response()->json($query->orderByDesc('return_date')->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'reel_no' => 'required|string|exists:reels',
            'challan_no' => 'nullable|string|max:50',
            'return_date' => 'required|date',
            'remaining_weight' => 'required|numeric|min:0',
            'returned_to' => 'required|in:stock,supplier',
            'condition' => 'required|in:good,damaged,qc_required',
            'vehicle_number' => 'nullable|string|max:50',
            'return_to_supplier_id' => 'nullable|exists:suppliers,id',
            'remarks' => 'nullable|string',
        ]);

        $reel = Reel::where('reel_no', $request->reel_no)->first();

        if (!$reel) {
            return response()->json(['error' => 'Reel not found'], 404);
        }

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
            } elseif ($issueDate && !$returnDate) {
                $alreadyInStock = false;
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

        if ($request->remaining_weight > $reel->original_weight) {
            return response()->json(['error' => 'Remaining weight cannot exceed original weight'], 400);
        }

        if ($request->returned_to === 'supplier' && $request->remaining_weight > $reel->balance_weight) {
            return response()->json(['error' => 'Returned weight cannot exceed current balance weight'], 400);
        }

        if ($request->returned_to === 'stock' && (!$hasIssueHistory || $alreadyInStock)) {
            return response()->json(['error' => 'This reel is already in the stock.'], 400);
        }

        $challanNo = null;
        if ($request->returned_to === 'supplier') {
            // Use provided challan_no if available, otherwise generate new one
            if ($request->filled('challan_no')) {
                $challanNo = $request->challan_no;
            } else {
                $challanNo = 'RT' . str_pad($this->generateNextChallanSequence(), 4, '0', STR_PAD_LEFT);
            }
        }

        $return = ReelReturn::create([
            'reel_id' => $reel->id,
            'challan_no' => $challanNo,
            'return_date' => $request->return_date,
            'remaining_weight' => $request->remaining_weight,
            'returned_to' => $request->returned_to,
            'condition' => $request->condition,
            'vehicle_number' => $request->vehicle_number,
            'return_to_supplier_id' => $request->return_to_supplier_id,
            'remarks' => $request->remarks,
        ]);

        if ($request->returned_to === 'stock') {
            $reel->balance_weight = $request->remaining_weight;
        } else {
            $reel->balance_weight = max($reel->balance_weight - $request->remaining_weight, 0);
        }

        $this->updateReelStatus($reel);
        $reel->save();

        return response()->json($return->load('reel.paperQuality', 'reel.supplier'), 201);
    }

    public function show($id)
    {
        $return = ReelReturn::with('reel')->findOrFail($id);
        return response()->json($return);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'return_date' => 'required|date',
            'remaining_weight' => 'required|numeric|min:0',
            'returned_to' => 'required|in:supplier',
            'condition' => 'required|in:good,damaged,qc_required',
            'vehicle_number' => 'nullable|string|max:50',
            'return_to_supplier_id' => 'nullable|exists:suppliers,id',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $return = ReelReturn::with('reel')->lockForUpdate()->findOrFail($id);

            if ($return->returned_to !== 'supplier') {
                return response()->json(['error' => 'Only supplier returns can be edited.'], 400);
            }

            $reel = $return->reel;
            if (!$reel) {
                return response()->json(['error' => 'Associated reel not found.'], 404);
            }

            $oldWeight = (float) $return->remaining_weight;
            $restoredBalance = min($reel->balance_weight + $oldWeight, $reel->original_weight);
            $newWeight = (float) $request->remaining_weight;

            if ($newWeight > $restoredBalance) {
                return response()->json(['error' => 'Returned weight cannot exceed current balance weight.'], 400);
            }

            $return->return_date = $request->return_date;
            $return->remaining_weight = $newWeight;
            $return->condition = $request->condition;
            $return->vehicle_number = $request->vehicle_number;
            $return->return_to_supplier_id = $request->return_to_supplier_id;
            $return->remarks = $request->remarks;
            $return->save();

            $reel->balance_weight = max($restoredBalance - $newWeight, 0);
            $this->updateReelStatus($reel);
            $reel->save();

            return response()->json($return->load('reel.paperQuality', 'reel.supplier'));
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $return = ReelReturn::with('reel')->lockForUpdate()->findOrFail($id);

            if ($return->returned_to !== 'supplier') {
                return response()->json(['error' => 'Only supplier returns can be deleted.'], 400);
            }

            $reel = $return->reel;
            if (!$reel) {
                return response()->json(['error' => 'Associated reel not found.'], 404);
            }

            $reel->balance_weight = min($reel->balance_weight + (float) $return->remaining_weight, $reel->original_weight);
            $this->updateReelStatus($reel);
            $reel->save();

            $return->delete();

            return response()->json(['message' => 'Return deleted successfully.']);
        });
    }

    protected function updateReelStatus(Reel $reel): void
    {
        if ($reel->balance_weight <= 0) {
            $reel->status = 'fully_used';
        } elseif ($reel->balance_weight < $reel->original_weight) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
    }

    protected function generateNextChallanSequence(): int
    {
        $challans = ReelReturn::whereNotNull('challan_no')
            ->pluck('challan_no');

        $max = 0;

        foreach ($challans as $challan) {
            $sequence = $this->parseChallanSequence($challan);
            if ($sequence > $max) {
                $max = $sequence;
            }
        }

        return $max + 1;
    }

    protected function parseChallanSequence(?string $challanNo): int
    {
        if (!$challanNo) {
            return 0;
        }

        if (preg_match('/^RT(\d+)$/', strtoupper($challanNo), $matches)) {
            return (int) $matches[1];
        }

        return 0;
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
            return response()->json(['message' => 'Reel not found'], 404);
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
        return response()->json($reel);
    }
}
