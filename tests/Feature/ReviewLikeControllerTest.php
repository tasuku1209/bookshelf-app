<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはレビューにいいね登録と解除と再登録ができる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $review = Review::factory()->create();

        // Act1 いいね登録
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $review->book))
            ->post(route('reviews.like', $review));

        // Assert1
        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // Act2 いいね解除
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $review->book))
            ->post(route('reviews.like', $review));

        // Assert2
        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // Act3 再度いいね登録
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $review->book))
            ->post(route('reviews.like', $review));

        // Assert3
        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_ゲストユーザーはレビューにいいね登録解除できない(): void
    {
        // Arrange
        $review = Review::factory()->create();

        // Act
        $response = $this
            ->post(route('reviews.like', $review));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }
}
