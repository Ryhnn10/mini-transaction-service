<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure(['message']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login_and_get_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_refresh_token_requires_authentication()
    {
        $response = $this->postJson('/api/refresh');
        $response->assertStatus(401);
    }

    public function test_logout_requires_authentication()
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }

    public function test_user_can_logout_successfully()
{
      /** @var \App\Models\User $user */
    $user = \App\Models\User::factory()->create();
    $token = auth('api')->login($user);

    $response = $this->postJson('/api/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
}

public function test_user_can_refresh_token()
{
      /** @var \App\Models\User $user */
    $user = \App\Models\User::factory()->create();
    $token = auth('api')->login($user);

    $response = $this->postJson('/api/refresh', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
}

}
