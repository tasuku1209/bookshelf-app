<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * コンストラクタ
     */
    public function __construct(public ReadingPlan $plan, public string $timing)
    {
        //
    }

    /**
     * 配信チャンネル
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベースへ保存する内容
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'timing' => $this->timing,
        ];
    }

    /**
     * 通知タイトル
     */
    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書計画の期日が近づいています',
            'on_due_date' => '本日が読書計画の期日です',
            'three_days_after' => '読書計画の期限を過ぎています',
        };
    }

    /**
     * 通知本文
     */
    private function body(): string
    {
        $title = $this->plan->book->title;

        return match ($this->timing) {
            'three_days_before' => "「{$title}」の読書計画の期限まであと3日です。計画どおり読み進めましょう。",

            'on_due_date' => "「{$title}」の読書計画は本日が期限です。読了した場合は「読了する」ボタンから完了してください。",

            'three_days_after' => "「{$title}」の読書計画は期限を3日過ぎています。引き続き読書を続ける場合は、計画を編集して期日を変更することもできます。",
        };
    }
}
