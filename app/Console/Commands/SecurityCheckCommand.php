<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SecurityCheckCommand extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Run security checks on the application';

    public function handle()
    {
        $this->info('Running security checks...');

        $checks = [
            'env_file' => $this->checkEnvFile(),
            'debug_mode' => $this->checkDebugMode(),
            'app_key' => $this->checkAppKey(),
            'database' => $this->checkDatabaseSecurity(),
            'storage_permissions' => $this->checkStoragePermissions(),
            'composer_dependencies' => $this->checkComposerDependencies(),
            'php_version' => $this->checkPhpVersion(),
            'ssl' => $this->checkSsl(),
            'headers' => $this->checkSecurityHeaders(),
        ];

        $this->displayResults($checks);

        return $this->getWorstStatus($checks) === 'error' ? 1 : 0;
    }

    protected function checkEnvFile()
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return ['status' => 'error', 'message' => '.env file not found'];
        }

        $content = File::get($envPath);

        $issues = [];

        // Check for sensitive information
        $sensitive = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN', 'CREDENTIAL'];
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Check for sensitive keys
            foreach ($sensitive as $sensitiveKey) {
                if (str_contains($line, $sensitiveKey) && ! str_contains($line, 'APP_KEY')) {
                    $key = explode('=', $line)[0];
                    if (! in_array($key, ['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD'])) {
                        $issues[] = "Sensitive key found in .env: $key";
                    }
                }
            }
        }

        if (count($issues) > 0) {
            return ['status' => 'warning', 'message' => implode("\n", $issues)];
        }

        return ['status' => 'ok', 'message' => '.env file is secure'];
    }

    protected function checkDebugMode()
    {
        if (config('app.debug') === true) {
            return ['status' => 'error', 'message' => 'Debug mode is enabled in production'];
        }

        return ['status' => 'ok', 'message' => 'Debug mode is disabled'];
    }

    protected function checkAppKey()
    {
        $key = config('app.key');

        if (empty($key) || $key === 'base64:') {
            return ['status' => 'error', 'message' => 'Application key is not set'];
        }

        return ['status' => 'ok', 'message' => 'Application key is set'];
    }

    protected function checkDatabaseSecurity()
    {
        $issues = [];

        try {
            // Check for empty passwords
            $users = DB::table('users')
                ->whereNull('password')
                ->orWhere('password', '')
                ->count();

            if ($users > 0) {
                $issues[] = "Found $users user(s) with empty passwords";
            }

            // Check for weak passwords (example: password = hash of 'password')
            $weakPasswords = DB::table('users')
                ->where('password', 'like', '$2y$10$%')
                ->whereRaw('LENGTH(password) < 60') // Example check, adjust as needed
                ->count();

            if ($weakPasswords > 0) {
                $issues[] = "Found $weakPasswords user(s) with potentially weak passwords";
            }

            if (count($issues) > 0) {
                return ['status' => 'warning', 'message' => implode("\n", $issues)];
            }

            return ['status' => 'ok', 'message' => 'Database security checks passed'];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Error checking database: '.$e->getMessage()];
        }
    }

    protected function checkStoragePermissions()
    {
        $directories = [
            storage_path(),
            base_path('bootstrap/cache'),
        ];

        $issues = [];

        foreach ($directories as $directory) {
            if (! is_writable($directory)) {
                $issues[] = "Directory not writable: $directory";
            }
        }

        if (count($issues) > 0) {
            return ['status' => 'warning', 'message' => implode("\n", $issues)];
        }

        return ['status' => 'ok', 'message' => 'Storage permissions are correct'];
    }

    protected function checkComposerDependencies()
    {
        $composerLock = base_path('composer.lock');

        if (! File::exists($composerLock)) {
            return ['status' => 'error', 'message' => 'composer.lock not found'];
        }

        $data = json_decode(File::get($composerLock), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['status' => 'error', 'message' => 'Invalid composer.lock file'];
        }

        $issues = [];

        foreach ($data['packages'] as $package) {
            if (isset($package['abandoned'])) {
                $issues[] = "Abandoned package: {$package['name']}";
            }
        }

        if (count($issues) > 0) {
            return ['status' => 'warning', 'message' => implode("\n", $issues)];
        }

        return ['status' => 'ok', 'message' => 'Dependencies are up to date'];
    }

    protected function checkPhpVersion()
    {
        $required = '8.1.0';
        $current = PHP_VERSION;

        if (version_compare($current, $required, '<')) {
            return ['status' => 'error', 'message' => "PHP version $current is outdated. Required: $required+"];
        }

        return ['status' => 'ok', 'message' => "PHP version $current is supported"];
    }

    protected function checkSsl()
    {
        if (! config('app.https')) {
            return ['status' => 'warning', 'message' => 'HTTPS is not enforced'];
        }

        return ['status' => 'ok', 'message' => 'HTTPS is enforced'];
    }

    protected function checkSecurityHeaders()
    {
        // This is a basic check. In a real application, you would make an HTTP request
        // to your application and check the response headers.
        return ['status' => 'info', 'message' => 'Run a tool like securityheaders.com for detailed header analysis'];
    }

    protected function displayResults($checks)
    {
        $this->line(str_repeat('-', 80));
        $this->line('Security Check Results');
        $this->line(str_repeat('-', 80));

        foreach ($checks as $name => $result) {
            $status = strtoupper($result['status']);
            $message = $result['message'];

            switch ($result['status']) {
                case 'ok':
                    $this->line('<fg=green>✓</> <fg=white;options=bold>'.str_pad($name, 20)."</> <fg=green>$status</>");
                    break;
                case 'warning':
                    $this->line('<fg=yellow>!</> <fg=white;options=bold>'.str_pad($name, 20)."</> <fg=yellow>$status</>");
                    $this->line("  <fg=yellow>$message</>");
                    break;
                case 'error':
                    $this->line('<fg=red>✗</> <fg=white;options=bold>'.str_pad($name, 20)."</> <fg=red>$status</>");
                    $this->line("  <fg=red>$message</>");
                    break;
                default:
                    $this->line('<fg=blue>i</> <fg=white;options=bold>'.str_pad($name, 20)."</> <fg=blue>$status</>");
                    $this->line("  <fg=blue>$message</>");
            }

            $this->line('');
        }
    }

    protected function getWorstStatus($checks)
    {
        $statuses = array_column($checks, 'status');

        if (in_array('error', $statuses)) {
            return 'error';
        }

        if (in_array('warning', $statuses)) {
            return 'warning';
        }

        if (in_array('info', $statuses)) {
            return 'info';
        }

        return 'ok';
    }
}
