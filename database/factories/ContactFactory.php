<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name'    => fake()->name(),
            'email'   => fake()->unique()->safeEmail(),
            'phone'   => fake()->optional()->phoneNumber(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status'  => fake()->randomElement(['new', 'read', 'replied']),
        ];
    }
}
