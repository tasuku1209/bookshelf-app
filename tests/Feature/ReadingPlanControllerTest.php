<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは読書計画一覧を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '書籍タイトル',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('書籍タイトル');
        $response->assertSee('進行中');
    }

    public function test_認証済みユーザー自身の読書計画だけが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userBook = Book::factory()->create([
            'title' => 'user書籍',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'other書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $userBook->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('user書籍');
        $response->assertDontSee('other書籍');
    }

    public function test_読書計画が期限超過_進行中_読了の優先順位で表示され同じ状態では期限日順で表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // 期限超過
        $book1 = Book::factory()->create([
            'title' => '期限超過の書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDay(),
        ]);

        // 進行中（期限日が遅い）
        $book2 = Book::factory()->create([
            'title' => '進行中の期限日が遅い書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(2),
        ]);

        // 進行中（期限日が早い）
        $book3 = Book::factory()->create([
            'title' => '進行中の期限日が早い書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDay(),
        ]);

        // 読了
        $book4 = Book::factory()->create([
            'title' => '読了の書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book4->id,
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->subDays(2),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        // Assert
        $response->assertOk();
        $response->assertSeeInOrder([
            '期限超過の書籍',
            '進行中の期限日が早い書籍',
            '進行中の期限日が遅い書籍',
            '読了の書籍',
        ]);
    }

    public function test_ゲストユーザーは読書計画一覧を表示できない(): void
    {
        // Act
        $response = $this->get(route('reading-plans.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_読書計画一覧で状態絞込みをすると指定した状態の読書計画だけが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => '進行中の書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => '読了の書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::InProgress->value,
            ]));

        // Assert
        $response->assertOk();
        $response->assertSee('進行中の書籍');
        $response->assertDontSee('読了の書籍');
    }

    public function test_読書計画一覧で不正な値では状態絞り込みができない(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'invalid-status',
            ]));

        // Assert
        $response->assertSessionHasErrors([
            'status',
        ]);
    }

    public function test_読了ボタンで認証済みかつ自分の読書計画を読了状態に更新でき完了日時が記録される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $plan->refresh();

        $this->assertNotNull($plan->completed_at);

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas('success', '読書計画を読了にしました');
    }

    public function test_読了ボタンでゲストユーザーは読書計画を読了状態に更新できない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create();

        // Act
        $response = $this->post(
            route('reading-plans.complete', $plan)
        );

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_読了ボタンで他人の読書計画は読了状態に更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        // Assert
        $response->assertForbidden();
    }

    public function test_認証済みユーザーは読書計画登録画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Book::factory()->create([
            'title' => '書籍タイトル',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.create'));

        // Assert
        $response->assertOk();
        $response->assertSee('書籍タイトル');
    }

    public function test_ゲストユーザーは読書計画登録画面を表示できない(): void
    {
        // Act
        $response = $this->get(route('reading-plans.create'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは読書計画を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas('success', '読書計画を登録しました');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'].' 00:00:00',
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_ゲストユーザーは読書計画を登録できない(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
        ];

        // Act
        $response = $this->post(
            route('reading-plans.store'),
            $data
        );

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_必須項目未入力では読書計画を登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => '',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_書籍指定が数値形式でない場合では読書計画を登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'book_id' => 'not-a-number',
            'target_date' => now()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'book_id',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'target_date' => $data['target_date'].' 00:00:00',
        ]);
    }

    public function test_存在しない書籍では読書計画を登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $data = [
            'book_id' => 999,
            'target_date' => now()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'book_id',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'target_date' => $data['target_date'].' 00:00:00',
        ]);
    }

    public function test_日付形式でない期日では読書計画を登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => 'not-a-date',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_過去の日付の期日では読書計画を登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_認証済みかつ自分の読書計画で編集画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '書籍タイトル',
        ]);

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        // Assert
        $response->assertOk();
        $response->assertSee('書籍タイトル');
    }

    public function test_ゲストユーザーは読書計画編集画面を表示できない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create();

        // Act
        $response = $this->get(route('reading-plans.edit', $plan));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みでも他人の読書計画は編集画面を表示できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        // Assert
        $response->assertForbidden();
    }

    public function test_認証済みかつ自分の登録読書計画を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDay()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas('success', '読書計画を更新しました');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $data['target_date'].' 00:00:00',
        ]);
    }

    public function test_認証済みかつ自分の登録読書計画で期限超過の読書計画を更新すると進行中に状態遷移する(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $data = [
            'target_date' => now()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => $data['target_date'].' 00:00:00',
        ]);
    }

    public function test_ゲストユーザーは読書計画を更新できない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create([
            'target_date' => now()->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDay()->toDateString(),
        ];

        // Act
        $response = $this->put(
            route('reading-plans.update', $plan),
            $data
        );

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $plan->target_date,
        ]);
    }

    public function test_認証済みでも他人の読書計画は更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'target_date' => now()->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDay()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $plan->target_date,
        ]);
    }

    public function test_必須項目未入力では読書計画を更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->toDateString(),
        ]);

        $data = [];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $plan->target_date,
        ]);
    }

    public function test_日付形式でない期日では読書計画を更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->toDateString(),
        ]);

        $data = [
            'target_date' => 'not-a-date',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $plan->target_date,
        ]);
    }

    public function test_過去の日付の期日では読書計画を更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->toDateString(),
        ]);

        $data = [
            'target_date' => now()->subDay()->toDateString(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), $data);

        // Assert
        $response->assertSessionHasErrors([
            'target_date',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $plan->target_date,
        ]);
    }

    public function test_認証済みかつ自分の読書計画を削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas('success', '読書計画を削除しました');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_ゲストユーザーは読書計画を削除できない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create();

        // Act
        $response = $this->delete(
            route('reading-plans.destroy', $plan)
        );

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_認証済みでも他人の読書計画は削除できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }
}
