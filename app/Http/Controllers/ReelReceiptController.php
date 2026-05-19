<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\ReelReceipt;
use App\Http\Requests\Inventory\StoreReelReceiptRequest;
use App\Domains\Inventory\DTOs\ReelReceiptDTO;
use App\Domains\Inventory\Actions\CreateReelReceiptAction;
use App\Domains\Inventory\Actions\FetchReelReceiptsAction;
use App\Domains\Inventory\Actions\UpdateReelReceiptAction;
use App\Domains\Inventory\Actions\DeleteReelReceiptAction;
use App\Domains\Inventory\Actions\BulkCreateReelReceiptAction;
use App\Http\Resources\Inventory\ReelReceiptResource;

class ReelReceiptController extends Controller
{
    public function index(Request $request, FetchReelReceiptsAction $action)
    {
        try {
            $filters = $request->all();
            $limit = $request->get('limit', 50);
            
            $receipts = $action->execute($filters, $limit);
            $prefix = Setting::where('key', 'reel_no_prefix')->value('value') ?? 'RL';

            // Add the paper_quality_display property to each reel for backward compatibility with frontend
            $receipts->getCollection()->each(function ($receipt) {
                if ($receipt->reel) {
                    $reel = $receipt->reel;
                    $quality = $reel->paperQuality ? ($reel->paperQuality->quality ?? $reel->paperQuality->item_code ?? '') : 'N/A';
                    $gsm = ($reel->paperQuality && $reel->paperQuality->gsm_range) ? ' ' . $reel->paperQuality->gsm_range : '';
                    $reel->paper_quality_display = ($quality === 'N/A') ? 'N/A' : ($quality . $gsm);
                }
            });

            $response = $receipts->toArray();
            $response['reel_prefix'] = $prefix;

            return response()->json($response);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreReelReceiptRequest $request, CreateReelReceiptAction $action)
    {
        $dto = ReelReceiptDTO::fromRequest($request);
        $receipt = $action->execute($dto);
        
        return $this->success(
            new ReelReceiptResource($receipt->load('reel.paperQuality', 'reel.supplier')),
            'Reel receipt created successfully.',
            201
        );
    }

    public function bulkStore(Request $request, BulkCreateReelReceiptAction $action)
    {
        $request->validate([
            'common' => 'required|array',
            'common.paper_quality_id' => 'required|exists:paper_qualities,id',
            'common.supplier_id' => 'required|exists:suppliers,id',
            'common.receiving_date' => 'required|date',
            'common.qc_status' => 'required|in:approved,rejected,on_hold',
            'reels' => 'required|array|min:1',
            'reels.*.reel_size' => 'required|numeric|min:0',
            'reels.*.reel_weight' => 'required|numeric|min:0',
        ]);

        try {
            $receipts = $action->execute($request->input('common'), $request->input('reels'));
            
            $loadedReceipts = ReelReceipt::with('reel.paperQuality', 'reel.supplier')
                ->whereIn('id', array_column($receipts, 'id'))
                ->get();

            return $this->success(
                ReelReceiptResource::collection($loadedReceipts),
                'Bulk reel receipts created successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $receipt = ReelReceipt::with('reel.paperQuality', 'reel.supplier')->findOrFail($id);
        return $this->success(new ReelReceiptResource($receipt));
    }

    public function update(Request $request, $id, UpdateReelReceiptAction $action)
    {
        try {
            $receipt = ReelReceipt::with('reel')->findOrFail($id);
            
            $receiptFields = ['receiving_date', 'received_by', 'gsm', 'bursting_strength', 'rate_per_kg', 'qc_status', 'remarks'];
            $receiptData = $request->only($receiptFields);
            
            $reelFields = ['paper_quality_id', 'supplier_id', 'reel_size'];
            $reelData = $request->only($reelFields);
            
            $originalWeight = $request->has('reel_weight') ? (float) $request->reel_weight : null;

            $updatedReceipt = $action->execute($receipt, $receiptData, $reelData, $originalWeight);

            return $this->success(
                new ReelReceiptResource($updatedReceipt),
                'Reel receipt updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id, DeleteReelReceiptAction $action)
    {
        try {
            $receipt = ReelReceipt::with('reel')->findOrFail($id);
            $action->execute($receipt);
            
            return $this->success(null, 'Receipt deleted successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
