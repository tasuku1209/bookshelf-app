<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはお気に入り一覧画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $favoriteBook = Book::factory()->create([
            'title' => 'お気に入り書籍',
        ]);

        $notFavoriteBook = Book::factory()->create([
            'title' => 'お気に入りしていない書籍',
        ]);

        $user->favoriteBooks()->attach($favoriteBook);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $response->assertOk();

        $response->assertSee('お気に入り書籍');
        $response->assertDontSee('お気に入りしていない書籍');
    }

    public function test_お気に入り書籍がお気に入りした順で表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'title' => '古いお気に入り',
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しいお気に入り',
        ]);

        $user->favoriteBooks()->attach($oldBook, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $user->favoriteBooks()->attach($newBook, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $response->assertOk();

        $response->assertSeeInOrder([
            '新しいお気に入り',
            '古いお気に入り',
        ]);
    }

    public function test_お気に入り一覧は10件でページネーションされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Book::factory()
            ->count(11)
            ->create()
            ->each(function ($book) use ($user) {
                $user->favoriteBooks()->attach($book);
            });

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertEquals(10, $books->count());
        $this->assertEquals(11, $books->total());
    }

    public function test_ゲストユーザーはお気に入り一覧画面を表示できない(): void
    {
        // Act
        $response = $this
            ->get(route('favorites.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはお気に入り登録と解除と再登録ができる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        // Act1 お気に入り登録
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        // Assert1
        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act2 お気に入り解除
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        // Assert2
        $response->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act3 再度お気に入り登録
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        // Assert3
        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_ゲストユーザーはお気に入り登録解除できない(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this
            ->post(route('favorites.toggle', $book));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }
}
