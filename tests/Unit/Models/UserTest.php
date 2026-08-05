<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
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

    public function test_ユーザーから読書計画を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        ReadingPlan::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // ActAssert
        $this->assertCount(
            2,
            $user->readingPlans
        );

        $this->assertInstanceOf(
            ReadingPlan::class,
            $user->readingPlans->first()
        );
    }

    public function test_基本統計でレビュー件数が取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Review::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // Act
        $stats = $user->summaryStats();

        // Assert
        $this->assertSame(2, $stats['total_reviews']);
    }

    public function test_基本統計で読了書籍数を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        ReadingPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $stats = $user->summaryStats();

        // Assert
        $this->assertSame(2, $stats['books_read']);
    }

    public function test_基本統計でレビューの平均評価を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 2,
        ]);

        // Act
        $stats = $user->summaryStats();

        // Assert
        $this->assertSame(3.0, $stats['average_rating']);
    }

    public function test_基本統計でレビューが存在しない場合は0を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $stats = $user->summaryStats();

        // Assert
        $this->assertSame(0, $stats['total_reviews']);
        $this->assertSame(0, $stats['books_read']);
        $this->assertSame(0, $stats['average_rating']);
    }

    public function test_評価分布でレビューを評価値ごとに集計し、レビューがない評価は0を返し、評価1から5を必ず返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 1,
        ]);

        Review::factory()->count(2)->create([
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        Review::factory()->count(3)->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        // Act
        $distribution = $user->ratingDistribution();

        // Assert
        $this->assertSame(
            [1, 0, 2, 0, 3],
            $distribution->values()->all()
        );

        $this->assertCount(5, $distribution);
    }

    public function test_書籍別で評価4以上の書籍だけ取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
        ]);

        // Act
        $result = $user->topRatedBooks();

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($book2->id, $result->first()['id']);
    }

    public function test_書籍別で評価が高い順に取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        // Act
        $result = $user->topRatedBooks();

        // Assert
        $this->assertSame(
            [$book2->id, $book1->id],
            $result->pluck('id')->all()
        );
    }

    public function test_書籍別で同じ評価の場合は更新日時順に取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 4,
            'updated_at' => '2026-08-01 10:00:00',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
            'updated_at' => '2026-08-02 10:00:00',
        ]);

        // Act
        $result = $user->topRatedBooks();

        // Assert
        $this->assertSame(
            [$book2->id, $book1->id],
            $result->pluck('id')->all()
        );
    }

    public function test_書籍別を最大5件まで取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Review::factory()->count(6)->create([
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        // Act
        $result = $user->topRatedBooks();

        // Assert
        $this->assertCount(5, $result);
    }

    public function test_書籍別の戻り値の形式が正しい(): void
    {
        // Arrange
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        // Act
        $result = $user->topRatedBooks();

        // Assert
        $this->assertSame(
            ['id', 'title', 'author', 'rating'],
            array_keys($result->first())
        );
    }

    public function test_ジャンル別のレビュー件数、平均評価を正しく算出できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book1 = Book::factory()->create();
        $book1->genres()->attach($genre);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($genre);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 2,
        ]);

        // Act
        $result = $user->genreRatings();

        // Assert
        $genreResult = $result->firstWhere('id', $genre->id);

        $this->assertSame(2, $genreResult['count']);
        $this->assertSame(3, $genreResult['average_rating']);
    }

    public function test_ジャンル別で1書籍が複数ジャンルの場合、各ジャンルに集計される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book = Book::factory()->create();
        $book->genres()->attach([
            $genre1->id,
            $genre2->id,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        // Act
        $result = $user->genreRatings();

        // Assert
        $genre1Result = $result->firstWhere('id', $genre1->id);
        $genre2Result = $result->firstWhere('id', $genre2->id);

        $this->assertNotNull($genre1Result);
        $this->assertNotNull($genre2Result);

        $this->assertSame(4, $genre1Result['average_rating']);
        $this->assertSame(4, $genre2Result['average_rating']);

        $this->assertSame(1, $genre1Result['count']);
        $this->assertSame(1, $genre2Result['count']);
    }

    public function test_ジャンル別で平均評価が高い順に取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book1 = Book::factory()->create();
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($genre2);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        // Act
        $result = $user->genreRatings();

        // Assert
        $this->assertSame(
            [$genre2->id, $genre1->id],
            $result->pluck('id')->all()
        );
    }

    public function test_ジャンル別で同じ平均評価の場合は更新日時順に取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book1 = Book::factory()->create();
        $book1->genres()->attach($genre1);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($genre2);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 4,
            'updated_at' => '2026-08-01 10:00:00',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
            'updated_at' => '2026-08-02 10:00:00',
        ]);

        // Act
        $result = $user->genreRatings();

        // Assert
        $this->assertSame(
            [$genre2->id, $genre1->id],
            $result->pluck('id')->all()
        );
    }

    public function test_ジャンル別を最大5件まで取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genres = Genre::factory()->count(6)->create();

        foreach ($genres as $genre) {
            $book = Book::factory()->create();

            $book->genres()->attach($genre);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 4,
            ]);
        }

        // Act
        $result = $user->genreRatings();

        // Assert
        $this->assertCount(5, $result);
    }

    public function test_ジャンル別の戻り値の形式が正しい(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create();
        $book->genres()->attach($genre);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        // Act
        $result = $user->genreRatings();

        // Assert
        $this->assertSame(
            [
                'id',
                'name',
                'average_rating',
                'count',
                'updated_at',
            ],
            array_keys($result->first())
        );
    }
}
