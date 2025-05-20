<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $otherUser;
    protected $report;
    protected $publicReport;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $viewAllReports = Permission::create(['name' => 'view all reports']);
        $manageReports = Permission::create(['name' => 'manage reports']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([$viewAllReports, $manageReports]);

        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo('view own reports');

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('user');

        // Create reports
        $this->report = Report::factory()->create([
            'created_by' => $this->user->id,
            'is_public' => false
        ]);

        $this->publicReport = Report::factory()->create([
            'created_by' => $this->otherUser->id,
            'is_public' => true
        ]);
    }

    /** @test */
    public function admin_can_view_any_reports()
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_own_reports()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('reports.show', $this->report->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_view_other_users_private_reports()
    {
        $otherReport = Report::factory()->create([
            'created_by' => $this->otherUser->id,
            'is_public' => false
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('reports.show', $otherReport->id));
        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_public_reports()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('reports.show', $this->publicReport->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_report()
    {
        $this->actingAs($this->admin);
        $response = $this->put(route('reports.update', $this->report->id), [
            'name' => 'Updated by Admin',
            'type' => $this->report->type,
            'columns' => $this->report->columns
        ]);
        $response->assertRedirect(route('reports.show', $this->report->id));
        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'name' => 'Updated by Admin'
        ]);
    }

    /** @test */
    public function user_can_update_own_report()
    {
        $this->actingAs($this->user);
        $response = $this->put(route('reports.update', $this->report->id), [
            'name' => 'Updated by Owner',
            'type' => $this->report->type,
            'columns' => $this->report->columns
        ]);
        $response->assertRedirect(route('reports.show', $this->report->id));
        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'name' => 'Updated by Owner'
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_reports()
    {
        $otherReport = Report::factory()->create([
            'created_by' => $this->otherUser->id
        ]);

        $this->actingAs($this->user);
        $response = $this->put(route('reports.update', $otherReport->id), [
            'name' => 'Should Not Update',
            'type' => $otherReport->type,
            'columns' => $otherReport->columns
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('reports', [
            'id' => $otherReport->id,
            'name' => 'Should Not Update'
        ]);
    }

    /** @test */
    public function admin_can_delete_any_report()
    {
        $this->actingAs($this->admin);
        $response = $this->delete(route('reports.destroy', $this->report->id));
        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseMissing('reports', ['id' => $this->report->id]);
    }

    /** @test */
    public function user_can_delete_own_report()
    {
        $this->actingAs($this->user);
        $response = $this->delete(route('reports.destroy', $this->report->id));
        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseMissing('reports', ['id' => $this->report->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_reports()
    {
        $otherReport = Report::factory()->create([
            'created_by' => $this->otherUser->id
        ]);

        $this->actingAs($this->user);
        $response = $this->delete(route('reports.destroy', $otherReport->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('reports', ['id' => $otherReport->id]);
    }
}
