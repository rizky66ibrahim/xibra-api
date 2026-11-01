<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'title'           => fake()->words(3, true),
            'type'            => fake()->randomElement(['debt', 'credit']),
            'amount'          => fake()->randomFloat(2, 100000, 10000000),
            'currency'        => 'IDR',
            'description'     => fake()->sentence(),
            'due_date'        => fake()->optional()->date(),
            'paid_amount'     => fake()->randomFloat(2, 0, 500000),
            'status'          => fake()->randomElement(['pending', 'paid', 'overdue']),
            'source_of_funds' => fake()->optional()->words(2, true),
        ];
    }
}
