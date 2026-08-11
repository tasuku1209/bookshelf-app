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
     * 処理内容：ジャンル一覧画面を表示する。ジャンルごとの書籍数を取得し、ビューに渡す。
     *
     * @return View ジャンル一覧画面
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
     * 処理内容：ジャンル登録画面を表示する。
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンル登録
     * 処理内容：ジャンルを登録する。
     *
     * @param  StoreGenreRequest  $request  ユーザーが入力したジャンル情報をバリデーション済みで取得
     * @return RedirectResponse ジャンル一覧画面へリダイレクト
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
     * 処理内容：ジャンル詳細画面を表示する。ジャンルに関連する書籍一覧を取得し、ビューに渡す。
     *
     * @param  Genre  $genre  ユーザーが選択したジャンル情報をルートパラメータより取得
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with([
                'genres' => function ($query) {
                    $query->orderBy('genres.name');
                },
            ])
            ->orderByDesc('books.created_at')
            ->orderByDesc('books.id')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面
     * 処理内容：ジャンル編集画面を表示する。
     *
     * @param  Genre  $genre  ユーザーが選択したジャンル情報をルートパラメータより取得
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        $this->authorize('update', $genre);

        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル更新
     * 処理内容：ジャンルを更新する。
     *
     * @param  UpdateGenreRequest  $request  ユーザーが入力したジャンル情報をバリデーション済みで取得
     * @param  Genre  $genre  ユーザーが選択したジャンル情報をルートパラメータより取得
     * @return RedirectResponse ジャンル一覧画面へリダイレクト
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
     * 処理内容：ジャンルを削除する。ジャンルに関連する書籍が存在する場合は削除できない。
     *
     * @param  Genre  $genre  ユーザーが選択したジャンル情報をルートパラメータより取得
     * @return RedirectResponse ジャンル一覧画面へリダイレクト
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
