<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioFactory extends Factory
{
    protected $model = Portfolio::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'title'        => fake()->words(3, true),
            'description'  => fake()->paragraph(),
            'image_url'    => fake()->imageUrl(),
            'is_public'    => fake()->boolean(),
            'is_featured'  => fake()->boolean(),
        ];
    }
}
