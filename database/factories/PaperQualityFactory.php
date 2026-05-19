<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaperQuality;

class PaperQualityFactory extends Factory
{
    protected $model = PaperQuality::class;

    public function definition()
    {
        return [
            'item_code' => $this->faker->unique()->word . '-' . $this->faker->numberBetween(100, 999),
            'quality' => $this->faker->randomElement(['Kraft', 'Testliner', 'Fluting', 'White Top']),
            'gsm_range' => $this->faker->numberBetween(100, 200) . '-' . $this->faker->numberBetween(201, 300),
            'min_gsm' => 100,
            'max_gsm' => 300,
            'paper_color' => 'Brown',
        ];
    }
}
