<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CartageRate;
use App\Models\CartageIncrementLog;
use App\Models\CartageIncrementDetail;
use Illuminate\Support\Facades\DB;

try {
    DB::transaction(function () {
        // Get unique vehicle types from current rates
        $classifications = CartageRate::distinct()->pluck('vehicle_type');

        if ($classifications->isEmpty()) {
            echo "No existing rates found to import.\n";
            return;
        }

        foreach ($classifications as $type) {
            // Create a log entry for each classification
            $log = CartageIncrementLog::create([
                'vehicle_type' => $type,
                'effective_date' => '2023-09-01',
                'increment_type' => 'Initial List',
                'increment_value' => 0,
                'created_at' => '2023-09-01 09:00:00',
                'updated_at' => '2023-09-01 09:00:00',
            ]);

            // Get all rates for this classification
            $rates = CartageRate::where('vehicle_type', $type)->get();

            foreach ($rates as $rate) {
                CartageIncrementDetail::create([
                    'cartage_increment_log_id' => $log->id,
                    'shipping_address_id' => $rate->shipping_address_id,
                    'old_rate' => $rate->rate,
                    'new_rate' => $rate->rate,
                    'amount_increase' => 0,
                    'created_at' => '2023-09-01 09:00:00',
                    'updated_at' => '2023-09-01 09:00:00',
                ]);
            }
            
            echo "Imported history for classification: $type\n";
        }
    });
    echo "Successfully added historical rate lists from 01-Sep-2023.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
