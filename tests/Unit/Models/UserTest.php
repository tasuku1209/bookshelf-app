<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーから書籍を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Book::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // Act Assert
        $this->assertCount(
            2,
            $user->books
        );

        $this->assertInstanceOf(
            Book::class,
            $user->books->first()
        );
    }

    public function test_ユーザーからレビューを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Review::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // Act Assert
        $this->assertCount(
            2,
            $user->reviews
        );

        $this->assertInstanceOf(
            Review::class,
            $user->reviews->first()
        );
    }

    public function test_ユーザーからお気に入りした書籍を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $books = Book::factory()
            ->count(2)
            ->create();
        $user->favoriteBooks()
            ->attach($books);

        // Act Assert
        $this->assertCount(
            2,
            $user->favoriteBooks
        );

        $this->assertInstanceOf(
            Book::class,
            $user->favoriteBooks->first()
        );
    }

    public function test_ユーザーからジャンルを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Genre::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // ActAssert
        $this->assertCount(
            2,
            $user->genres
        );

        $this->assertInstanceOf(
            Genre::class,
            $user->genres->first()
        );
    }

    public function test_ユーザーからいいねしたレビューを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $reviews = Review::factory()
            ->count(2)
            ->create();
        $user->likedReviews()
            ->attach($reviews);

        // Act Assert
        $this->assertCount(
            2,
            $user->likedReviews
        );

        $this->assertInstanceOf(
            Review::class,
            $user->likedReviews->first()
        );
    }
}
