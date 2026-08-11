<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookIndexResource;
use App\Http\Resources\BookResponseResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧
     * 処理内容：書籍の一覧を取得し、キーワード検索やジャンル絞り込み、ページネーションを適用して返す。
     *
     * @param  IndexBookRequest  $request  ユーザーが入力した検索条件をバリデーション済みで取得
     * @return AnonymousResourceCollection 書籍一覧を含むJSONレスポンス
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $books = Book::query()
            ->with([
                'genres' => function ($query) {
                    $query->orderBy('genres.name');
                },
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->keyword($validated['keyword'] ?? null) // キーワード検索（タイトル・著者）
            ->genre($validated['genre_id'] ?? null)  // ジャンル絞り込み
            ->orderByDesc('books.created_at')
            ->orderByDesc('books.id')
            ->paginate(
                $validated['per_page'] ?? 20,
                ['*'],
                'page',
                $validated['page'] ?? 1
            )
            ->withQueryString();

        return BookIndexResource::collection($books);
    }

    /**
     * 書籍登録
     * 処理内容：書籍を登録する。ジャンル情報を関連付ける。
     *
     * @param  StoreBookRequest  $request  ユーザーが入力した書籍情報とジャンル情報をバリデーション済みで取得
     * @return JsonResponse 書籍登録完了メッセージと登録した書籍情報を含むJSONレスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $validated['user_id'] = auth()->id();

        $book = DB::transaction(function () use ($validated, $genres) {

            $book = Book::create($validated);

            $book->genres()->sync($genres);

            return $book;
        });

        $book->load([
            'genres' => function ($query) {
                $query->orderBy('genres.name');
            },
        ]);

        return response()->json([
            'message' => '書籍を登録しました',
            'data' => new BookResponseResource($book),
        ], 201);
    }

    /**
     * 書籍詳細
     * 処理内容：書籍の詳細情報を取得し、レビュー情報やジャンル情報を含めて返す。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return BookDetailResource 書籍詳細情報を含むJSONレスポンス
     */
    public function show(Book $book): BookDetailResource
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
                    ])
                    ->withCount('likedByUsers');
            },
        ])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookDetailResource($book);
    }

    /**
     * 書籍更新
     * 処理内容：書籍を更新する。ジャンル情報を関連付ける。
     *
     * @param  UpdateBookRequest  $request  ユーザーが入力した書籍情報とジャンル情報をバリデーション済みで取得
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return JsonResponse 書籍更新完了メッセージと更新した書籍情報を含むJSONレスポンス
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
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

        $book->load([
            'genres' => function ($query) {
                $query->orderBy('genres.name');
            },
        ]);

        return response()->json([
            'message' => '書籍を更新しました',
            'data' => new BookResponseResource($book),
        ], 200);
    }

    /**
     * 書籍削除
     * 処理内容：書籍を削除する。
     *
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return JsonResponse 書籍削除完了メッセージを含むJSONレスポンス
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
