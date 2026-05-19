<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ReelReceipt;
use App\Models\Reel;

class ReelReceiptFactory extends Factory
{
    protected $model = ReelReceipt::class;

    public function definition()
    {
        return [
            'reel_id' => Reel::factory(),
            'lot_number' => 'LOT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'po_number' => 'PO-' . $this->faker->numberBetween(1000, 9999),
            'grn_number' => 'GRN-' . $this->faker->numberBetween(1000, 9999),
            'receiving_date' => $this->faker->date(),
            'received_by' => $this->faker->name,
            'gsm' => $this->faker->numberBetween(100, 300),
            'bursting_strength' => $this->faker->randomFloat(2, 10, 50),
            'qc_status' => 'approved',
            'rate_per_kg' => $this->faker->randomFloat(2, 50, 150),
            'remarks' => $this->faker->sentence,
        ];
    }
}
