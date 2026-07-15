<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧
     */
    public function index(): View
    {
        $books = Book::with([
            'genres' => function ($query) {
                $query->orderBy('genres.name');
            },
        ])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('books.created_at')
            ->orderByDesc('books.id')
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍登録
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $validated['user_id'] = auth()->id();

        $book = Book::create($validated);

        $book->genres()->attach($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました');
    }

    /**
     * 書籍詳細
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres' => function ($query) {
                $query->orderBy('genres.name');
            },
            'reviews' => function ($query) {
                $query
                    ->orderByDesc('reviews.created_at')
                    ->orderByDesc('reviews.id')
                    ->with([
                        'user',
                        'likedByUsers',
                    ]);
            },
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');

        $genres = Genre::orderBy('name')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍更新
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました');
    }

    /**
     * 書籍削除
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました');
    }
}
