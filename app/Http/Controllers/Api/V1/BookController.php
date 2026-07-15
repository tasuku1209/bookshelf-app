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

class BookController extends Controller
{
    /**
     * 書籍一覧
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $query = Book::query()
            ->with([
                'genres' => function ($query) {
                    $query->orderBy('genres.name');
                },
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワード検索（タイトル・著者）
        if (! empty($validated['keyword'])) {
            $query->where(function ($query) use ($validated) {
                $query->where('title', 'like', '%'.$validated['keyword'].'%')
                    ->orWhere('author', 'like', '%'.$validated['keyword'].'%');
            });
        }

        // ジャンル絞り込み
        if (! empty($validated['genre_id'])) {
            $query->whereHas('genres', function ($query) use ($validated) {
                $query->where('genres.id', $validated['genre_id']);
            });
        }

        $books = $query
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
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['genres']);

        $book = Book::create($validated);

        $book->genres()->sync($genres);

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
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];

        unset($validated['user_id']);
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        $book->load([
            'genres' => function ($query) {
                $query->orderBy('genres.name');
            },
        ]);

        return response()->json([
            'message' => '書籍を更新しました',
            'data' => new BookResponseResource($book),
        ]);
    }

    /**
     * 書籍削除
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
