<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validBookData(Genre $genre, array $overrides = []): array
    {
        return array_merge([
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '1111111111111',
            'description' => 'テスト説明',
            'published_date' => '2026-01-01',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_isbn検索でisbnに該当する書籍情報を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $isbn = '1234567890123';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'タイトル',
                            'authors' => ['著者'],
                            'publishedDate' => '2026-01-01',
                            'description' => '書籍の説明書き',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/image.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $response = $this->actingAs($user)->getJson("/books/isbn/{$isbn}");

        // Assert
        $response->assertStatus(200);

        $response->assertJson([
            'title' => 'タイトル',
            'author' => '著者',
            'published_date' => '2026-01-01',
            'description' => '書籍の説明書き',
            'image_url' => 'https://example.com/image.jpg',
        ]);
    }

    public function test_isbn検索で未認証の場合はログイン画面へリダイレクトされる(): void
    {
        // Arrange
        $isbn = '1234567890123';

        // Act
        $response = $this->get("/books/isbn/{$isbn}");

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_isb_n検索で該当書籍がない場合は404を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        $isbn = '1234567890123';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get("/books/isbn/{$isbn}");

        // Assert
        $response->assertStatus(404);

        $response->assertJson([
            'error' => '書籍情報が見つかりませんでした。',
        ]);
    }

    public function test_isbn検索でエラーとなった場合は500を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        $isbn = '1234567890123';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get("/books/isbn/{$isbn}");

        // Assert
        $response->assertStatus(500);

        $response->assertJson([
            'error' => '現在、書籍情報を取得できません。しばらくしてからお試しください。',
        ]);
    }

    public function test_isbn検索でapi通信の例外が発生した場合は500を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        $isbn = '1234567890123';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => function () {
                throw new \Exception('API通信エラー');
            },
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get("/books/isbn/{$isbn}");

        // Assert
        $response->assertStatus(500);

        $response->assertJson([
            'error' => '現在、書籍情報を取得できません。しばらくしてからお試しください。',
        ]);
    }

    public function test_書籍一覧を表示できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => 'ジャンル',
        ]);

        $book = Book::factory()->create([
            'title' => 'タイトル',
        ]);

        $book->genres()->attach($genre);

        // Act
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('タイトル');
        $response->assertSee('ジャンル');
    }

    public function test_書籍一覧で書籍が最新順で表示される(): void
    {
        // Arrange
        Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        // Act
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい本',
            '古い本',
        ]);
    }

    public function test_書籍一覧の評価順ソートで書籍のレートが同率の場合、レビュー件数順で表示する(): void
    {
        // Arrange
        $book1 = Book::factory()->create([
            'title' => 'レビュー数の少ない書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'レビュー数の多い書籍',
        ]);

        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        // Act
        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeInOrder([
            'レビュー数の多い書籍',
            'レビュー数の少ない書籍',
        ]);
    }

    public function test_書籍一覧の評価順ソートで書籍のレート、レビュー件数が同率の場合、書籍最新順で表示する(): void
    {
        // Arrange
        $book1 = Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDay(),
        ]);

        $book2 = Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        // Act
        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_書籍一覧で、キーワード・ジャンル・並び順を組み合わせて検索できる(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book1 = Book::factory()->create([
            'title' => 'ジャンル1の検索対象の新しい書籍',
            'author' => 'author',
            'created_at' => now(),
        ]);
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create([
            'title' => 'ジャンル1の検索対象の古い書籍',
            'author' => 'author',
            'created_at' => now()->subDay(),
        ]);
        $book2->genres()->attach($genre1);

        $book3 = Book::factory()->create([
            'title' => 'ジャンル1のtitle',
            'author' => 'author',
        ]);
        $book3->genres()->attach($genre1);

        $book4 = Book::factory()->create([
            'title' => 'ジャンル2のtitle',
            'author' => 'author',
        ]);
        $book4->genres()->attach($genre2);

        // Act
        $response = $this->get(route('books.index', [
            'keyword' => '検索対象',
            'genre' => $genre1->id,
            'sort' => 'oldest',
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeInOrder([
            'ジャンル1の検索対象の古い書籍',
            'ジャンル1の検索対象の新しい書籍',
        ]);
        $response->assertDontSee('ジャンル1のtitle');
        $response->assertDontSee('ジャンル2のtitle');
    }

    public function test_書籍一覧の検索機能でキーワードが文字数超過では検索できない(): void
    {
        // Act
        $response = $this->get(route('books.index', [
            'keyword' => str_repeat('a', 256),
        ]));

        // Assert
        $response->assertSessionHasErrors('keyword');
    }

    public function test_書籍一覧の検索機能でジャンルが数値形式でない場合は検索できない(): void
    {
        // Act
        $response = $this->get(route('books.index', [
            'genre' => 'abc',
        ]));

        // Assert
        $response->assertSessionHasErrors('genre');
    }

    public function test_書籍一覧の検索機能で存在しないジャンルでは検索できない(): void
    {
        // Act
        $response = $this->get(route('books.index', [
            'genre' => 999,
        ]));

        // Assert
        $response->assertSessionHasErrors('genre');
    }

    public function test_書籍一覧の検索機能で許可されていない並び順では検索できない(): void
    {
        // Act
        $response = $this->get(route('books.index', [
            'sort' => 'invalid',
        ]));

        // Assert
        $response->assertSessionHasErrors('sort');
    }

    public function test_書籍一覧は10件でページネーションされる(): void
    {
        // Arrange
        Book::factory()->count(11)->create();

        // Act
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertEquals(10, $books->count());
        $this->assertEquals(11, $books->total());
    }

    public function test_書籍一覧で2ページ目に進んでも検索条件が維持される(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $books = Book::factory()->count(10)->create([
            'title' => '検索対象の古い書籍',
            'created_at' => now()->subDay(),
        ]);
        foreach ($books as $book) {
            $book->genres()->attach($genre1);
        }

        $target = Book::factory()->create([
            'title' => 'ジャンル1の検索対象の新しい本',
            'created_at' => now(),
        ]);
        $target->genres()->attach($genre1);

        $other1 = Book::factory()->create([
            'title' => 'ジャンル1の新しい本',
            'created_at' => now(),
        ]);
        $other1->genres()->attach($genre1);

        $other2 = Book::factory()->create([
            'title' => 'ジャンル2の新しい本',
            'created_at' => now(),
        ]);
        $other2->genres()->attach($genre2);

        // Act
        $response = $this->get(route('books.index', [
            'keyword' => '検索対象',
            'genre' => $genre1->id,
            'sort' => 'oldest',
            'page' => 2,
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee('ジャンル1の検索対象の新しい本');
        $response->assertDontSee('ジャンル1の新しい本');
        $response->assertDontSee('ジャンル2の新しい本');
    }

    public function test_書籍詳細を表示できる(): void
    {
        // Arrange
        $reviewUser = User::factory()->create([
            'name' => 'レビュー投稿者氏名',
        ]);

        $genre = Genre::factory()->create([
            'name' => 'ジャンル',
        ]);

        $book = Book::factory()->create([
            'title' => 'タイトル',
        ]);

        $book->genres()->attach($genre);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'comment' => 'コメント',
        ]);

        // Act
        $response = $this->get(route('books.show', $book));

        // Assert
        $response->assertOk();

        $response->assertSee('タイトル');
        $response->assertSee('ジャンル');
        $response->assertSee('コメント');
        $response->assertSee('レビュー投稿者氏名');
    }

    public function test_認証済みユーザーは書籍登録画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Genre::factory()->create([
            'name' => 'ジャンル',
        ]);
        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.create'));

        // Assert
        $response->assertOk();
        $response->assertSee('ジャンル');
    }

    public function test_ゲストユーザーは書籍登録画面を表示できない(): void
    {
        // Act
        $response = $this->get(route('books.create'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは書籍を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'isbn' => '1111111111111',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $this->assertDatabaseHas('books', [
            'isbn' => '1111111111111',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', '1111111111111')->first();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', '書籍を登録しました');
    }

    public function test_isbn、出版日を空白とした書籍でも登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'title' => 'タイトル',
            'isbn' => '',
            'published_date' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'タイトル',
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_ゲストユーザーは書籍を登録できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'isbn' => '1111111111111',
        ]);

        // Act
        $response = $this->post(route('books.store'), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'isbn' => '1111111111111',
        ]);
    }

    public function test_タイトルが未入力の場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'title',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_著者が文字数超過の場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'author' => str_repeat('あ', 256),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'author',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_isbnが13桁でない場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'isbn' => '123',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'isbn',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_出版日が日付形式でない場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'published_date' => 'not-a-date',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'published_date',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_画像urlがurl形式でない場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'image_url' => 'not-a-url',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'image_url',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_選択ジャンルが存在しない場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = $this->validBookData($genre, [
            'genres' => [99],
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'genres.0',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_isbnが重複する場合は登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $data = $this->validBookData($genre, [
            'isbn' => '1111111111111',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'isbn',
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_認証済みかつ自分の登録書籍で書籍編集画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ジャンル',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'タイトル',
        ]);

        $book->genres()->attach($genre);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.edit', $book));

        // Assert
        $response->assertOk();
        $response->assertSee('タイトル');
        $response->assertSee('ジャンル');
    }

    public function test_ゲストユーザーは編集画面を表示できない(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->get(route('books.edit', $book));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みでも他人の書籍は編集画面を表示できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->get(route('books.edit', $book));

        // Assert
        $response->assertForbidden();
    }

    public function test_認証済みかつ自分の書籍で更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $oldGenre = Genre::factory()->create([
            'name' => '旧ジャンル',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => '新ジャンル',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        $book->genres()->attach($oldGenre);

        $data = $this->validBookData($newGenre, [
            'title' => '更新後タイトル',
            'isbn' => '1111111111111', // 自分自身のISBNなので更新可能
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', '書籍を更新しました');
    }

    public function test_isbn、出版日を空白とした書籍でも更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre);

        $data = $this->validBookData($genre, [
            'title' => 'タイトル',
            'isbn' => '',
            'published_date' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'タイトル',
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_ゲストユーザーは書籍を更新できない(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'title' => '更新前タイトル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '更新後タイトル',
        ]);

        // Act
        $response = $this->put(route('books.update', $book), $data);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_認証済みでも他人の書籍は更新できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '更新後タイトル',
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_タイトルが未入力の場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'title',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_著者が文字数超過の場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'author' => '更新前著者',
        ]);

        $data = $this->validBookData($genre, [
            'author' => str_repeat('あ', 256),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'author',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'author' => '更新前著者',
        ]);
    }

    public function test_isbnが13桁でない場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        $data = $this->validBookData($genre, [
            'isbn' => '123',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'isbn',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '1111111111111',
        ]);
    }

    public function test_出版日が日付形式でない場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'published_date' => '2026-01-01',
        ]);

        $data = $this->validBookData($genre, [
            'published_date' => 'not-a-date',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'published_date',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'published_date' => '2026-01-01 00:00:00',
        ]);
    }

    public function test_画像urlがurl形式でない場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $data = $this->validBookData($genre, [
            'image_url' => 'not-a-url',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'image_url',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'image_url' => 'https://example.com/image.jpg',
        ]);
    }

    public function test_選択ジャンルが存在しない場合は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre);

        $data = $this->validBookData($genre, [
            'genres' => [99],
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'genres.0',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_重複するisbnは更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        // 更新対象
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'isbn' => '1111111111111',
        ]);

        // 重複先
        Book::factory()->create([
            'isbn' => '2222222222222',
        ]);

        $data = $this->validBookData($genre, [
            'isbn' => '2222222222222',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $data);

        // Assert
        $response->assertSessionHasErrors([
            'isbn',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '1111111111111',
        ]);
    }

    public function test_認証済みかつ自分の書籍を削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('books.destroy', $book));

        // Assert
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas('success', '書籍を削除しました');
    }

    public function test_ゲストユーザーは書籍を削除できない(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->delete(route('books.destroy', $book));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_認証済みでも他人の書籍は削除できない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
