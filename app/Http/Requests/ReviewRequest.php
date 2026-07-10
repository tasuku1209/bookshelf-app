<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'in:1,2,3,4,5',
            ],
            'comment' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を入力してください',
            'rating.integer' => '評価の形式が正しくありません',
            'rating.in' => '評価は1～5の範囲で選択してください',

            'comment.required' => 'コメントを入力してください',
            'comment.max' => 'コメントは1000文字以内で入力してください',
        ];
    }
}
