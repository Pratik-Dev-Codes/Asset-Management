<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });

        // Create locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('zip_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create asset_categories table
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('eula_id')->nullable();
            $table->boolean('checkin_email')->default(false);
            $table->boolean('require_acceptance')->default(false);
            $table->boolean('use_default_eula')->default(false);
            $table->boolean('checkin_email_required')->default(false);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('zip_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create assets table
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->nullable()->unique();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('status');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->string('asset_condition')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->string('warranty_provider')->nullable();
            $table->text('warranty_details')->nullable();
            $table->string('depreciation_method')->nullable();
            $table->integer('expected_lifetime_years')->nullable();
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->string('depreciation_frequency')->default('yearly');
            $table->string('insurer_company')->nullable();
            $table->string('policy_number')->nullable();
            $table->text('coverage_details')->nullable();
            $table->date('insurance_start_date')->nullable();
            $table->date('insurance_end_date')->nullable();
            $table->decimal('premium_amount', 15, 2)->nullable();
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('assigned_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('category_id')->references('id')->on('asset_categories');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create asset_attachments table
        Schema::create('asset_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
            
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        // Create maintenance_logs table
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('title');
            $table->text('description');
            $table->date('maintenance_date');
            $table->date('completion_date')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('status');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
        });

        // Create asset_history table
        Schema::create('asset_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();
            
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order of creation
        Schema::dropIfExists('asset_history');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('asset_attachments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
