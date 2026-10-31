<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'title'        => fake()->words(3, true),
            'description'  => fake()->paragraph(),
            'image_url'    => fake()->imageUrl(),
            'github_url'   => fake()->url(),
            'live_url'     => fake()->url(),
            'start_date'   => fake()->date(),
            'end_date'     => fake()->date(),
            'is_featured'  => fake()->boolean(),
            'is_public'    => fake()->boolean(),
        ];
    }
}
