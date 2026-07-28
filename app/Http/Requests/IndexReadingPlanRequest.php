<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                Rule::enum(ReadingPlanStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => '状態の指定が不正です',
        ];
    }
}
