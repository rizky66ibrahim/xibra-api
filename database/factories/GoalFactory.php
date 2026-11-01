<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'title'           => fake()->words(3, true),
            'description'     => fake()->sentence(),
            'emoji'           => fake()->randomElement(['💰', '🏠', '🚗', '📱', '✈️']),
            'target_amount'   => fake()->randomFloat(2, 100000, 50000000),
            'current_amount'  => fake()->randomFloat(2, 0, 1000000),
            'target_date'     => fake()->optional()->date(),
        ];
    }
}
