<?php

namespace Database\Factories;

use App\Models\Summary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SummaryFactory extends Factory
{
    protected $model = Summary::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'period'        => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'start_date'    => fake()->date(),
            'end_date'      => fake()->date(),
            'total_income'  => fake()->randomFloat(2, 0, 10000000),
            'total_expense' => fake()->randomFloat(2, 0, 5000000),
            'net'           => fake()->randomFloat(2, -5000000, 10000000),
            'net_worth'     => fake()->randomFloat(2, 0, 20000000),
            'breakdown'     => null,
        ];
    }
}
