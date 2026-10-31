<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'title'             => fake()->sentence(),
            'description'       => fake()->paragraph(),
            'start_time'        => fake()->dateTime(),
            'end_time'          => fake()->dateTime(),
            'location'          => fake()->address(),
            'is_all_day'        => fake()->boolean(),
            'recurring'         => fake()->boolean(),
            'recurring_pattern' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'timezone'          => 'Asia/Jakarta',
            'is_overnight'      => fake()->boolean(),
        ];
    }
}
