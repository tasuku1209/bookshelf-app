<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id'),
            ],
            'target_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください',
            'book_id.integer' => '書籍の選択が不正です',
            'book_id.exists' => '選択された書籍は存在しません',

            'target_date.required' => '期日を入力してください',
            'target_date.date' => '期日は日付形式で入力してください',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください',
        ];
    }
}
