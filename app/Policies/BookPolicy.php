<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookPolicy
{
    /**
     * 書籍編集
     */
    public function update(User $user, Book $book): Response
    {
        return $user->id === $book->user_id
            ? Response::allow()
            : Response::deny('この書籍を編集する権限がありません。');
    }

    /**
     * 書籍削除
     */
    public function delete(User $user, Book $book): Response
    {
        return $user->id === $book->user_id
            ? Response::allow()
            : Response::deny('この書籍を削除する権限がありません。');
    }
}
