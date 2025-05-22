<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FullApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected $testUser = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    /** @test */
    public function test_environment_configuration()
    {
        $this->assertEquals('testing', config('app.env'), 'Application is not in testing environment');
        $this->assertTrue(config('app.debug'), 'Debug mode is not enabled');
    }

    /** @test */
    public function test_database_connection()
    {
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true, 'Database connection successful');
        } catch (\Exception $e) {
            $this->fail('Could not connect to the database: ' . $e->getMessage());
        }
    }

    /** @test */
    public function test_basic_routes()
    {
        $routes = [
            '/',
            '/login',
            '/register',
            '/password/reset',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(500, $response->status(), "Route {$route} returned server error");
            $this->assertNotEquals(404, $response->status(), "Route {$route} not found");
        }
    }

    /** @test */
    public function test_authentication()
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => $this->testUser['name'],
            'email' => $this->testUser['email'],
            'password' => Hash::make($this->testUser['password']),
        ]);

        // Test login with invalid credentials
        $response = $this->post('/login', [
            'email' => $this->testUser['email'],
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors('email');

        // Test login with valid credentials
        $response = $this->post('/login', [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password'],
        ]);
        $this->assertAuthenticated();

        // Test logout
        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function test_protected_routes()
    {
        $protectedRoutes = [
            '/dashboard',
            '/profile',
        ];

        // Test unauthenticated access
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }

        // Test authenticated access
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(500, $response->status(), "Protected route {$route} returned server error");
            $this->assertNotEquals(404, $response->status(), "Protected route {$route} not found");
        }
    }

    /** @test */
    public function test_api_authentication()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('password'),
        ]);

        // Get CSRF token
        $response = $this->get('/sanctum/csrf-cookie');
        $xsrfToken = $this->extractCookie($response, 'XSRF-TOKEN');
        
        // Test login
        $response = $this->withHeaders([
            'X-XSRF-TOKEN' => $xsrfToken,
            'Accept' => 'application/json',
        ])->post('/login', [
            'email' => 'api@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(204); // No content response for successful login
    }

    /** @test */
    public function test_file_permissions()
    {
        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('logs'),
            bootstrap/cache(),
        ];

        foreach ($directories as $directory) {
            $this->assertDirectoryExists($directory, "Directory {$directory} does not exist");
            $this->assertDirectoryIsWritable($directory, "Directory {$directory} is not writable");
        }
    }

    /** @test */
    public function test_environment_variables()
    {
        $requiredEnvVars = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_DEBUG',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
        ];

        foreach ($requiredEnvVars as $var) {
            $this->assertNotEmpty(
                env($var),
                "Environment variable {$var} is not set"
            );
        }
    }

    /** @test */
    public function test_mail_configuration()
    {
        $this->assertNotEmpty(
            config('mail.mailers.smtp.host'),
            'Mail host is not configured'
        );
        $this->assertNotEmpty(
            config('mail.from.address'),
            'Mail from address is not configured'
        );
    }

    /** @test */
    public function test_session_configuration()
    {
        $this->assertNotEmpty(
            config('session.driver'),
            'Session driver is not configured'
        );
        $this->assertNotEmpty(
            config('session.lifetime'),
            'Session lifetime is not configured'
        );
    }

    /** @test */
    public function test_cache_configuration()
    {
        $this->assertNotEmpty(
            config('cache.default'),
            'Cache driver is not configured'
        );
    }

    /** @test */
    public function test_queue_configuration()
    {
        $this->assertNotEmpty(
            config('queue.default'),
            'Queue connection is not configured'
        );
    }

    /** @test */
    public function test_storage_link()
    {
        $this->assertTrue(
            file_exists(public_path('storage')),
            'Storage link does not exist. Run: php artisan storage:link'
        );
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
