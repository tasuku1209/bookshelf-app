<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $userA = User::where('name', '山田太郎')->firstOrFail();
        $userB = User::where('name', '鈴木花子')->firstOrFail();

        // 山田Book id1～id9 ※id2、id5、id8が通知対象
        collect(range(-4, 4))
            ->each(function (int $offset, int $index) use ($userA) {

                ReadingPlan::create([
                    'user_id' => $userA->id,
                    'book_id' => $index + 1,
                    'target_date' => Carbon::today()->addDays($offset),
                    'status' => ReadingPlanStatus::InProgress,
                    'completed_at' => null,
                ]);
            });

        // 山田Book id10 ※通知対象日だが読了状態により通知なし
        collect([-3, 0, 3])
            ->each(function (int $offset) use ($userA) {

                ReadingPlan::create([
                    'user_id' => $userA->id,
                    'book_id' => 10,
                    'target_date' => Carbon::today()->addDays($offset),
                    'status' => ReadingPlanStatus::Completed,
                    'completed_at' => Carbon::today(),
                ]);
            });

        // 鈴木Book id11 ※認可確認用
        ReadingPlan::create([
            'user_id' => $userB->id,
            'book_id' => 11,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today(),
        ]);
    }
}
