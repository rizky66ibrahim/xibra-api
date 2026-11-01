<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'name'      => fake()->words(2, true),
            'type'      => fake()->randomElement(['cash', 'bank', 'ewallet']),
            'provider'  => fake()->randomElement(['BCA', 'ShopeePay', 'GoPay', 'Dana', 'OVO', 'LinkAja', 'Jenius', 'BRI', 'Mandiri', 'BNI', 'CIMB Niaga', 'Permata Bank', 'Bank Syariah Indonesia']),
            'balance'   => fake()->randomFloat(2, 0, 10000000),
            'currency'  => 'IDR',
            'is_active' => fake()->boolean(),
        ];
    }
}
