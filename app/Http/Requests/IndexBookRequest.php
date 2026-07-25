<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],

            'genre' => [
                'nullable',
                'integer',
                'exists:genres,id',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'newest',
                    'oldest',
                    'title',
                    'rating',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => '検索キーワードは255文字以内で入力してください',

            'genre.integer' => 'ジャンルIDは数値で入力してください',
            'genre.exists' => '指定されたジャンルは存在しません',

            'sort.in' => '並び順の指定が正しくありません',
        ];
    }
}
