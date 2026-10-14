<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_create_with_a_monthly_budget(): void
    {
        $this->postJson('/api/v1/categories', [
            'name' => 'Pets',
            'type' => 'expense',
            'color' => '#aa8844',
            'icon' => 'paw',
            'budget' => 45.5,
        ])->assertCreated()->assertJsonPath('data.budget', 45.5);

        $this->assertDatabaseHas('categories', ['name' => 'Pets', 'budget' => 4550]);
    }

    public function test_names_are_unique_per_user_and_type(): void
    {
        Category::factory()->for($this->user)->create(['name' => 'Gifts', 'type' => 'expense']);
        Category::factory()->create(['name' => 'Pets']);

        $payload = ['type' => 'expense', 'color' => '#aa8844', 'icon' => 'gift'];

        $this->postJson('/api/v1/categories', ['name' => 'Gifts'] + $payload)->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/categories', ['name' => 'Gifts', 'type' => 'income'] + $payload)->assertCreated();
        $this->postJson('/api/v1/categories', ['name' => 'Pets'] + $payload)->assertCreated();
    }

    public function test_invalid_colour_and_icon_are_rejected(): void
    {
        $this->postJson('/api/v1/categories', [
            'name' => 'Pets', 'type' => 'expense', 'color' => 'red', 'icon' => 'unicorn',
        ])->assertJsonValidationErrors(['color', 'icon']);
    }

    public function test_list_includes_transaction_counts(): void
    {
        $category = Category::factory()->for($this->user)->create();
        Transaction::factory()->count(3)->for($this->user)->for($category)->create();

        $this->getJson('/api/v1/categories')->assertJsonPath('data.0.transactions_count', 3);
    }

    public function test_deleting_a_used_category_needs_confirmation(): void
    {
        $category = Category::factory()->for($this->user)->create();
        Transaction::factory()->count(2)->for($this->user)->for($category)->create();

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertConflict()
            ->assertJsonPath('transactions_count', 2);

        $this->deleteJson("/api/v1/categories/{$category->id}?with_transactions=1")->assertNoContent();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_other_users_categories_are_hidden(): void
    {
        $foreign = Category::factory()->create();

        $this->getJson("/api/v1/categories/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/categories/{$foreign->id}", ['name' => 'Mine'])->assertNotFound();
    }
}
