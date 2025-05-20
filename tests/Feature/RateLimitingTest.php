<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_rate_limiting_works_correctly()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Try to log in 6 times (limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            $response->assertStatus(302);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function api_rate_limiting_works_correctly()
    {
        // Create a test user
        $user = User::factory()->create();

        // Generate a token for the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 60 requests (API limit is 60 per minute)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->get('/api/v1/user');

            $response->assertStatus(200);
        }

        // 61st request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->get('/api/v1/user');

        $response->assertStatus(429);
    }

    /** @test */
    public function password_reset_rate_limiting_works_correctly()
    {
        // Try to request password reset 4 times (limit is 3 per minute)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/forgot-password', [
                'email' => 'test@example.com',
            ]);

            $response->assertStatus(302);
        }

        // 4th attempt should be rate limited
        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
    }
}
