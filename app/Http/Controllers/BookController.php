<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * ISBN検索
     * 処理内容：Google Books APIを用いてISBNから書籍情報を検索する
     *
     * @param  string  $isbn  ユーザーが入力したISBN番号をパスパラメータより取得
     * @return JsonResponse Google Books APIより取得した書籍情報をJSON形式で返す
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        try {
            $response = Http::get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => 'isbn:'.$isbn,
                    'key' => config('services.google_books.api_key'),
                ]
            );

            // Google APIエラー
            if ($response->failed()) {
                return response()->json([
                    'error' => '現在、書籍情報を取得できません。しばらくしてからお試しください。',
                ], 500);
            }

            $data = $response->json();

            // 該当書籍なし
            if (($data['totalItems'] ?? 0) === 0) {
                return response()->json([
                    'error' => '書籍情報が見つかりませんでした。',
                ], 404);
            }

            $book = $data['items'][0]['volumeInfo'];

            return response()->json([
                'title' => $book['title'] ?? '',
                'author' => $book['authors'][0] ?? '',
                'published_date' => $book['publishedDate'] ?? '',
                'description' => $book['description'] ?? '',
                'image_url' => $book['imageLinks']['thumbnail'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '現在、書籍情報を取得できません。しばらくしてからお試しください。',
            ], 500);
        }
    }

    /**
     * 書籍一覧
     * 処理内容：書籍一覧画面を表示する。また、検索条件に応じて書籍を絞り込む。
     *
     * @param  IndexBookRequest  $request  ユーザーが入力した検索条件をバリデーション済みで取得
     * @return View 書籍一覧画面
     */
    public function index(IndexBookRequest $request): View
    {
        $sort = $request->sort ?? 'newest';

        $genres = Genre::orderBy('name')->get();

        $books = Book::query()
            ->with([
                'genres' => function ($query) {
                    $query->orderBy('genres.name');
                },
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->keyword($request->keyword)
            ->genre($request->genre)
            ->sort($sort)
            ->paginate(10)
            ->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面
     * 処理内容：書籍登録画面を表示する。ジャンル一覧を取得し、ビューに渡す。
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍登録
     * 処理内容：書籍を登録する。ジャンル情報を関連付ける。
     *
     * @param  StoreBookRequest  $request  ユーザーが入力した書籍情報とジャンル情報をバリデーション済みで取得
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $validated['user_id'] = auth()->id();

        $book = DB::transaction(function () use ($validated, $genres) {

            $book = Book::create($validated);

            $book->genres()->attach($genres);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました');
    }

    /**
     * 書籍詳細
     * 処理内容：書籍詳細画面を表示する。ジャンル情報とレビュー情報を取得し、ビューに渡す。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return View 書籍詳細画面
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
     * 処理内容：書籍編集画面を表示する。ジャンル一覧を取得し、ビューに渡す。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return View 書籍編集画面
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
     * 処理内容：書籍を更新する。ジャンル情報を関連付ける。
     *
     * @param  UpdateBookRequest  $request  ユーザーが入力した書籍情報とジャンル情報をバリデーション済みで取得
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $book = DB::transaction(function () use ($book, $validated, $genres) {
            $book->update($validated);

            $book->genres()->sync($genres);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました');
    }

    /**
     * 書籍削除
     * 処理内容：書籍を削除する。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return RedirectResponse 書籍一覧画面へリダイレクト
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
