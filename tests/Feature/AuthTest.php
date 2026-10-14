<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_a_token_and_creates_starter_categories(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sam',
            'email' => 'sam@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'currency']])
            ->assertJsonPath('user.currency', 'EUR');

        $user = User::firstWhere('email', 'sam@example.com');
        $this->assertSame(11, $user->categories()->count());
        $this->assertTrue($user->categories()->where('name', 'Groceries')->exists());
    }

    public function test_starter_categories_can_be_dutch(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sanne',
            'email' => 'sanne@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'locale' => 'nl',
        ])->assertCreated();

        $this->assertTrue(User::first()->categories()->where('name', 'Boodschappen')->exists());
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_with_correct_and_wrong_password(): void
    {
        User::factory()->create(['email' => 'sam@example.com', 'password' => 'secret-password']);

        $this->postJson('/api/v1/auth/login', ['email' => 'sam@example.com', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/login', ['email' => 'sam@example.com', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/v1/transactions')->assertUnauthorized();
    }

    public function test_demo_account_comes_with_example_data(): void
    {
        $response = $this->postJson('/api/v1/auth/demo')->assertCreated();

        $user = User::find($response->json('user.id'));
        $this->assertTrue($user->is_demo);
        $this->assertGreaterThan(50, $user->transactions()->count());
        $this->assertSame(3, $user->goals()->count());

        $this->withToken($response->json('token'))->getJson('/api/v1/me')->assertOk();
    }

    public function test_old_demo_accounts_are_pruned(): void
    {
        $old = User::factory()->create(['is_demo' => true, 'created_at' => now()->subDays(2)]);
        $fresh = User::factory()->create(['is_demo' => true]);
        $real = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->artisan('demo:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($fresh);
        $this->assertModelExists($real);
    }

    public function test_profile_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', ['currency' => 'USD', 'name' => 'New name'])
            ->assertOk()
            ->assertJsonPath('data.currency', 'USD');

        $this->patchJson('/api/v1/me', ['currency' => 'XXX'])->assertUnprocessable();

        $this->deleteJson('/api/v1/me')->assertNoContent();
        $this->assertModelMissing($user);
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->create(['email' => 'sam@example.com', 'password' => 'secret-password']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'sam@example.com', 'password' => 'secret-password'])->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
