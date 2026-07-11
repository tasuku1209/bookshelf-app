<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BookIndexRequest extends FormRequest
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

            'genre_id' => [
                'nullable',
                'integer',
                'exists:genres,id',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => '検索キーワードは255文字以内で入力してください',

            'genre_id.integer' => 'ジャンルIDは数値で入力してください',
            'genre_id.exists' => '指定されたジャンルは存在しません',

            'page.integer' => 'ページ番号は数値で入力してください',
            'page.min' => 'ページ番号は1以上で入力してください',

            'per_page.integer' => '表示件数は数値で入力してください',
            'per_page.min' => '表示件数は1～100件の間で指定してください',
            'per_page.max' => '表示件数は1～100件の間で指定してください',
        ];
    }
}
