<?php

namespace Database\Seeders;

use App\Models\Reminder;
use Illuminate\Database\Seeder;

class ReminderSeeder extends Seeder
{
    public function run(): void
    {
        Reminder::factory()->count(10)->create();
    }
}
