<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ReelReturn;
use App\Models\Reel;

class ReelReturnFactory extends Factory
{
    protected $model = ReelReturn::class;

    public function definition()
    {
        return [
            'reel_id' => Reel::factory(),
            'challan_no' => 'RT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'vehicle_number' => 'V-' . $this->faker->bothify('???-####'),
            'return_date' => $this->faker->date(),
            'remaining_weight' => $this->faker->randomFloat(2, 50, 500),
            'returned_to' => $this->faker->randomElement(['stock', 'supplier']),
            'condition' => 'good',
            'remarks' => $this->faker->sentence,
        ];
    }
}
