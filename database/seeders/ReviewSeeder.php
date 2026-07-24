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

        foreach ($books as $book) {

            $reviewCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $reviewCount; $i++) {

                $rating = fake()->numberBetween(1, 5);

                Review::create([
                    'user_id' => $users->random()->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => match ($rating) {
                        5 => 'とても良い内容でした。',
                        4 => '満足できる内容でした。',
                        3 => '普通に楽しめました。',
                        2 => '少し物足りませんでした。',
                        1 => '期待していた内容ではありませんでした。',
                    },
                ]);
            }
        }
    }
}
