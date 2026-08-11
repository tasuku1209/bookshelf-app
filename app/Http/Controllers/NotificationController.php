<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 通知一覧
     * 処理内容：ユーザーの通知一覧を表示する。
     * @param Request $request ユーザー情報を取得するためのリクエストオブジェクト
     * @return View 通知一覧画面
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 既読ボタン
     * 処理内容：ユーザーが通知の既読ボタンを押下した際に、通知を既読にする。
     * @param Request $request ユーザー情報を取得するためのリクエストオブジェクト
     * @param string $id ユーザーが選択した通知のIDをルートパラメータより取得
     * @return RedirectResponse 通知一覧画面へリダイレクト
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました');
    }
}
