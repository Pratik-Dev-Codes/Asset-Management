<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->string('type')->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_public')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }

    /**
     * Seed default settings.
     */
    protected function seedDefaultSettings(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app.name',
                'value' => 'Asset Management System',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Application name',
            ],
            [
                'key' => 'app.timezone',
                'value' => 'UTC',
                'group' => 'general',
                'type' => 'select',
                'options' => [
                    'UTC' => 'UTC',
                    'Asia/Kolkata' => 'Asia/Kolkata',
                    'America/New_York' => 'America/New_York',
                    'Europe/London' => 'Europe/London',
                ],
                'description' => 'Application timezone',
            ],

            // Email Settings
            [
                'key' => 'mail.mailers.smtp.host',
                'value' => 'smtp.mailtrap.io',
                'group' => 'email',
                'type' => 'text',
                'description' => 'SMTP host',
            ],
            [
                'key' => 'mail.mailers.smtp.port',
                'value' => '2525',
                'group' => 'email',
                'type' => 'number',
                'description' => 'SMTP port',
            ],
            [
                'key' => 'mail.mailers.smtp.encryption',
                'value' => 'tls',
                'group' => 'email',
                'type' => 'select',
                'options' => [
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    'none' => 'None',
                ],
                'description' => 'SMTP encryption',
            ],
            [
                'key' => 'mail.mailers.smtp.username',
                'value' => '',
                'group' => 'email',
                'type' => 'text',
                'description' => 'SMTP username',
            ],
            [
                'key' => 'mail.mailers.smtp.password',
                'value' => '',
                'group' => 'email',
                'type' => 'password',
                'description' => 'SMTP password',
            ],
            [
                'key' => 'mail.from.address',
                'value' => 'noreply@example.com',
                'group' => 'email',
                'type' => 'email',
                'description' => 'Default sender email',
            ],
            [
                'key' => 'mail.from.name',
                'value' => 'Asset Management System',
                'group' => 'email',
                'type' => 'text',
                'description' => 'Default sender name',
            ],

            // Asset Settings
            [
                'key' => 'assets.default_status',
                'value' => 'available',
                'group' => 'assets',
                'type' => 'select',
                'options' => [
                    'available' => 'Available',
                    'in_use' => 'In Use',
                    'maintenance' => 'Under Maintenance',
                    'retired' => 'Retired',
                ],
                'description' => 'Default status for new assets',
            ],
            [
                'key' => 'assets.auto_assign_asset_number',
                'value' => '1',
                'group' => 'assets',
                'type' => 'checkbox',
                'description' => 'Automatically generate asset numbers',
            ],
            [
                'key' => 'assets.asset_number_prefix',
                'value' => 'AST-',
                'group' => 'assets',
                'type' => 'text',
                'description' => 'Prefix for auto-generated asset numbers',
            ],

            // Notification Settings
            [
                'key' => 'notifications.asset_due_for_maintenance',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'checkbox',
                'description' => 'Send notifications for assets due for maintenance',
            ],
            [
                'key' => 'notifications.asset_assigned',
                'value' => '1',
                'group' => 'notifications',
                'type' => 'checkbox',
                'description' => 'Send notifications when assets are assigned',
            ],

            // Security Settings
            [
                'key' => 'security.password_expiry_days',
                'value' => '90',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Number of days before passwords expire',
            ],
            [
                'key' => 'security.login_attempts',
                'value' => '5',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Maximum number of login attempts before lockout',
            ],
            [
                'key' => 'security.two_factor_auth',
                'value' => '0',
                'group' => 'security',
                'type' => 'checkbox',
                'description' => 'Enable two-factor authentication',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'options' => json_encode($setting['options'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
};
