<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

// APIルート　V1
Route::prefix('v1')->as('api.v1.')->group(function () {

    // 認証ルート
    Route::middleware('auth:sanctum')->group(function () {

        // ログアウト
        Route::post('/logout', [AuthController::class, 'logout']);

        // 書籍の作成、更新、削除
        Route::apiResource('books', BookController::class)
            ->only(['store', 'update', 'destroy']);
    });

    // 公開ルート
    // ログイン
    Route::post('/login', [AuthController::class, 'login']);

    // 書籍一覧、詳細
    Route::apiResource('books', BookController::class)
        ->only(['index', 'show']);
});
