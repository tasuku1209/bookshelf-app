<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_バッチ実行で期日を過ぎた進行中の読書計画が期限超過状態になる(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->subDay(),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Overdue,
            $plan->status
        );
    }

    public function test_バッチ実行で期日を過ぎていない読書計画は期限超過状態にならない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now(),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $plan->status
        );
    }

    public function test_バッチ実行で読了済みの読書計画は期限超過状態にならない(): void
    {
        // Arrange
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->subDay(),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );
    }

    public function test_バッチ実行で期日3日前の読書計画にリマインダー通知が作成される(): void
    {
        // Arrange
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(3),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );
    }

    public function test_バッチ実行しても読了済みの期日3日前の読書計画にはリマインダー通知が作成されない(): void
    {
        // Arrange
        ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->addDays(3),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_バッチ実行で期日当日の読書計画にリマインダー通知が作成される(): void
    {
        // Arrange
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now(),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );
    }

    public function test_バッチ実行しても読了済みの期日当日の読書計画にはリマインダー通知が作成されない(): void
    {
        // Arrange
        ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now(),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_バッチ実行で期日3日後の読書計画にリマインダー通知が作成される(): void
    {
        // Arrange
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->subDays(3),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );
    }

    public function test_バッチ実行しても読了済みの期日3日後の読書計画にはリマインダー通知が作成されない(): void
    {
        // Arrange
        ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->subDays(3),
        ]);

        // Act
        $this->artisan('reading-plans:remind');

        // Assert
        $this->assertDatabaseCount('notifications', 0);
    }
}
