<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public const ICONS = [
        'briefcase', 'spark', 'gift', 'home', 'cart', 'cup', 'bus', 'repeat',
        'bag', 'heart', 'ticket', 'book', 'plane', 'paw', 'bolt', 'dot',
    ];

    public function rules(): array
    {
        $category = $this->route('category');
        $required = $category ? 'sometimes' : 'required';

        return [
            'name' => [
                $required, 'string', 'max:60',
                Rule::unique('categories')
                    ->where('user_id', $this->user()->id)
                    ->where('type', $this->input('type', $category?->type->value))
                    ->ignore($category),
            ],
            'type' => [$required, Rule::enum(CategoryType::class)],
            'color' => [$required, 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => [$required, Rule::in(self::ICONS)],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];
    }
}
