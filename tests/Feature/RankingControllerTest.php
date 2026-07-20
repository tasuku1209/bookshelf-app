<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍ランキング画面を表示できる(): void
    {
        // Arrange
        Book::factory()
            ->count(11)
            ->create()
            ->each(function ($book) {
                Review::factory()->count(2)->create([
                    'book_id' => $book->id,
                ]);
            });

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertOk();

        $response->assertViewHas('rankedBooks');

        $rankedBooks = $response->viewData('rankedBooks');

        $this->assertCount(10, $rankedBooks);
    }

    public function test_評価平均値順で表示される(): void
    {
        // Arrange
        $book1 = Book::factory()->create([
            'title' => '評価4',
        ]);

        $book2 = Book::factory()->create([
            'title' => '評価5',
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book1->id,
            'rating' => 4,
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertSeeInOrder([
            '評価5',
            '評価4',
        ]);
    }

    public function test_レビュー件数順で表示される(): void
    {
        // Arrange
        $book1 = Book::factory()->create([
            'title' => 'レビュー1件',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'レビュー2件',
        ]);

        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertSeeInOrder([
            'レビュー2件',
            'レビュー1件',
        ]);
    }

    public function test_最新順で表示される(): void
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

        Review::factory()->count(2)->create([
            'book_id' => $oldBook->id,
            'rating' => 5,
        ]);

        Review::factory()->count(2)->create([
            'book_id' => $newBook->id,
            'rating' => 5,
        ]);

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertSeeInOrder([
            '新しい本',
            '古い本',
        ]);
    }

    public function test_評価平均値とレビュー件数がビューへ渡される(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // reviews_avg_rating (5+1)/2=3
        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 1,
        ]);

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $rankedBooks = $response->viewData('rankedBooks');

        $this->assertEquals(3, $rankedBooks->first()->reviews_avg_rating);
        $this->assertEquals(2, $rankedBooks->first()->reviews_count);
    }
}
