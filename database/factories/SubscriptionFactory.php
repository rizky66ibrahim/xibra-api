<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'name'           => fake()->words(2, true),
            'provider'       => fake()->company(),
            'amount'         => fake()->randomFloat(2, 50000, 500000),
            'currency'       => 'IDR',
            'billing_cycle'  => fake()->randomElement(['monthly', 'yearly']),
            'next_due_date'  => fake()->date(),
            'status'         => fake()->randomElement(['active', 'cancelled', 'paused']),
            'category'       => fake()->randomElement(['entertainment', 'software', 'news']),
        ];
    }
}
