<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧
     * 処理内容：ユーザーの読書計画一覧を表示する。読書計画のステータスごとにソートし、ビューに渡す。
     *
     * @param  IndexReadingPlanRequest  $request  ユーザー情報を取得するためのリクエストオブジェクト
     * @return View 読書計画一覧画面
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        $currentStatus = $request->validated('status');

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->status($currentStatus)
            ->orderByRaw("
                CASE status
                    WHEN 'overdue' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed' THEN 3
                END
            ")
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus',
        ));
    }

    /**
     * 読了ボタン
     * 処理内容：ユーザーが読書計画の読了ボタンを押下した際に、読書計画を読了状態に更新する。
     *
     * @param  ReadingPlan  $plan  ユーザーが選択した読書計画をルートパラメータより取得
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました');
    }

    /**
     * 読書計画作成画面
     * 処理内容：読書計画の作成画面を表示する。書籍一覧を取得し、ビューに渡す。
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画登録
     * 処理内容：読書計画を登録する。
     *
     * @param  StoreReadingPlanRequest  $request  ユーザーが入力した読書計画情報をバリデーション済みで取得
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();

        ReadingPlan::create($validated);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました');
    }

    /**
     * 読書計画編集画面
     * 処理内容：読書計画の編集画面を表示する。
     *
     * @param  ReadingPlan  $plan  ユーザーが選択した読書計画をルートパラメータより取得
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        return view('reading-plans.edit', [
            'readingPlan' => $plan,
        ]);
    }

    /**
     * 読書計画更新
     * 処理内容：読書計画を更新する。
     *
     * @param  UpdateReadingPlanRequest  $request  ユーザーが入力した読書計画情報をバリデーション済みで取得
     * @param  ReadingPlan  $plan  ユーザーが選択した読書計画をルートパラメータより取得
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        if (
            $plan->status === ReadingPlanStatus::Overdue &&
            $validated['target_date'] >= today()->toDateString()
        ) {
            $validated['status'] = ReadingPlanStatus::InProgress;
        }

        $plan->update($validated);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました');
    }

    /**
     * 読書計画削除
     * 処理内容：読書計画を削除する。
     *
     * @param  ReadingPlan  $plan  ユーザーが選択した読書計画をルートパラメータより取得
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました');
    }
}
