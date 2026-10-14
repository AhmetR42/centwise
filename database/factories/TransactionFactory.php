<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'user_id' => fn (array $attributes) => Category::find($attributes['category_id'])->user_id,
            'amount' => fake()->numberBetween(100, 10000),
            'description' => fake()->words(2, true),
            'occurred_on' => now()->toDateString(),
        ];
    }
}
