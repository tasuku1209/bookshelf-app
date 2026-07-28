<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReadingPlanController extends Controller
{
    /**
     * 一覧
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
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました');
    }

    /**
     * 作成画面
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 登録
     */
    public function store(ReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();

        ReadingPlan::create($validated);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました');
    }

    /**
     * 編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $books = Book::orderBy('title')->get();

        return view('reading-plans.edit', compact(
            'plan',
            'books',
        ));
    }

    /**
     * 更新
     */
    public function update(ReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました');
    }

    /**
     * 削除
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
