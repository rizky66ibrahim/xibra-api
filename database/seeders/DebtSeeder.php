<?php

namespace Database\Seeders;

use App\Models\Debt;
use Illuminate\Database\Seeder;

class DebtSeeder extends Seeder
{
    public function run(): void
    {
        Debt::factory()->count(5)->create();
    }
}
