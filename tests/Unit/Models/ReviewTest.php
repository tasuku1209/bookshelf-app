<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビューからユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(
            User::class,
            $review->user
        );

        $this->assertEquals(
            $user->id,
            $review->user->id
        );
    }

    public function test_レビューから書籍を取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(
            Book::class,
            $review->book
        );

        $this->assertEquals(
            $book->id,
            $review->book->id
        );
    }

    public function test_レビューからいいねしたユーザーを取得できる(): void
    {
        // Arrange
        $review = Review::factory()->create();
        $users = User::factory()
            ->count(2)
            ->create();
        $review->likedByUsers()
            ->attach($users);

        // Act Assert
        $this->assertCount(
            2,
            $review->likedByUsers
        );

        $this->assertInstanceOf(
            User::class,
            $review->likedByUsers->first()
        );
    }
}
