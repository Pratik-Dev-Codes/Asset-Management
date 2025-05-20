<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we're in the testing environment
        $this->app['env'] = 'testing';
        
        // Enable debug mode for more detailed error messages
        config(['app.debug' => true]);
    }

    /** @test */
    public function security_headers_are_set_correctly()
    {
        $response = $this->get('/');

        // Basic security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS header should only be present in production
        if (app()->environment('production')) {
            $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        
        // CSP headers
        $response->assertHeader('Content-Security-Policy');
        
        // Legacy CSP headers for older browsers
        $response->assertHeader('X-Content-Security-Policy');
        $response->assertHeader('X-WebKit-CSP');
        
        // Additional security headers
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        
        // Expect-CT header (deprecated but still used by some browsers)
        $response->assertHeader('Expect-CT', 'enforce, max-age=30');
        
        // Cross-Origin headers
        $response->assertHeader('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-site');
    }
    
    /** @test */
    public function security_headers_are_set_on_api_routes()
    {
        $response = $this->getJson('/api/user');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
    
    /** @test */
    public function security_headers_are_set_on_authenticated_routes()
    {
        // Create a test user
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->get('/dashboard');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
