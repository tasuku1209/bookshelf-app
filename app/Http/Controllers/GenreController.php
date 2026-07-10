<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')
            ->orderBy('name')
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンル登録
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();

        Genre::create($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました');
    }

    /**
     * ジャンル詳細
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        $this->authorize('update', $genre);

        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル更新
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $this->authorize('update', $genre);

        $genre->update($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました');
    }

    /**
     * ジャンル削除
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $this->authorize('delete', $genre);

        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', 'このジャンルには書籍が登録されているため削除できません');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました');
    }
}
