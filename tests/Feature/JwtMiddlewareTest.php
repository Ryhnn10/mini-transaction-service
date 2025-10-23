<?php

namespace Tests\Feature;

use Tests\TestCase;

class JwtMiddlewareTest extends TestCase
{
    public function test_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/users/1/balance');

        $response->assertStatus(401)
                ->assertJson(['message' => 'Unauthenticated.']);
    }
}
