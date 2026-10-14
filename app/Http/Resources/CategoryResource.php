<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'color' => $this->color,
            'icon' => $this->icon,
            'budget' => Money::fromCents($this->budget),
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
