<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkShiftFactory extends Factory
{
    protected $model = WorkShift::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'shift_name'   => fake()->words(2, true),
            'start_time'   => '08:00:00',
            'end_time'     => '16:00:00',
            'break_minutes' => fake()->numberBetween(0, 120),
            'work_date'    => fake()->date(),
            'is_active'    => fake()->boolean(),
        ];
    }
}
