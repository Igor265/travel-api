<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password2',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('password')]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password2',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_revokes_previous_tokens(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->createToken('old-token');

        $this->assertCount(1, $user->tokens);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out.']);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/logout')
            ->assertStatus(200);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->deleteJson('/api/logout')->assertStatus(401);
    }

    public function test_register_response_contains_user_fields(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'created_at'], 'token']);
    }
}
