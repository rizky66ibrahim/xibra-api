<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'name'         => fake()->words(3, true),
            'category'     => fake()->randomElement(['makanan', 'transport', 'hiburan', 'tagihan', 'tabungan']),
            'amount'       => fake()->randomFloat(2, 500000, 5000000),
            'period'       => 'monthly',
            'start_date'   => fake()->date(),
            'end_date'     => fake()->optional()->date(),
            'spent_amount' => fake()->randomFloat(2, 0, 2000000),
            'is_active'    => fake()->boolean(),
        ];
    }
}
