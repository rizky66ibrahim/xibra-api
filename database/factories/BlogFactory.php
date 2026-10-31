<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        $title = fake()->sentence();
        return [
            'user_id'       => User::factory(),
            'title'         => $title,
            'slug'          => Str::slug($title),
            'excerpt'       => fake()->sentence(),
            'content'       => fake()->paragraphs(3, true),
            'status'        => fake()->randomElement(['draft', 'published']),
            'published_at'  => fake()->optional()->dateTime(),
            'featured_image' => fake()->optional()->imageUrl(),
        ];
    }
}
