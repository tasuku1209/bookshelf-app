<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍からユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(
            User::class,
            $book->user
        );

        $this->assertEquals(
            $user->id,
            $book->user->id
        );
    }

    public function test_書籍からジャンルを取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $book->genres()->attach($genres);

        // Act Assert
        $this->assertCount(
            2,
            $book->genres
        );

        $this->assertInstanceOf(
            Genre::class,
            $book->genres->first()
        );
    }

    public function test_書籍からレビューを取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();
        Review::factory()->count(2)->create([
            'book_id' => $book->id,
        ]);

        // Act Assert
        $this->assertCount(
            2,
            $book->reviews
        );

        $this->assertInstanceOf(
            Review::class,
            $book->reviews->first()
        );
    }

    public function test_書籍からお気に入りしているユーザーを取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $users = User::factory()->count(2)->create();
        $book->favoritedUsers()->attach($users);

        // Act Assert
        $this->assertCount(
            2,
            $book->favoritedUsers
        );

        $this->assertInstanceOf(
            User::class,
            $book->favoritedUsers->first()
        );
    }
}
