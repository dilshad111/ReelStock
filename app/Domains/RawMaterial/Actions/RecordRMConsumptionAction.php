<?php

namespace App\Domains\RawMaterial\Actions;

use App\Models\RMConsumption;
use App\Models\RMConsumptionItem;
use App\Models\RMStockLedger;
use App\Domains\RawMaterial\DTOs\RMConsumptionDTO;
use Illuminate\Support\Facades\DB;
use Exception;

class RecordRMConsumptionAction
{
    public function execute(RMConsumptionDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $consumption = RMConsumption::create([
                'voucher_no' => $dto->voucher_no,
                'job_card_id' => $dto->job_card_id,
                'date' => $dto->date,
                'department' => $dto->department,
                'issued_to' => $dto->issued_to,
                'notes' => $dto->notes,
                'created_by' => $dto->created_by,
            ]);

            foreach ($dto->items as $itemData) {
                // Check stock before consumption
                $lastLedger = RMStockLedger::where('rm_item_id', $itemData['rm_item_id'])
                    ->latest('id')->lockForUpdate()->first();
                
                $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : 0;
                
                if ($currentBalance < $itemData['quantity']) {
                    throw new Exception("Insufficient stock for item ID: {$itemData['rm_item_id']}. Available: {$currentBalance}");
                }

                RMConsumptionItem::create([
                    'rm_consumption_id' => $consumption->id,
                    'rm_item_id' => $itemData['rm_item_id'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Update Ledger
                $newBalance = $currentBalance - (float)$itemData['quantity'];

                RMStockLedger::create([
                    'rm_item_id' => $itemData['rm_item_id'],
                    'transaction_type' => 'consumption',
                    'reference_id' => $consumption->id,
                    'quantity_in' => 0,
                    'quantity_out' => $itemData['quantity'],
                    'balance_after' => $newBalance,
                    'transaction_date' => $dto->date,
                ]);

                // Update Job Card requirement progress
                if ($consumption->job_card_id) {
                    \App\Models\JobCardItem::where('job_card_id', $consumption->job_card_id)
                        ->where('rm_item_id', $itemData['rm_item_id'])
                        ->increment('consumed_qty', $itemData['quantity']);
                }
            }

            return $consumption->load('items.item');
        });
    }
}
