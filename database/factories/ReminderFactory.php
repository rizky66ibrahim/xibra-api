<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'title'             => fake()->sentence(),
            'description'       => fake()->paragraph(),
            'reminder_at'       => fake()->dateTime(),
            'recurring'         => fake()->boolean(),
            'recurring_pattern' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'delivery_status'   => fake()->randomElement(['pending', 'sent', 'failed']),
            'dedupe_key'        => fake()->unique()->slug(),
            'is_sent'           => fake()->boolean(),
            'sent_at'           => null,
        ];
    }
}
