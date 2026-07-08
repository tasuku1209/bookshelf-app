<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $totalReviews = 32;

        $userCount = $users->count();
        $bookCount = $books->count();

        for ($i = 0; $i < $totalReviews; $i++) {

            Review::create([
                'user_id' => $users[$i % $userCount]->id,
                'book_id' => $books[$i % $bookCount]->id,
                'rating' => fake()->numberBetween(3, 5),
                'comment' => fake()->realText(150),
            ]);
        }
    }
}
