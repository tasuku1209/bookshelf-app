<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
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

    // ISBN検索
    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchByIsbn'])
        ->name('books.isbn');

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

    // マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    // 読書計画
    Route::resource('reading-plans', ReadingPlanController::class)
        ->except('show')
        ->parameters([
            'reading-plans' => 'plan',
        ]);

    // 読了ボタン
    Route::post(
        'reading-plans/{plan}/complete',
        [ReadingPlanController::class, 'complete']
    )->name('reading-plans.complete');

    // 通知一覧
    Route::get(
        'notifications',
        [NotificationController::class, 'index']
    )->name('notifications.index');

    // 既読ボタン
    Route::post(
        'notifications/{id}/read',
        [NotificationController::class, 'read']
    )->name('notifications.read');
});

// 公開ルート
// 書籍一覧表示、詳細表示
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// ランキング表示
Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');
