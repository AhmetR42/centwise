<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $groceries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->groceries = Category::factory()->for($this->user)->create(['name' => 'Groceries']);
        Sanctum::actingAs($this->user);
    }

    public function test_create_stores_amount_in_cents(): void
    {
        $this->postJson('/api/v1/transactions', [
            'category_id' => $this->groceries->id,
            'amount' => 12.35,
            'description' => 'Albert Heijn',
            'occurred_on' => '2026-10-03',
        ])->assertCreated()
            ->assertJsonPath('data.amount', 12.35)
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.category.name', 'Groceries');

        $this->assertDatabaseHas('transactions', ['amount' => 1235, 'user_id' => $this->user->id]);
    }

    public function test_cannot_use_someone_elses_category(): void
    {
        $foreign = Category::factory()->create();

        $this->postJson('/api/v1/transactions', [
            'category_id' => $foreign->id,
            'amount' => 5,
            'occurred_on' => '2026-10-03',
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_amount_must_be_positive(): void
    {
        $this->postJson('/api/v1/transactions', [
            'category_id' => $this->groceries->id,
            'amount' => 0,
            'occurred_on' => '2026-10-03',
        ])->assertJsonValidationErrors('amount');
    }

    public function test_other_users_transactions_are_hidden(): void
    {
        $foreign = Transaction::factory()->create();

        $this->getJson("/api/v1/transactions/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/v1/transactions/{$foreign->id}", ['amount' => 1])->assertNotFound();
        $this->deleteJson("/api/v1/transactions/{$foreign->id}")->assertNotFound();
        $this->getJson('/api/v1/transactions')->assertJsonCount(0, 'data');
    }

    public function test_update_and_delete(): void
    {
        $transaction = Transaction::factory()->for($this->user)->for($this->groceries)->create(['amount' => 500]);

        $this->putJson("/api/v1/transactions/{$transaction->id}", ['amount' => 7.5])
            ->assertOk()
            ->assertJsonPath('data.amount', 7.5);

        $this->deleteJson("/api/v1/transactions/{$transaction->id}")->assertNoContent();
        $this->assertModelMissing($transaction);
    }

    public function test_list_can_be_filtered_searched_and_sorted(): void
    {
        $salary = Category::factory()->income()->for($this->user)->create(['name' => 'Salary']);
        Transaction::factory()->for($this->user)->for($this->groceries)->create(['amount' => 2000, 'description' => 'Jumbo', 'occurred_on' => '2026-09-10']);
        Transaction::factory()->for($this->user)->for($this->groceries)->create(['amount' => 4000, 'description' => 'Lidl', 'occurred_on' => '2026-10-02']);
        Transaction::factory()->for($this->user)->for($salary)->create(['amount' => 150000, 'description' => 'Pay', 'occurred_on' => '2026-10-01']);

        $this->getJson('/api/v1/transactions?month=2026-10')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/transactions?type=income')->assertJsonCount(1, 'data')->assertJsonPath('data.0.description', 'Pay');
        $this->getJson('/api/v1/transactions?q=lid')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/transactions?q=groc')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/transactions?category_id='.$this->groceries->id.'&sort=lowest')
            ->assertJsonPath('data.0.description', 'Jumbo');
        $this->getJson('/api/v1/transactions?per_page=5')->assertJsonPath('meta.per_page', 5);
        $this->getJson('/api/v1/transactions?month=october')->assertUnprocessable();
    }

    public function test_export_as_csv(): void
    {
        Transaction::factory()->for($this->user)->for($this->groceries)->create([
            'amount' => 1999, 'description' => 'Jumbo', 'occurred_on' => '2026-10-02',
        ]);

        $response = $this->get('/api/v1/transactions/export?month=2026-10');

        $response->assertOk()->assertDownload('centwise-2026-10.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Date,Type,Category,Description,Amount', $csv);
        $this->assertStringContainsString('2026-10-02,expense,Groceries,Jumbo,-19.99', $csv);
    }
}
