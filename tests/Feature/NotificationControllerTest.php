<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは通知一覧画面を表示できる(): void
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

        $user->notify(
            new ReadingPlanReminderNotification(
                $plan,
                'on_due_date'
            )
        );

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('書籍タイトル');
        $response->assertSee('本日が読書計画の期日です');
    }

    public function test_通知が最新順で表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // 古い通知を作成
        $oldBook = Book::factory()->create([
            'title' => '古い通知の書籍',
        ]);

        $oldPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $oldBook->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $oldPlan,
                'on_due_date'
            )
        );

        $user->notifications()->first()->update([
            'created_at' => now()->subDay(),
        ]);

        // 新しい通知を作成
        $newBook = Book::factory()->create([
            'title' => '新しい通知の書籍',
        ]);

        $newPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $newBook->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $newPlan,
                'on_due_date'
            )
        );

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        // Assert
        $response->assertOk();

        $response->assertSeeInOrder([
            '新しい通知の書籍',
            '古い通知の書籍',
        ]);
    }

    public function test_認証済みユーザー自身の通知だけが表示される(): void
    {
        // Arrange
        // 自分の通知を作成
        $user = User::factory()->create();

        $userBook = Book::factory()->create([
            'title' => '自分の書籍',
        ]);

        $userPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $userBook->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $userPlan,
                'on_due_date'
            )
        );

        // 他人の通知を作成
        $otherUser = User::factory()->create();

        $otherBook = Book::factory()->create([
            'title' => '他人の書籍',
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
        ]);

        $otherUser->notify(
            new ReadingPlanReminderNotification(
                $otherPlan,
                'on_due_date'
            )
        );

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('自分の書籍');
        $response->assertDontSee('他人の書籍');
    }

    public function test_ゲストユーザーは通知一覧画面を表示できない(): void
    {
        // Act
        $response = $this->get(route('notifications.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは自分の通知を既読にできる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $plan,
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        // Assert
        $response->assertRedirect(route('notifications.index'));

        $response->assertSessionHas(
            'success',
            '通知を既読にしました'
        );

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_ゲストユーザーは通知を既読にできない(): void
    {
        // Arrange
        $notificationId = 'test-notification-id';

        // Act
        $response = $this->post(
            route('notifications.read', $notificationId)
        );

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは他人の通知を既読にできず書籍一覧画面へリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $otherUser->notify(
            new ReadingPlanReminderNotification(
                $plan,
                'on_due_date'
            )
        );

        $notification = $otherUser->notifications()->first();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        // Assert
        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'error',
            '指定されたデータは存在しません'
        );
    }
}
