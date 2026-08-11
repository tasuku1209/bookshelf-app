<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * マイ読書レポート
     * 処理内容：ユーザーの読書統計情報を取得し、ビューに渡す。
     * @param Request $request ユーザー情報を取得するためのリクエストオブジェクト
     * @return View マイ読書レポート画面
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'summary' => $user->summaryStats(),
            'rating_distribution' => $user->ratingDistribution(),
            'top_rated_books' => $user->topRatedBooks(),
            'genre_ratings' => $user->genreRatings(),
        ];

        return view('reports.index', compact('stats'));
    }
}
