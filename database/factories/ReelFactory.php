<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reel;
use App\Models\Supplier;
use App\Models\PaperQuality;

class ReelFactory extends Factory
{
    protected $model = Reel::class;

    public function definition()
    {
        $originalWeight = $this->faker->randomFloat(2, 500, 2000);
        
        return [
            'reel_no' => 'RL' . $this->faker->unique()->numberBetween(10000, 99999),
            'supplier_id' => Supplier::factory(),
            'paper_quality_id' => PaperQuality::factory(),
            'reel_size' => $this->faker->randomElement([20, 25, 30, 35, 40]),
            'original_weight' => $originalWeight,
            'balance_weight' => $originalWeight,
            'status' => 'in_stock',
        ];
    }
}
