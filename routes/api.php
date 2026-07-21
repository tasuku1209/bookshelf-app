<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->as('api.v1.')->group(function () {
    Route::apiResource('books', BookController::class);
});
