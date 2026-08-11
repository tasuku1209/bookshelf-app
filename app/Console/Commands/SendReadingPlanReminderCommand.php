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
     * 処理内容：処理内容：期限超過状態への更新と、期日3日前・当日・3日後のリマインダー通知を実行する。
     *
     * @return int コマンドの実行結果
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
     * 処理内容：読書計画のステータスが「進行中」で、期日が過ぎているものを「期限超過」に更新する。
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
     * 処理内容：読書計画のステータスが「進行中」で、期日の3日前のものに対してリマインダー通知を作成保存する。
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
     * 処理内容：読書計画のステータスが「進行中」で、期日が当日のものに対してリマインダー通知を作成保存する。
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
     * 処理内容：読書計画のステータスが「期限超過」で、期日の3日後のものに対してリマインダー通知を作成保存する。
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
