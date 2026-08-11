<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビュー投稿
     * 処理内容：レビューを登録する。
     *
     * @param  ReviewRequest  $request  ユーザーが入力したレビュー情報をバリデーション済みで取得
     * @param  Book  $book  ユーザーが選択した書籍情報をルートパラメータより取得
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $validated['book_id'] = $book->id;

        Review::create($validated);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました');
    }

    /**
     * レビュー編集画面
     * 処理内容：レビュー編集画面を表示する。
     *
     * @param  Review  $review  ユーザーが選択したレビュー情報をルートパラメータより取得
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビュー更新
     * 処理内容：レビューを更新する。
     *
     * @param  ReviewRequest  $request  ユーザーが入力したレビュー情報をバリデーション済みで取得
     * @param  Review  $review  ユーザーが選択したレビュー情報をルートパラメータより取得
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました');
    }

    /**
     * レビュー削除
     * 処理内容：レビューを削除する。
     *
     * @param  Review  $review  ユーザーが選択したレビュー情報をルートパラメータより取得
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました');
    }
}
