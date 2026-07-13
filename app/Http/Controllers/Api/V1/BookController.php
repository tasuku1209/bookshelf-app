<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;
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
            ->with('genres')
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
            ->latest()
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return BookResource::collection($books);
    }

    /**
     * 書籍登録
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        $genres = $validated['genres'];

        unset($validated['email']);
        unset($validated['genres']);

        $validated['user_id'] = $user->id;

        $book = Book::create($validated);

        $book->genres()->sync($genres);

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
        ], 201);
    }

    /**
     * 書籍詳細
     */
    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    /**
     * 書籍更新
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($book->user_id !== $user->id) {
            return response()->json([
                'message' => 'この書籍を更新する権限がありません。',
            ], 403);
        }

        $genres = $validated['genres'];

        unset($validated['email']);
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
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
