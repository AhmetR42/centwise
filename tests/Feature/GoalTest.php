<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_create_shows_progress_and_monthly_amount(): void
    {
        $this->travelTo('2026-10-14');

        $this->postJson('/api/v1/goals', [
            'name' => 'New laptop',
            'target' => 1200,
            'saved' => 300,
            'deadline' => '2027-01-14',
            'color' => '#6fa3d8',
        ])->assertCreated()
            ->assertJsonPath('data.progress', 25)
            ->assertJsonPath('data.months_left', 3)
            ->assertJsonPath('data.monthly_needed', 300);
    }

    public function test_deposit_and_withdraw(): void
    {
        $goal = Goal::factory()->for($this->user)->create(['target' => 100000, 'saved' => 25000]);

        $this->postJson("/api/v1/goals/{$goal->id}/deposit", ['amount' => 50])
            ->assertOk()
            ->assertJsonPath('data.saved', 300)
            ->assertJsonPath('data.progress', 30);

        // Never below zero
        $this->postJson("/api/v1/goals/{$goal->id}/deposit", ['amount' => -1000])
            ->assertJsonPath('data.saved', 0);

        $this->postJson("/api/v1/goals/{$goal->id}/deposit", ['amount' => 0])->assertUnprocessable();
    }

    public function test_other_users_goals_are_hidden(): void
    {
        $foreign = Goal::factory()->create();

        $this->postJson("/api/v1/goals/{$foreign->id}/deposit", ['amount' => 5])->assertNotFound();
        $this->deleteJson("/api/v1/goals/{$foreign->id}")->assertNotFound();
        $this->getJson('/api/v1/goals')->assertJsonCount(0, 'data');
    }
}
