<?php

namespace Tests\Feature;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_fetch_authenticated_user(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_at',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseCount('auth_token', 1);
        $this->assertDatabaseMissing('auth_token', [
            'token_hash' => $loginResponse->json('token'),
        ]);

        $this
            ->withToken($loginResponse->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonMissing(['password']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'person@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this
            ->postJson('/api/auth/login', [
                'email' => 'person@example.com',
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('auth_token', 0);
    }

    public function test_me_requires_valid_non_revoked_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = 'test-token';

        AuthToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addHour(),
        ]);

        $this
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this
            ->withToken($plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this
            ->withToken($plainTextToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
