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
     */
    public function toggle(Book $book): RedirectResponse
    {
        auth()->user()->favoriteBooks()->toggle($book);

        /** @var User $user */
        /*
        $user = auth()->user();
        $user->favoriteBooks()->toggle($book);
        */

        return back();
    }

    /**
     * お気に入り一覧
     */
    public function index(): View
    {
        $books = auth()->user()->favoriteBooks()
            ->latest('books.created_at')
            ->paginate(10);

        /** @var User $user */
        /*
        $user = auth()->user();
        $books = $user->favoriteBooks()
            ->latest('books.created_at')
            ->paginate(10);
        */

        return view('favorites.index', compact('books'));
    }
}
