<?php

namespace App\Domains\RawMaterial\Actions;

use App\Models\RMItem;
use App\Models\RMStockLedger;
use Illuminate\Support\Facades\DB;

class CreateRMItemAction
{
    public function execute(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate auto code
            $lastItem = RMItem::orderBy('id', 'desc')->first();
            $nextId = $lastItem ? $lastItem->id + 1 : 1;
            $data['code'] = 'RM-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $item = RMItem::create($data);

            // Create initial ledger entry for opening stock
            if ($item->opening_stock > 0) {
                RMStockLedger::create([
                    'rm_item_id' => $item->id,
                    'transaction_type' => 'opening',
                    'reference_id' => $item->id,
                    'quantity_in' => $item->opening_stock,
                    'quantity_out' => 0,
                    'balance_after' => $item->opening_stock,
                    'transaction_date' => now()->toDateString(),
                ]);
            } else {
                // Even with 0 stock, create an entry to initialize the ledger
                RMStockLedger::create([
                    'rm_item_id' => $item->id,
                    'transaction_type' => 'opening',
                    'reference_id' => $item->id,
                    'quantity_in' => 0,
                    'quantity_out' => 0,
                    'balance_after' => 0,
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            return $item;
        });
    }
}
