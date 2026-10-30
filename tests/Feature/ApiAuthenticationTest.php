<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_a_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'rina@example.com',
            'password' => 'secret-password',
            'role' => 'kasir',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rina@example.com',
            'password' => 'secret-password',
            'device_name' => 'postman',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'kasir')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'postman',
        ]);
    }

    public function test_invalid_credentials_do_not_issue_a_token(): void
    {
        User::factory()->create([
            'email' => 'rina@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'rina@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_api_routes_require_a_valid_token_and_logout_revokes_it(): void
    {
        $this->getJson('/api/books')->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('postman')->plainTextToken;
        $accessTokenId = $user->tokens()->firstOrFail()->id;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessTokenId]);
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/user')->assertUnauthorized();
    }
}
