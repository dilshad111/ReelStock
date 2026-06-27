<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FGStockLedger extends Model
{
    use HasFactory;

    protected $table = 'fg_stock_ledger';

    public $timestamps = false;

    protected $fillable = [
        'transaction_type', 'reference_id', 'product_id', 'warehouse_id',
        'customer_id', 'job_number', 'quantity_in', 'quantity_out',
        'balance_after', 'transaction_date', 'document_number', 'remarks', 'created_by'
    ];

    protected $casts = [
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public static function recalculateForProduct($productId)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($productId) {
            $product = Product::findOrFail($productId);
            
            // 1. Get all actual transactions
            $receipts = FGReceipt::where('product_id', $productId)
                ->get()
                ->map(function ($r) {
                    $dateStr = ($r->date instanceof \DateTimeInterface) ? $r->date->format('Y-m-d') : substr((string)$r->date, 0, 10);
                    return [
                        'type' => 'receipt',
                        'id' => $r->id,
                        'customer_id' => $r->customer_id,
                        'warehouse_id' => $r->warehouse_id ?? 1,
                        'document_number' => 'RCPT-' . $r->id,
                        'created_by' => $r->created_by ?? 1,
                        'job_number' => $r->job_number,
                        'quantity_in' => (float)$r->quantity_produced,
                        'quantity_out' => 0.0,
                        'date' => $dateStr,
                        'remarks' => $r->remarks,
                    ];
                });

            $dispatches = FGDispatch::where('product_id', $productId)
                ->get()
                ->map(function ($d) {
                    $dateStr = ($d->date instanceof \DateTimeInterface) ? $d->date->format('Y-m-d') : substr((string)$d->date, 0, 10);
                    return [
                        'type' => 'dispatch',
                        'id' => $d->id,
                        'customer_id' => $d->customer_id,
                        'warehouse_id' => $d->warehouse_id ?? 1,
                        'document_number' => $d->dc_number ?? ('DSP-' . $d->id),
                        'created_by' => $d->created_by ?? 1,
                        'job_number' => $d->job_number,
                        'quantity_in' => 0.0,
                        'quantity_out' => (float)$d->quantity_dispatched,
                        'date' => $dateStr,
                        'remarks' => $d->remarks,
                    ];
                });

            // Combine and sort chronologically. If dates match, receipts come before dispatches, then sorted by ID.
            $transactions = $receipts->concat($dispatches)->sort(function ($a, $b) {
                if ($a['date'] === $b['date']) {
                    if ($a['type'] !== $b['type']) {
                        return $a['type'] === 'receipt' ? -1 : 1;
                    }
                    return $a['id'] <=> $b['id'];
                }
                return strcmp($a['date'], $b['date']);
            })->values();

            // 2. Delete existing ledger entries for this product
            self::where('product_id', $productId)->delete();

            // 3. Create opening balance entry
            $runningBalances = [];
            $runningBalances[1] = (float)$product->opening_balance;
            $openingDate = $product->created_at ? $product->created_at->format('Y-m-d') : now()->toDateString();
            
            self::create([
                'transaction_type' => 'opening',
                'reference_id' => $product->id,
                'document_number' => 'OPENING',
                'product_id' => $product->id,
                'warehouse_id' => 1,
                'customer_id' => $product->customer_id,
                'job_number' => null,
                'quantity_in' => $product->opening_balance,
                'quantity_out' => 0,
                'balance_after' => $runningBalances[1],
                'transaction_date' => $openingDate,
                'created_by' => 1,
                'remarks' => 'Opening Balance'
            ]);

            // 4. Create ledger entries for each transaction
            foreach ($transactions as $tx) {
                $whId = $tx['warehouse_id'];
                if (!isset($runningBalances[$whId])) {
                    $runningBalances[$whId] = 0.0000;
                }

                $runningBalances[$whId] += $tx['quantity_in'];
                $runningBalances[$whId] -= $tx['quantity_out'];

                self::create([
                    'transaction_type' => $tx['type'],
                    'reference_id' => $tx['id'],
                    'document_number' => $tx['document_number'],
                    'product_id' => $productId,
                    'warehouse_id' => $whId,
                    'customer_id' => $tx['customer_id'],
                    'job_number' => $tx['job_number'],
                    'quantity_in' => $tx['quantity_in'],
                    'quantity_out' => $tx['quantity_out'],
                    'balance_after' => $runningBalances[$whId],
                    'transaction_date' => $tx['date'],
                    'created_by' => $tx['created_by'],
                    'remarks' => $tx['remarks'] ?? null,
                ]);
            }

            // Validate negative balances
            foreach ($runningBalances as $whId => $bal) {
                if ($bal < 0) {
                    $wh = Warehouse::find($whId);
                    if ($wh && !$wh->allow_negative_stock) {
                        throw new \Exception("Action would result in negative stock balance ({$bal}) for product {$product->item_code} in warehouse {$wh->name}.");
                    }
                }
            }

            // Sync with current stock cache
            CurrentFGStock::where('product_id', $productId)->delete();
            foreach ($runningBalances as $whId => $bal) {
                CurrentFGStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $whId,
                    'quantity' => $bal
                ]);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
