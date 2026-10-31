<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinanceCategoryFactory extends Factory
{
    protected $model = FinanceCategory::class;

    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'name'      => fake()->word(),
            'type'      => fake()->randomElement(['income', 'expense']),
            'color'     => fake()->hexcolor(),
            'icon'      => fake()->word(),
            'is_system' => fake()->boolean(),
        ];
    }
}
