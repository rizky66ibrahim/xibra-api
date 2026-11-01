<?php

namespace Database\Seeders;

use App\Models\Summary;
use Illuminate\Database\Seeder;

class SummarySeeder extends Seeder
{
    public function run(): void
    {
        Summary::factory()->count(5)->create();
    }
}
