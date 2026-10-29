<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesTransaction>
 */
class SalesTransactionFactory extends Factory
{
    protected $model = SalesTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_code' => 'TXN-' . strtoupper(fake()->unique()->bothify('####-????')),
            'member_id' => Member::factory(),
            'sale_amount' => fake()->randomFloat(2, 50, 5000),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => 'completed',
            'reference_note' => fake()->sentence(),
        ];
    }
}
