<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューいいね追加・解除
     */
    public function toggle(Review $review): RedirectResponse
    {
        auth()->user()->likedReviews()->toggle($review);

        /** @var User $user */
        /*
        $user = auth()->user();
        $user->likedReviews()->toggle($review);
        */

        return back();
    }
}
