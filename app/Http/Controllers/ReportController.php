<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * マイ読書レポート
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
