<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GenrePolicy
{
    /**
     * ジャンル編集
     */
    public function update(User $user, Genre $genre): Response
    {
        return $user->id === $genre->user_id
            ? Response::allow()
            : Response::deny('このジャンルを編集する権限がありません。');
    }

    /**
     * ジャンル削除
     */
    public function delete(User $user, Genre $genre): Response
    {
        return $user->id === $genre->user_id
            ? Response::allow()
            : Response::deny('このジャンルを削除する権限がありません。');
    }
}
