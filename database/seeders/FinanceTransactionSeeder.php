<?php

namespace Database\Seeders;

use App\Models\FinanceTransaction;
use Illuminate\Database\Seeder;

class FinanceTransactionSeeder extends Seeder
{
    public function run(): void
    {
        FinanceTransaction::factory()->count(5)->create();
    }
}
