<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * ログイン
     * 処理内容：ユーザーがログインする際に、メールアドレスとパスワードを検証し、認証トークンを発行する。
     *
     * @param  LoginRequest  $request  ユーザーが入力したメールアドレスとパスワードをバリデーション済みで取得
     * @return JsonResponse 認証トークンとユーザー情報を含むJSONレスポンス
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが正しくありません',
            ], 401);
        }

        $user = $request->user();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'ログインしました',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 200);
    }

    /**
     * ログアウト
     * 処理内容：ユーザーがログアウトする際に、現在の認証トークンを削除する。
     *
     * @param  Request  $request  ユーザー情報を取得するためのリクエストオブジェクト
     * @return JsonResponse ログアウト完了メッセージを含むJSONレスポンス
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'ログアウトしました',
        ], 200);
    }
}
