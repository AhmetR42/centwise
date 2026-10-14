<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'type' => 'expense',
            'color' => fake()->hexColor(),
            'icon' => 'dot',
            'budget' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => 'income']);
    }
}
