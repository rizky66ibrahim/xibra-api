<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinanceTransactionFactory extends Factory
{
    protected $model = FinanceTransaction::class;

    public function definition(): array
    {
        return [
            'user_id'                => User::factory(),
            'finance_category_id'    => FinanceCategory::factory(),
            'type'                   => fake()->randomElement(['income', 'expense']),
            'amount'                 => fake()->randomFloat(2, 1000, 10000000),
            'currency'               => 'IDR',
            'description'            => fake()->sentence(),
            'transaction_date'       => fake()->date(),
            'reference_number'       => fake()->optional()->slug(),
        ];
    }
}
