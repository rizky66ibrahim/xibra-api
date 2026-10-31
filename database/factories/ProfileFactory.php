<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'bio'          => fake()->sentence(),
            'birth_date'   => fake()->date(),
            'gender'       => fake()->randomElement(['male', 'female']),
            'address'      => fake()->address(),
            'city'         => fake()->city(),
            'province'     => fake()->state(),
            'postal_code'  => fake()->postcode(),
            'country'      => 'Indonesia',
            'website'      => fake()->url(),
            'social_media' => null,
            'timezone'     => 'Asia/Jakarta',
            'language'     => 'id',
            'avatar_url'   => null,
        ];
    }
}
