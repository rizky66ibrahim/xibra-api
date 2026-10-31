<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            'Database\\Seeders\\UserSeeder',
            'Database\\Seeders\\ProfileSeeder',
            'Database\\Seeders\\PortfolioSeeder',
            'Database\\Seeders\\SkillSeeder',
            'Database\\Seeders\\ProjectSeeder',
            'Database\\Seeders\\TaskSeeder',
            'Database\\Seeders\\ScheduleSeeder',
            'Database\\Seeders\\ReminderSeeder',
            'Database\\Seeders\\FinanceCategorySeeder',
            'Database\\Seeders\\FinanceTransactionSeeder',
            'Database\\Seeders\\WorkShiftSeeder',
            'Database\\Seeders\\BlogSeeder',
            'Database\\Seeders\\ContactSeeder',
            'Database\\Seeders\\NotificationSeeder',
        ]);
    }
}
