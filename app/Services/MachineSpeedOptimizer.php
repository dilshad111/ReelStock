<?php

namespace App\Services;

use App\Models\OptimizationRule;

class MachineSpeedOptimizer
{
    public function apply(float $baseSpeed, array $jobContext, ?float $minimumSpeed = null): array
    {
        $speed = $baseSpeed;
        $appliedRules = [];

        $rules = OptimizationRule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (!$this->matches($rule, $jobContext)) {
                continue;
            }

            $impact = $rule->adjustment_type === 'percentage_reduction'
                ? ($speed * ($rule->adjustment_value / 100))
                : $rule->adjustment_value;

            $speed -= $impact;
            $appliedRules[] = [
                'id' => $rule->id,
                'parameter_name' => $rule->parameter_name,
                'impact' => round($impact, 2),
                'adjustment_type' => $rule->adjustment_type,
                'adjustment_value' => $rule->adjustment_value,
            ];
        }

        if ($minimumSpeed !== null) {
            $speed = max($speed, $minimumSpeed);
        }

        return [
            'base_speed' => $baseSpeed,
            'final_speed' => round(max($speed, 0), 2),
            'minimum_speed' => $minimumSpeed,
            'applied_rules' => $appliedRules,
        ];
    }

    private function matches(OptimizationRule $rule, array $jobContext): bool
    {
        if (!array_key_exists($rule->condition_field, $jobContext)) {
            return false;
        }

        $actual = $jobContext[$rule->condition_field];
        $expected = $rule->condition_value;

        if (is_numeric($actual) && is_numeric($expected)) {
            $actual = (float) $actual;
            $expected = (float) $expected;
        }

        return match ($rule->operator) {
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '=' => (string) $actual === (string) $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => false,
        };
    }
}
