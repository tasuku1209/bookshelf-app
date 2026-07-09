<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 公開ルート
// 書籍一覧表示、詳細表示
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// 認証済みユーザーのみアクセス可能なルート
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
