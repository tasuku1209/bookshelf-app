<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validReviewData(array $overrides = []): array
    {
        return array_merge([
            'rating' => 5,
            'comment' => 'テストレビュー',
        ], $overrides);
    }

    public function test_認証済みユーザーはレビューを登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validReviewData();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', 'レビューを投稿しました');

        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);
    }

    public function test_ゲストユーザーはレビューを登録できない(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $data = $this->validReviewData();

        // Act
        $response = $this
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'comment' => $data['comment'],
        ]);
    }

    public function test_必須項目未入力では登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validReviewData([
            'comment' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'comment',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_数値形式でないレーティングでは登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validReviewData([
            'rating' => 'abc',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_1から5以外のレーティングでは登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validReviewData([
            'rating' => 6,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_文字数超過では登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validReviewData([
            'comment' => str_repeat('あ', 1001),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'comment',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_認証済みかつ自分のレビューで編集画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'comment' => 'レビューコメント',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        // Assert
        $response->assertOk();

        $response->assertSee('レビューコメント');
    }

    public function test_ゲストユーザーはレビュー編集画面を表示できない(): void
    {
        // Arrange
        $review = Review::factory()->create();

        // Act
        $response = $this
            ->get(route('reviews.edit', $review));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みでも他人のレビューは編集画面を表示できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->get(route('reviews.edit', $review));

        // Assert
        $response->assertForbidden();
    }

    public function test_認証済みかつ自分のレビューを更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'comment' => '更新前レビュー',
        ]);

        $data = $this->validReviewData([
            'comment' => '更新後レビュー',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        // Assert
        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', 'レビューを更新しました');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '更新後レビュー',
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_ゲストユーザーはレビューを更新できない(): void
    {
        // Arrange
        $review = Review::factory()->create([
            'comment' => '更新前レビュー',
        ]);

        $data = $this->validReviewData([
            'comment' => '更新後レビュー',
        ]);

        // Act
        $response = $this
            ->put(route('reviews.update', $review), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '更新前レビュー',
        ]);
    }

    public function test_認証済みでも他人のレビューは更新できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'comment' => '更新前レビュー',
        ]);

        $data = $this->validReviewData([
            'comment' => '更新後レビュー',
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $review), $data);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '更新前レビュー',
        ]);
    }

    public function test_必須項目未入力では更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'comment' => '更新前レビュー',
        ]);

        $data = $this->validReviewData([
            'comment' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        // Assert
        $response->assertSessionHasErrors([
            'comment',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '更新前レビュー',
        ]);
    }

    public function test_認証済みかつ自分のレビューを削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        // Assert
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', 'レビューを削除しました');
    }

    public function test_ゲストユーザーはレビューを削除できない(): void
    {
        // Arrange
        $review = Review::factory()->create();

        // Act
        $response = $this
            ->delete(route('reviews.destroy', $review));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_認証済みでも他人のレビューは削除できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
