<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JsAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_javascript_without_user_data_when_not_authenticated()
    {
        $response = $this->get('/js/laravel-data.js');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
        $response->assertSee('window.laravelData = ');
        $response->assertSee('"user":null');
        $response->assertSee('"csrfToken":"' . csrf_token() . '"');
    }

    /** @test */
    public function it_returns_javascript_with_user_data_when_authenticated()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->get('/js/laravel-data.js');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
        $response->assertSee('window.laravelData = ');
        $response->assertSee('"user":{"id":' . $user->id . ',"name":"Test User","email":"test@example.com"}');
        $response->assertSee('"csrfToken":"' . csrf_token() . '"');
    }

    /** @test */
    public function it_sets_correct_cache_headers()
    {
        $response = $this->get('/js/laravel-data.js');
        
        $response->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->assertHeader('Pragma', 'no-cache');
    }
}
