<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ReelIssue;
use App\Models\Reel;

class ReelIssueFactory extends Factory
{
    protected $model = ReelIssue::class;

    public function definition()
    {
        return [
            'reel_id' => Reel::factory(),
            'issue_date' => $this->faker->date(),
            'quantity_issued' => $this->faker->randomFloat(2, 100, 500),
            'issued_to' => $this->faker->word,
            'remarks' => $this->faker->sentence,
            'return_to_stock_weight' => 0,
            'net_consumed_weight' => function (array $attributes) {
                return $attributes['quantity_issued'] - $attributes['return_to_stock_weight'];
            },
        ];
    }
}
