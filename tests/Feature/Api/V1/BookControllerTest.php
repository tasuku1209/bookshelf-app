<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validBookData(Genre $genre, array $overrides = []): array
    {
        return array_merge([
            'user_id' => User::factory()->create()->id,
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '1111111111111',
            'description' => 'テスト説明',
            'published_date' => '2026-01-01',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_書籍一覧を取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 1,
        ]);

        // Act
        $response = $this->getJson(route('api.v1.books.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.average_rating', 3);
        $response->assertJsonPath('data.0.review_count', 2);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'image_url',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'average_rating',
                    'review_count',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_書籍が最新順で返る(): void
    {
        // Arrange
        $oldBook = Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.books.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonPath('data.0.title', '新しい本');
        $response->assertJsonPath('data.1.title', '古い本');
    }

    public function test_タイトルで検索できる(): void
    {
        // Arrange
        $target = Book::factory()->create([
            'title' => 'タイトルA',
        ]);

        $other = Book::factory()->create([
            'title' => 'タイトルB',
        ]);

        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'keyword' => 'タイトルA',
        ]));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'title' => 'タイトルA',
        ]);

        $response->assertJsonMissing([
            'title' => 'タイトルB',
        ]);
    }

    public function test_著者で検索できる(): void
    {
        // Arrange
        $target = Book::factory()->create([
            'author' => '著者A',
        ]);

        $other = Book::factory()->create([
            'author' => '著者B',
        ]);

        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'keyword' => '著者A',
        ]));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'author' => '著者A',
        ]);

        $response->assertJsonMissing([
            'author' => '著者B',
        ]);
    }

    public function test_ジャンルで絞り込み検索できる(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create();

        $genre2 = Genre::factory()->create();

        $target = Book::factory()->create([
            'title' => 'ジャンル1の書籍',
        ]);
        $target->genres()->attach($genre1);

        $other = Book::factory()->create([
            'title' => 'ジャンル2の書籍',
        ]);
        $other->genres()->attach($genre2);

        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'genre_id' => $genre1->id,
        ]));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'title' => 'ジャンル1の書籍',
        ]);

        $response->assertJsonMissing([
            'title' => 'ジャンル2の書籍',
        ]);
    }

    public function test_キーワード検索とジャンル検索を組み合わせて検索できる(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create();

        $genre2 = Genre::factory()->create();

        $target = Book::factory()->create([
            'title' => 'ジャンル1のタイトルA',
        ]);
        $target->genres()->attach($genre1);

        $book1 = Book::factory()->create([
            'title' => 'ジャンル1のタイトルB',
        ]);
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create([
            'title' => 'ジャンル2のタイトルA',
        ]);
        $book2->genres()->attach($genre2);

        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'keyword' => 'タイトルA',
            'genre_id' => $genre1->id,
        ]));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');

        $response->assertJsonFragment([
            'title' => 'ジャンル1のタイトルA',
        ]);
    }

    public function test_20件でページネーションされる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        Book::factory()
            ->count(21)
            ->create()
            ->each(function ($book) use ($genre) {
                $book->genres()->attach($genre);
            });

        // Act
        $response = $this->getJson(route('api.v1.books.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonCount(20, 'data');

        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 21);
    }

    public function test_キーワードが文字数超過では検索できない(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'keyword' => str_repeat('あ', 256),
        ]));

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'keyword',
        ]);
    }

    public function test_ジャンルが数値形式でない場合は検索できない(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'genre_id' => 'abc',
        ]));

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genre_id',
        ]);
    }

    public function test_存在しないジャンルでは検索できない(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'genre_id' => 99,
        ]));

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genre_id',
        ]);
    }

    public function test_1を下回るページ指定では検索できない(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'page' => 0,
        ]));

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'page',
        ]);
    }

    public function test_表示件数が100を超える場合検索できない(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.books.index', [
            'per_page' => 101,
        ]));

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'per_page',
        ]);
    }

    public function test_書籍詳細を取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        $reviewUser = User::factory()->create();

        $likeUser = User::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'rating' => 5,
        ]);

        $review->likedByUsers()->attach($likeUser);

        // Act
        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        // Assert
        $response->assertStatus(200);

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.reviews.0.id', $review->id);
        $response->assertJsonPath('data.reviews.0.user.id', $reviewUser->id);
        $response->assertJsonPath(
            'data.reviews.0.liked_by_users.0.id',
            $likeUser->id
        );
        $response->assertJsonPath('data.reviews.0.likes_count', 1);
        $response->assertJsonPath('data.average_rating', 5);
        $response->assertJsonPath('data.review_count', 1);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'reviews' => [
                    '*' => [
                        'id',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'rating',
                        'comment',
                        'created_at',
                        'updated_at',
                        'liked_by_users' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                        'likes_count',
                    ],
                ],
                'average_rating',
                'review_count',
            ],
        ]);
    }

    public function test_書籍詳細で存在しない書籍を指定した場合は404エラーが返る(): void
    {
        // Act
        $response = $this->getJson(
            route('api.v1.books.show', 999)
        );

        // Assert
        $response->assertStatus(404);
    }

    public function test_書籍が登録され201が返る(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $user = User::factory()->create();

        $data = $this->validBookData($genre, [
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        // Act
        $response = $this->postJson(
            route('api.v1.books.store'),
            $data
        );

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '1111111111111',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', $data['isbn'])->first();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertJsonFragment([
            'message' => '書籍を登録しました',
        ]);

        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.isbn', '1111111111111');

        $response->assertJsonPath('data.genres.0.id', $genre->id);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_存在しないユーザーでは書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'user_id' => 99,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'user_id',
        ]);
    }

    public function test_必須項目未入力では書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
        ]);
    }

    public function test_文字数超過では書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'author' => str_repeat('あ', 256),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'author',
        ]);
    }

    public function test_13桁でない_isb_nは書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'isbn' => '123',
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);
    }

    public function test_重複する_isb_nは書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $data = $this->validBookData($genre, [
            'isbn' => '1111111111111',
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);
    }

    public function test_不正な日付では書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'published_date' => 'not-a-date',
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'published_date',
        ]);
    }

    public function test_不正な_ur_lでは書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'image_url' => 'not-a-url',
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'image_url',
        ]);
    }

    public function test_存在しないジャンルでは書籍が登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'genres' => [99],
        ]);

        // Act
        $response = $this->postJson(route('api.v1.books.store'), $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genres.0',
        ]);
    }

    public function test_書籍が更新され200が返る(): void
    {
        // Arrange
        $oldGenre = Genre::factory()->create();

        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'title' => '更新前タイトル',
            'isbn' => '1111111111111',
        ]);

        $book->genres()->attach($oldGenre);

        $data = $this->validBookData($newGenre, [
            'title' => '更新後タイトル',
            'isbn' => '1111111111111', // 自分自身のISBNなので更新可能
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'isbn' => '1111111111111',
        ]);

        // syncされ、旧ジャンルとの関連が削除されている
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        // 新ジャンルとの関連が登録されている
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $response->assertJsonFragment([
            'message' => '書籍を更新しました',
        ]);

        $response->assertJsonPath('data.title', '更新後タイトル');
        $response->assertJsonPath('data.isbn', '1111111111111');
        $response->assertJsonPath('data.genres.0.id', $newGenre->id);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_書籍更新で存在しない書籍を指定した場合は404エラーが返る(): void
    {
        // Act
        $response = $this->putJson(
            route('api.v1.books.update', 999),
            []
        );

        // Assert
        $response->assertStatus(404);
    }

    public function test_必須項目未入力では書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
        ]);
    }

    public function test_文字数超過では書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'author' => str_repeat('あ', 256),
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'author',
        ]);
    }

    public function test_13桁でない_isb_nは書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'isbn' => '123',
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);
    }

    public function test_重複する_isb_nは書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        Book::factory()->create([
            'isbn' => '2222222222222',
        ]);

        $data = $this->validBookData($genre, [
            'isbn' => '2222222222222',
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);
    }

    public function test_不正な日付では書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'published_date' => 'not-a-date',
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'published_date',
        ]);
    }

    public function test_不正な_ur_lでは書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'image_url' => 'not-a-url',
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'image_url',
        ]);
    }

    public function test_不正しないジャンルでは書籍が更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $data = $this->validBookData($genre, [
            'genres' => [99],
        ]);

        // Act
        $response = $this->putJson(
            route('api.v1.books.update', $book),
            $data
        );

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genres.0',
        ]);
    }

    public function test_書籍が削除され204が返る(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->deleteJson(
            route('api.v1.books.destroy', $book)
        );

        // Assert
        $response->assertStatus(204);

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_存在しない書籍を指定した場合は404エラーが返る(): void
    {
        // Act
        $response = $this->deleteJson(
            route('api.v1.books.destroy', 99)
        );

        // Assert
        $response->assertStatus(404);
    }
}
