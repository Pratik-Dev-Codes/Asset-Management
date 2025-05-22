<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JavaScriptDataTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthenticated_users_can_access_javascript_data()
    {
        $response = $this->get('/js/laravel-data.js');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
        $this->assertStringContainsString('window.laravelData', $response->getContent());
        $this->assertStringContainsString('csrfToken', $response->getContent());
        $this->assertStringContainsString('user:null', str_replace(' ', '', $response->getContent()));
    }

    /** @test */
    public function authenticated_users_get_their_data_in_javascript()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)
                         ->get('/js/laravel-data.js');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
        $this->assertStringContainsString('window.laravelData', $response->getContent());
        $this->assertStringContainsString('csrfToken', $response->getContent());
        $this->assertStringContainsString('"name":"Test User"', $response->getContent());
        $this->assertStringContainsString('"email":"test@example.com"', $response->getContent());
    }

    /** @test */
    public function csrf_token_is_available_in_javascript()
    {
        $response = $this->get('/');
        $token = csrf_token();
        
        $response->assertStatus(200);
        $this->assertStringContainsString($token, $response->getContent());
    }
}
