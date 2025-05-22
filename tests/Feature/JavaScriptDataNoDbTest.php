<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JavaScriptDataNoDbTest extends TestCase
{
    /** @test */
    public function javascript_data_route_returns_successful_response()
    {
        $response = $this->get('/js/laravel-data.js');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
    }

    /** @test */
    public function javascript_data_contains_expected_variables()
    {
        $response = $this->get('/js/laravel-data.js');
        $content = $response->getContent();
        
        $this->assertStringContainsString('window.laravelData', $content);
        $this->assertStringContainsString('csrfToken', $content);
        
        // For unauthenticated users, user should be null
        $this->assertStringContainsString('user:null', str_replace(' ', '', $content));
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
