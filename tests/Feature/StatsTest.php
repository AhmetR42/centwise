<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_summary(): void
    {
        $user = User::factory()->create();
        $salary = Category::factory()->income()->for($user)->create(['name' => 'Salary']);
        $food = Category::factory()->for($user)->create(['name' => 'Food', 'budget' => 20000]);
        $add = fn (Category $category, int $cents, string $date) => Transaction::factory()
            ->for($user)->for($category)->create(['amount' => $cents, 'occurred_on' => $date]);

        $add($salary, 200000, '2026-10-01');
        $add($food, 5000, '2026-10-02');
        $add($food, 10000, '2026-10-02');
        $add($food, 7000, '2026-09-15');
        Transaction::factory()->create(['amount' => 99999, 'occurred_on' => '2026-10-02']); // someone else

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/stats/summary?month=2026-10')->assertOk();

        $response->assertJsonPath('income', 2000)
            ->assertJsonPath('expense', 150)
            ->assertJsonPath('balance', 1850)
            ->assertJsonPath('savings_rate', 92.5)
            ->assertJsonPath('transactions_count', 3)
            ->assertJsonPath('previous.expense', 70)
            ->assertJsonPath('budget_total', 200)
            ->assertJsonCount(31, 'daily')
            ->assertJsonPath('daily.1', ['date' => '2026-10-02', 'income' => 0, 'expense' => 150]);

        $food = collect($response->json('categories'))->firstWhere('name', 'Food');
        $this->assertSame(150, $food['total']);
        $this->assertEquals(75, $food['budget_used']);
    }

    public function test_trend_over_several_months(): void
    {
        $this->travelTo('2026-10-14');
        $user = User::factory()->create();
        $food = Category::factory()->for($user)->create();
        Transaction::factory()->for($user)->for($food)->create(['amount' => 1000, 'occurred_on' => '2026-08-03']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/stats/trend?months=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0', ['month' => '2026-08', 'income' => 0, 'expense' => 10]);
    }
}
