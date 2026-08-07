<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはマイ読書レポートを表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        // Assert
        $response->assertOk();
    }

    public function test_認証済みユーザー自身のデータだけが集計対象になる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach($genre);

        // target user
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        // other user

        $other = User::factory()->create();

        Review::factory()->create([
            'user_id' => $other->id,
            'book_id' => $book->id,
            'rating' => 1,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $other->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        // Assert
        $response->assertOk();

        $stats = $response->viewData('stats');

        // summary
        $this->assertSame(1, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertSame(5.0, $stats['summary']['average_rating']);

        // rating_distribution
        $this->assertSame(
            [0, 0, 0, 0, 1],
            $stats['rating_distribution']->all()
        );

        // top_rated_books
        $this->assertCount(1, $stats['top_rated_books']);
        $this->assertSame(5, $stats['top_rated_books']->first()['rating']);

        // genre_ratings
        $this->assertCount(1, $stats['genre_ratings']);
        $this->assertSame(5, $stats['genre_ratings']->first()['average_rating']);
    }

    public function test_ゲストユーザーはマイ読書レポートを表示できない(): void
    {
        // Act
        $response = $this->get(route('reports.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }
}
