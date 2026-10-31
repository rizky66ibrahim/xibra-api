<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'title'               => fake()->sentence(),
            'description'         => fake()->paragraph(),
            'status'              => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'priority'            => fake()->randomElement(['low', 'medium', 'high']),
            'due_date'            => fake()->date(),
            'scheduled_start_at'  => fake()->dateTime(),
            'scheduled_end_at'    => fake()->dateTime(),
            'is_completed'        => fake()->boolean(),
            'completed_at'        => null,
        ];
    }
}
