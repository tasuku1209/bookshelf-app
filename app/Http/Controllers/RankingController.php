<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 評価ランキング表示
     * 処理内容：レビューの平均評価が高い順に書籍をランキング形式で表示する。
     *
     * @return View 評価ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('created_at')
            ->orderByDesc('books.id')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
