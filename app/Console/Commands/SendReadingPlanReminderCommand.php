<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

class SendReadingPlanReminderCommand extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'reading-plans:remind';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '読書計画のリマインダー通知を送信し、期限超過状態を更新する';

    /**
     * 実行
     */
    public function handle(): int
    {
        $this->markOverduePlans();

        $this->sendThreeDaysBeforeReminder();

        $this->sendTodayReminder();

        $this->sendThreeDaysAfterReminder();

        return self::SUCCESS;
    }

    /**
     * 期限超過状態へ更新
     */
    private function markOverduePlans(): void
    {
        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Overdue,
            ]);
    }

    /**
     * 期日3日前のリマインダー通知
     */
    private function sendThreeDaysBeforeReminder(): void
    {
        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today()->addDays(3))
            ->get();

        foreach ($plans as $plan) {
            $plan->user->notify(
                new ReadingPlanReminderNotification(
                    $plan,
                    'three_days_before',
                )
            );
        }
    }

    /**
     * 期日当日のリマインダー通知
     */
    private function sendTodayReminder(): void
    {
        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today())
            ->get();

        foreach ($plans as $plan) {
            $plan->user->notify(
                new ReadingPlanReminderNotification(
                    $plan,
                    'on_due_date',
                )
            );
        }
    }

    /**
     * 期日3日後のリマインダー通知
     */
    private function sendThreeDaysAfterReminder(): void
    {
        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Overdue)
            ->whereDate('target_date', today()->subDays(3))
            ->get();

        foreach ($plans as $plan) {
            $plan->user->notify(
                new ReadingPlanReminderNotification(
                    $plan,
                    'three_days_after',
                )
            );
        }
    }
}
