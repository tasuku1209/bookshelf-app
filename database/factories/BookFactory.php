<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3, true),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('#############'),
            'published_date' => fake()->date(),
            'description' => fake()->realText(800),
            'image_url' => fake()->imageUrl(),
        ];
    }
}
