<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'name'                => fake()->word(),
            'category'            => fake()->randomElement(['programming', 'design', 'marketing']),
            'level'               => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
            'years_of_experience' => fake()->numberBetween(0, 20),
            'is_public'           => fake()->boolean(),
        ];
    }
}
