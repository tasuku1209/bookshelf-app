<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReadingPlanPolicy
{
    /**
     * 読書計画更新
     */
    public function update(User $user, ReadingPlan $plan): Response
    {
        return $user->id === $plan->user_id
            ? Response::allow()
            : Response::deny('この読書計画を編集する権限がありません。');
    }

    /**
     * 削除
     */
    public function delete(User $user, ReadingPlan $plan): Response
    {
        return $user->id === $plan->user_id
            ? Response::allow()
            : Response::deny('この読書計画を削除する権限がありません。');
    }

    /**
     * 読了
     */
    public function complete(User $user, ReadingPlan $plan): Response
    {
        return $user->id === $plan->user_id
            ? Response::allow()
            : Response::deny('この読書計画を読了に変更する権限がありません。');
    }
}
