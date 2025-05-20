<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinalSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop all existing tables if they exist
        $tables = [
            'asset_attachments',
            'asset_depreciations',
            'asset_transfers',
            'maintenance_records',
            'assets',
            'vendors',
            'locations',
            'departments',
            'asset_categories',
            'personal_access_tokens',
            'sessions',
            'password_reset_tokens',
            'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Create personal access tokens table
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Create asset categories table
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Create locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create vendors table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create assets table
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained('asset_categories');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('serial_number')->nullable();
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();

            // Purchase Information
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->string('order_number')->nullable();

            // Warranty Information
            $table->date('warranty_expires')->nullable();
            $table->text('warranty_notes')->nullable();

            // Status
            $table->enum('status', ['available', 'assigned', 'under_maintenance', 'disposed'])->default('available');

            // Additional Fields
            $table->text('notes')->nullable();
            $table->string('image')->nullable();

            // Audit Information
            $table->timestamp('last_audit_date')->nullable();
            $table->foreignId('audited_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });

        // Create maintenance records table
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['preventive', 'corrective', 'inspection', 'upgrade']);
            $table->dateTime('start_date');
            $table->dateTime('completion_date')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->decimal('cost', 10, 2)->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create asset transfers table
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->foreignId('from_department_id')->nullable()->constrained('departments');
            $table->foreignId('to_department_id')->nullable()->constrained('departments');
            $table->foreignId('from_user_id')->nullable()->constrained('users');
            $table->foreignId('to_user_id')->nullable()->constrained('users');
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignId('initiated_by')->constrained('users');
            $table->timestamps();
        });

        // Create asset depreciations table
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->decimal('purchase_cost', 10, 2);
            $table->decimal('salvage_value', 10, 2)->default(0);
            $table->integer('useful_life_years');
            $table->date('start_date');
            $table->timestamps();
            $table->softDeletes();
        });

        // Create asset attachments table
        Schema::create('asset_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        // Add indexes for better performance
        Schema::table('assets', function (Blueprint $table) {
            $table->index('asset_tag');
            $table->index('status');
            $table->index('purchase_date');
            $table->index('warranty_expires');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->index('status');
            $table->index('start_date');
            $table->index('completion_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This is a one-way migration for fresh installs
        throw new \RuntimeException('This migration cannot be reversed. Use a fresh database instead.');
    }
}
