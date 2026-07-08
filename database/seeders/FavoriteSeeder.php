<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($users as $user) {

            $favoriteBookIds = $books
                ->random(fake()->numberBetween(3, 5))
                ->pluck('id')
                ->toArray();

            $user->favoriteBooks()
                ->syncWithoutDetaching($favoriteBookIds);
        }
    }
}
