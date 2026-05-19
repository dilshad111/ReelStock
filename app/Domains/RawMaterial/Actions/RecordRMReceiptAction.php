<?php

namespace App\Domains\RawMaterial\Actions;

use App\Models\RMReceipt;
use App\Models\RMReceiptItem;
use App\Models\RMStockLedger;
use App\Domains\RawMaterial\DTOs\RMReceiptDTO;
use Illuminate\Support\Facades\DB;

class RecordRMReceiptAction
{
    public function execute(RMReceiptDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $receipt = RMReceipt::create([
                'grn_no' => $dto->grn_no,
                'supplier_id' => $dto->supplier_id,
                'date' => $dto->date,
                'remarks' => $dto->remarks,
                'attachment_path' => $dto->attachment_path,
                'created_by' => $dto->created_by,
            ]);

            foreach ($dto->items as $itemData) {
                RMReceiptItem::create([
                    'rm_receipt_id' => $receipt->id,
                    'rm_item_id' => $itemData['rm_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'rate' => $itemData['rate'],
                    'total_amount' => $itemData['total_amount'],
                ]);

                // Update Ledger
                $lastLedger = RMStockLedger::where('rm_item_id', $itemData['rm_item_id'])
                    ->latest('id')->lockForUpdate()->first();
                
                $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
                $newBalance = $currentBalance + (float)$itemData['quantity'];

                RMStockLedger::create([
                    'rm_item_id' => $itemData['rm_item_id'],
                    'transaction_type' => 'receipt',
                    'reference_id' => $receipt->id,
                    'quantity_in' => $itemData['quantity'],
                    'quantity_out' => 0,
                    'balance_after' => $newBalance,
                    'transaction_date' => $dto->date,
                ]);
            }

            return $receipt->load('items.item', 'supplier');
        });
    }
}
