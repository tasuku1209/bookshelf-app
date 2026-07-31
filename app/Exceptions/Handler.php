<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // API404エラー
        if ($request->is('api/*') && $e instanceof ModelNotFoundException) {
            return response()->json([
                'error' => '指定されたデータは存在しません',
            ], 404);
        }

        // API401エラー
        if ($request->is('api/*') && $e instanceof AuthenticationException) {
            return response()->json([
                'message' => '認証が必要です',
            ], 401);
        }

        // WEB404エラー
        if (! $request->is('api/*') && $e instanceof ModelNotFoundException) {
            return redirect()
                ->route('books.index')
                ->with('error', '指定されたデータは存在しません');
        }

        return parent::render($request, $e);
    }
}
