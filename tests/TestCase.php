<?php

namespace Tests;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Assert as PHPUnit;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Base test case for all tests
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker, WithoutMiddleware;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Test admin user
     *
     * @var \App\Models\User
     */
    protected $admin;

    /**
     * Test regular user
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seeders
        $this->artisan('migrate:fresh');
        $this->seed();

        // Set up test users and roles
        $this->setUpTestUsers();

        // Fake storage
        Storage::fake('reports');
    }

    /**
     * Set up test users with roles and permissions
     *
     * @return void
     */
    protected function setUpTestUsers()
    {
        // Create admin user
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create regular user
        $this->user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->admin->assignRole($adminRole);
        $this->user->assignRole($userRole);

        // Grant all permissions to admin
        $permissions = [
            'view reports', 'create reports', 'edit reports', 'delete reports',
            'generate reports', 'schedule reports', 'view report files',
            'download report files', 'delete report files', 'cleanup report files',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole->givePermissionTo(Permission::all());
    }

    /**
     * Create a test report
     *
     * @param  array  $attributes
     * @return \App\Models\Report
     */
    protected function createTestReport($attributes = [])
    {
        return Report::factory()->create(array_merge([
            'created_by' => $this->admin->id,
            'status' => 'completed',
        ], $attributes));
    }

    /**
     * Create a test report file
     *
     * @param  \App\Models\Report  $report
     * @param  array  $attributes
     * @return \App\Models\ReportFile
     */
    protected function createTestReportFile($report, $attributes = [])
    {
        $fileName = 'test_report_'.time().'.xlsx';
        $filePath = 'reports/'.$fileName;

        Storage::disk('reports')->put($filePath, 'Test file content');

        return ReportFile::create(array_merge([
            'report_id' => $report->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'generated_by' => $this->admin->id,
            'expires_at' => now()->addDays(7),
        ], $attributes));
    }

    /**
     * Create HTTP headers with authentication token
     *
     * @param  array  $headers  Additional headers to include
     */
    protected function withAuthHeaders(array $headers = [], $user = null): array
    {
        $user = $user ?: $this->user ?? null;

        if (! $user) {
            $user = \App\Models\User::factory()->create();
        }

        $token = $user->createToken('test-token')->plainTextToken;

        return array_merge([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ], $headers);
    }

    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  string  $table
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $database = $this->app->make('db');
        $connection = $connection ?: $database->getDefaultConnection();
        $count = $database->connection($connection)->table($table)->where($data)->count();

        PHPUnit::assertTrue(
            $count > 0,
            "Unable to find row in database table [{$table}] that matched attributes ".json_encode($data, JSON_PRETTY_PRINT).'.'
        );

        return $this;
    }

    /**
     * Assert that the given record has been soft-deleted.
     *
     * @param  string|\Illuminate\Database\Eloquent\Model  $table
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($table instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->assertSoftDeleted($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName(), $table->getDeletedAtColumn());
        }

        $this->assertThat(
            $table,
            new \Illuminate\Testing\Constraints\SoftDeletedInDatabase(
                $this->getConnection($connection),
                $data,
                $deletedAtColumn
            )
        );

        return $this;
    }

    /**
     * Make a JSON request to the application.
     *
     * @param  string  $method
     * @param  string  $uri
     * @return \Illuminate\Testing\TestResponse
     */
    protected function jsonRequest($method, $uri, array $data = [], array $headers = [])
    {
        $response = $this->json($method, $uri, $data, $headers);

        if ($response->exception) {
            throw $response->exception;
        }

        return $response;
    }
}
