<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->route('transaction') ? 'sometimes' : 'required';

        return [
            'category_id' => [
                $required, 'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'amount' => [$required, 'numeric', 'min:0.01', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:120'],
            'occurred_on' => [$required, 'date_format:Y-m-d', 'before_or_equal:'.now()->addYear()->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Choose one of your own categories.',
        ];
    }
}
