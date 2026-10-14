<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->route('goal') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:60'],
            'target' => [$required, 'numeric', 'min:1', 'max:10000000'],
            'saved' => ['sometimes', 'numeric', 'min:0', 'max:10000000'],
            'deadline' => ['nullable', 'date_format:Y-m-d', 'after:today'],
            'color' => [$required, 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
