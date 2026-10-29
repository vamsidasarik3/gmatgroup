<?php

namespace Database\Factories;

use App\Models\CommissionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommissionModel>
 */
class CommissionModelFactory extends Factory
{
    protected $model = CommissionModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Model',
            'description' => fake()->sentence(),
            'model_type' => fake()->randomElement(['weakest_link', 'generation_override', 'unilevel']),
            'status' => 'active',
            'commission_rate' => 5.0000,
            'max_generations' => 5,
            'settings' => [
                'currency' => 'USD',
                'payout_threshold' => 10.00,
                'generation_limit' => 5,
                'include_inactive_members' => false,
            ],
        ];
    }
}
