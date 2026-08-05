<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
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

    public function test_書籍から読書計画を取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        ReadingPlan::factory()
            ->count(2)
            ->create([
                'book_id' => $book->id,
            ]);

        // Act & Assert
        $this->assertCount(
            2,
            $book->readingPlans
        );

        $this->assertInstanceOf(
            ReadingPlan::class,
            $book->readingPlans->first()
        );
    }

    public function test_キーワード検索でタイトルの部分一致検索ができる(): void
    {
        // Arrange
        $target = Book::factory()->create([
            'title' => 'タイトルAの書籍',
            'author' => 'author',
        ]);

        $other = Book::factory()->create([
            'title' => 'タイトルBの書籍',
            'author' => 'author',
        ]);

        // Act
        $result = Book::query()
            ->keyword('タイトルA')
            ->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $target->id));
    }

    public function test_キーワード検索で著者の部分一致検索ができる(): void
    {
        // Arrange
        $target = Book::factory()->create([
            'title' => 'title',
            'author' => '著者Aの書籍',
        ]);

        $other = Book::factory()->create([
            'title' => 'title',
            'author' => '著者Bの書籍',
        ]);

        // Act
        $result = Book::query()
            ->keyword('著者A')
            ->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $target->id));
    }

    public function test_ジャンル検索で指定したジャンルの書籍を取得できる(): void
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
        $result = Book::query()
            ->genre($genre1->id)
            ->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $target->id));
    }

    public function test_ジャンル検索で1冊に複数ジャンルが設定されていても取得できる(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book = Book::factory()->create();
        $book->genres()->attach([$genre1->id, $genre2->id]);

        // Act
        $result = Book::query()
            ->genre($genre2->id)
            ->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $book->id));
    }

    public function test_並び替えでnewestを指定すると新しい順に取得できる(): void
    {
        // Arrange
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $result = Book::query()
            ->sort('newest')
            ->get();

        // Assert
        $this->assertSame(
            [$newBook->id, $oldBook->id],
            $result->pluck('id')->all()
        );
    }

    public function test_並び替えでoldestを指定すると古い順に取得できる(): void
    {
        // Arrange
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $result = Book::query()
            ->sort('oldest')
            ->get();

        // Assert
        $this->assertSame(
            [$oldBook->id, $newBook->id],
            $result->pluck('id')->all()
        );
    }

    public function test_並び替えでtitleを指定するとタイトル順に取得できる(): void
    {
        // Arrange
        $bookB = Book::factory()->create([
            'title' => 'タイトルBの書籍',
        ]);

        $bookA = Book::factory()->create([
            'title' => 'タイトルAの書籍',
        ]);

        // Act
        $result = Book::query()
            ->sort('title')
            ->get();

        // Assert
        $this->assertSame(
            [$bookA->id, $bookB->id],
            $result->pluck('id')->all()
        );
    }

    public function test_並び替えでratingを指定すると評価順に取得できる(): void
    {
        // Arrange
        $lowRatedBook = Book::factory()->create();

        $highRatedBook = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        // Act
        $result = Book::query()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->sort('rating')
            ->get();

        // Assert
        $this->assertSame(
            [$highRatedBook->id, $lowRatedBook->id],
            $result->pluck('id')->all()
        );
    }
}
