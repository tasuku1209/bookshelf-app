<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画からユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(User::class, $readingPlan->user);
        $this->assertSame($user->id, $readingPlan->user->id);
    }

    public function test_読書計画から書籍を取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'book_id' => $book->id,
        ]);

        // Act Assert
        $this->assertInstanceOf(Book::class, $readingPlan->book);
        $this->assertSame($book->id, $readingPlan->book->id);
    }

    public function test_状態絞り込みで指定した状態の読書計画だけ取得できる(): void
    {
        // Arrange
        $inProgressPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
        ]);

        // Act
        $result = ReadingPlan::query()
            ->status(ReadingPlanStatus::InProgress->value)
            ->get();

        // Assert
        $this->assertCount(1, $result);

        $this->assertSame(
            $inProgressPlan->id,
            $result->first()->id
        );
    }
}
