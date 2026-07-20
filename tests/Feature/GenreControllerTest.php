<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはジャンル一覧画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ジャンル',
        ]);

        Book::factory()->count(2)->create()->each(function ($book) use ($genre) {
            $book->genres()->attach($genre);
        });

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        // Assert
        $response->assertOk();

        $response->assertSee('ジャンル');
        $response->assertSee('2');
    }

    public function test_ジャンルがname順で表示される(): void
    {
        // Arrange
        Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        Genre::factory()->create([
            'name' => 'ジャンル3',
        ]);

        Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        // Act
        $response = $this->actingAs(User::factory()->create())
            ->get(route('genres.index'));

        // Assert
        $response->assertOk();

        $response->assertSeeInOrder([
            'ジャンル1',
            'ジャンル2',
            'ジャンル3',
        ]);
    }

    public function test_ゲストユーザーはジャンル一覧画面を表示できない(): void
    {
        // Act
        $response = $this->get(route('genres.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル詳細画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $book = Book::factory()->create([
            'title' => 'タイトル',
        ]);

        $book->genres()->attach([
            $genre1->id,
            $genre2->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre1));

        // Assert
        $response->assertOk();

        $response->assertSee('ジャンル1');
        $response->assertSee('タイトル');
        $response->assertSee('ジャンル2');
    }

    public function test_ジャンル詳細の書籍一覧は10件でページネーションされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        Book::factory()->count(11)->create()->each(function ($book) use ($genre) {
            $book->genres()->attach($genre);
        });

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        // Assert
        $response->assertOk();
        $response->assertViewHas('books');

        $books = $response->viewData('books');

        $this->assertEquals(10, $books->count());
        $this->assertEquals(11, $books->total());
    }

    public function test_ゲストユーザーはジャンル詳細画面を表示できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        // Act
        $response = $this->get(route('genres.show', $genre));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル登録画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.create'));

        // Assert
        $response->assertOk();
    }

    public function test_ゲストユーザーはジャンル登録画面を表示できない(): void
    {
        // Act
        $response = $this->get(route('genres.create'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンルを登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'name' => 'テストジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), $data);

        // Assert
        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('success', 'ジャンルを登録しました');

        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
            'user_id' => $user->id,
        ]);
    }

    public function test_ゲストユーザーはジャンルを登録できない(): void
    {
        // Arrange
        $data = [
            'name' => 'テストジャンル',
        ];

        // Act
        $response = $this
            ->post(route('genres.store'), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('genres', [
            'name' => 'テストジャンル',
        ]);
    }

    public function test_ジャンル名が未入力の場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'name' => '',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseMissing('genres', [
            'name' => '',
        ]);
    }

    public function test_ジャンル名が文字数超過の場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'name' => str_repeat('あ', 256),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseMissing('genres', [
            'name' => $data['name'],
        ]);
    }

    public function test_重複するジャンル名は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'ジャンル',
        ]);

        $data = [
            'name' => 'ジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_認証済みかつ自分の登録ジャンルで編集画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => 'ジャンル',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre));

        // Assert
        $response->assertOk();
        $response->assertSee('ジャンル');
    }

    public function test_ゲストユーザーはジャンル編集画面を表示できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        // Act
        $response = $this->get(route('genres.edit', $genre));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みでも他人の登録ジャンルは編集画面を表示できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->get(route('genres.edit', $genre));

        // Assert
        $response->assertForbidden();
    }

    public function test_認証済みかつ自分の登録ジャンルを更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => '更新前ジャンル',
        ]);

        $data = [
            'name' => '更新後ジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('success', 'ジャンルを更新しました');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);
    }

    public function test_自分自身のジャンル名なら重複しても更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => 'ジャンル（重複可）',
        ]);

        $data = [
            'name' => 'ジャンル（重複可）',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('success', 'ジャンルを更新しました');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル（重複可）',
        ]);
    }

    public function test_ゲストユーザーはジャンルを更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $data = [
            'name' => '更新後ジャンル',
        ];

        // Act
        $response = $this
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_認証済みでも他人の登録ジャンルは更新できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $owner->id,
            'name' => '更新前ジャンル',
        ]);

        $data = [
            'name' => '更新後ジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名が未入力の場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => '更新前ジャンル',
        ]);

        $data = [
            'name' => '',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名が文字数超過の場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => '更新前ジャンル',
        ]);

        $data = [
            'name' => str_repeat('あ', 256),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_重複するジャンル名は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        // 更新対象
        $genre = Genre::factory()->create([
            'user_id' => $user->id,
            'name' => 'ジャンル',
        ]);

        // 重複先
        Genre::factory()->create([
            'name' => '重複ジャンル',
        ]);

        $data = [
            'name' => '重複ジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        // Assert
        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル',
        ]);
    }

    public function test_認証済みかつ自分の登録ジャンルを削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // Assert
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('success', 'ジャンルを削除しました');
    }

    public function test_認証済みかつ自分の登録ジャンルでも紐づく書籍がある場合は削除できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $user->id,
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // Assert
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('error', 'このジャンルには書籍が登録されているため削除できません');
    }

    public function test_ゲストユーザーはジャンルを削除できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        // Act
        $response = $this
            ->delete(route('genres.destroy', $genre));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_認証済みでも他人の登録ジャンルは削除できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->delete(route('genres.destroy', $genre));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
