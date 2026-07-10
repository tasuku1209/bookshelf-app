<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

// ログアウト後のリダイレクト先を指定
Route::get('/', function () {
    return redirect('/books');
});

// 認証ルート
Route::middleware('auth')->group(function () {
    // 書籍の作成、編集、削除
    Route::resource('books', BookController::class)
        ->only([
            'create',
            'store',
            'edit',
            'update',
            'destroy',
        ]);
});

// 公開ルート
// 書籍一覧表示、詳細表示
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// 仮ルート設置
Route::get('/ranking', function () {
    return view('ranking.index');
})->name('ranking.index');

Route::get('/favorites', function () {
    return view('favorites.index');
})->name('favorites.index');

Route::get('/genres', function () {
    return view('genres.index');
})->name('genres.index');
