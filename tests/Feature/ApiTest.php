<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $baseUrl = 'http://localhost:8000';
    protected $testUser = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        User::create([
            'name' => $this->testUser['name'],
            'email' => $this->testUser['email'],
            'password' => Hash::make($this->testUser['password']),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
    }

    /** @test */
    public function test_api_endpoints()
    {
        $endpoints = [
            ['method' => 'GET', 'url' => '/api/user', 'auth' => true, 'expected_status' => 200],
            ['method' => 'GET', 'url' => '/sanctum/csrf-cookie', 'auth' => false, 'expected_status' => 204],
            // Add more API endpoints to test
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->makeRequest(
                $endpoint['method'],
                $endpoint['url'],
                [],
                $endpoint['auth']
            );

            $this->assertEquals(
                $endpoint['expected_status'],
                $response->getStatusCode(),
                "Failed to access {$endpoint['method']} {$endpoint['url']}"
            );
        }
    }

    /** @test */
    public function test_authentication_flow()
    {
        // Get CSRF token
        $response = $this->getJson('/sanctum/csrf-cookie');
        $xsrfToken = $this->extractCookie($response, 'XSRF-TOKEN');
        
        // Test login with invalid credentials
        $response = $this->postJson('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ], [
            'X-XSRF-TOKEN' => $xsrfToken,
            'Referer' => $this->baseUrl,
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response->assertStatus(422);

        // Test login with valid credentials
        $response = $this->postJson('/login', [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password'],
        ], [
            'X-XSRF-TOKEN' => $xsrfToken,
            'Referer' => $this->baseUrl,
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response->assertStatus(204);
        $this->assertAuthenticated();

        // Test getting authenticated user
        $response = $this->getJson('/api/user', [
            'X-XSRF-TOKEN' => $xsrfToken,
            'Referer' => $this->baseUrl,
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'email' => $this->testUser['email']
        ]);

        // Test logout
        $response = $this->postJson('/logout', [], [
            'X-XSRF-TOKEN' => $xsrfToken,
            'Referer' => $this->baseUrl,
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response->assertStatus(204);
        $this->assertGuest();
    }

    protected function makeRequest($method, $url, $data = [], $authenticated = false)
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => $this->baseUrl,
        ];

        if ($authenticated) {
            $user = User::first();
            $this->actingAs($user);
        }

        switch (strtoupper($method)) {
            case 'GET':
                return $this->getJson($url, $headers);
            case 'POST':
                return $this->postJson($url, $data, $headers);
            case 'PUT':
                return $this->putJson($url, $data, $headers);
            case 'DELETE':
                return $this->deleteJson($url, $data, $headers);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }
    }

    protected function extractCookie($response, $name)
    {
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie->getValue();
            }
        }
        return null;
    }
}
