<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name'                 => fake()->name(),
            'username'             => fake()->unique()->userName(),
            'email'                => fake()->unique()->safeEmail(),
            'phone_number'         => null,
            'profile_image'        => null,
            'role'                 => 'user',
            'password'             => static::$password ??= Hash::make('password'),
            'is_active'            => true,
            'failed_login_attempts' => 0,
            'locked_until'         => null,
            'last_login_at'        => null,
            'email_verified_at'    => now(),
            'remember_token'       => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
