<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り追加・解除
     * 処理内容：ユーザーがお気に入りボタンを押下した際に、お気に入りの追加・解除を行う。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return RedirectResponse 前のページへリダイレクト
     */
    public function toggle(Book $book): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $user->favoriteBooks()->toggle($book);

        return back();
    }

    /**
     * お気に入り一覧
     * 処理内容：ユーザーのお気に入り書籍一覧を表示する。
     *
     * @return View お気に入り一覧画面
     */
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $books = $user->favoriteBooks()
            ->orderByDesc('favorites.created_at')
            ->orderByDesc('books.id')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }
}
