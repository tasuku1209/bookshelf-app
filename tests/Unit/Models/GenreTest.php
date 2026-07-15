<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_ジャンルからユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(
            User::class,
            $genre->user
        );

        $this->assertEquals(
            $user->id,
            $genre->user->id
        );
    }

    public function test_ジャンルから書籍を取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $books = Book::factory()
            ->count(2)
            ->create();
        $genre->books()
            ->attach($books);

        // Act Assert
        $this->assertCount(
            2,
            $genre->books
        );

        $this->assertInstanceOf(
            Book::class,
            $genre->books->first()
        );
    }
}
