<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProfileSeeder::class,
            PortfolioSeeder::class,
            SkillSeeder::class,
            ProjectSeeder::class,
            TaskSeeder::class,
            ScheduleSeeder::class,
            ReminderSeeder::class,
            FinanceCategorySeeder::class,
            FinanceTransactionSeeder::class,
            WorkShiftSeeder::class,
            BlogSeeder::class,
            ContactSeeder::class,
            NotificationSeeder::class,
            GoalSeeder::class,
            DebtSeeder::class,
            SubscriptionSeeder::class,
            AccountSeeder::class,
            BudgetSeeder::class,
            SummarySeeder::class,
        ]);
    }
}
