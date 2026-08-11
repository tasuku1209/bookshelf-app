<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューいいね追加・解除
     * 処理内容：ユーザーがレビューのいいねボタンを押下した際に、レビューのいいねの追加・解除を行う。
     *
     * @param  Review  $review  ユーザーが選択したレビュー情報をルートパラメータより取得
     * @return RedirectResponse 前のページへリダイレクト
     */
    public function toggle(Review $review): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $user->likedReviews()->toggle($review);

        return back();
    }
}
