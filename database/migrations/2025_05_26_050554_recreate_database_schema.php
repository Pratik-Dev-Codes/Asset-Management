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
        // Define variables for permission tables
        $teams = false; // Set to true if using team features
        $columnNames = [
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ];
        
        // Drop all tables in the correct order to avoid foreign key constraint issues
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        
        // Drop all other tables in reverse order of their dependencies
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('users');
        
        // Recreate the users table
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
        
        // Recreate the permissions tables
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        
        Schema::create('model_has_permissions', function (Blueprint $table) use ($teams) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] = 'model_id');
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });
        
        Schema::create('model_has_roles', function (Blueprint $table) use ($teams) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] = 'model_id');
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], 'role_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });
        
        Schema::create('role_has_permissions', function (Blueprint $table) use ($teams) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });
        
        // Recreate the personal_access_tokens table
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
        
        // Recreate the locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
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
        
        // Recreate the departments table
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
        
        // Recreate the suppliers table
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
        
        // Recreate the asset_categories table
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('eula_id')->nullable();
            $table->boolean('checkin_email')->default(false);
            $table->boolean('require_acceptance')->default(false);
            $table->boolean('use_default_eula')->default(false);
            $table->boolean('checkin_email_required')->default(false);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Recreate the assets table
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('status');
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
            $table->integer('expected_lifetime_years')->nullable();
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->string('depreciation_frequency')->default('yearly');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('assigned_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('category_id')->references('id')->on('asset_categories')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive operation, so we'll leave this empty
        // to prevent accidental data loss in production
    }
};
