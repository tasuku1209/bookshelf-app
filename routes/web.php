<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
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

    // レビュー投稿
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    // レビュー編集・更新・削除
    Route::resource('reviews', ReviewController::class)
        ->only([
            'edit',
            'update',
            'destroy',
        ]);

    // お気に入り
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    // レビューいいね
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
        ->name('reviews.like');

    // ジャンル管理
    Route::resource('genres', GenreController::class);
});

// 公開ルート
// 書籍一覧表示、詳細表示
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// ランキング表示
Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');
