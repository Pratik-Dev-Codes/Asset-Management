<?php

// Read the current .env file
$envPath = __DIR__ . '/.env';
$envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

// Remove any existing configurations we want to update
$patterns = [
    "/^SANCTUM_STATEFUL_DOMAINS=.*$/m",
    "/^SESSION_DOMAIN=.*$/m",
    "/^SANCTUM_PREFIX=.*$/m",
    "/^SESSION_DRIVER=.*$/m",
    "/^SESSION_LIFETIME=.*$/m",
    "/^SESSION_SECURE_COOKIE=.*$/m"
];

$envContent = preg_replace($patterns, '', $envContent);

// Add or update configurations
$configs = [
    'SANCTUM_STATEFUL_DOMAINS' => 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,localhost:8000',
    'SESSION_DOMAIN' => 'localhost',
    'SANCTUM_PREFIX' => 'api',
    'SESSION_DRIVER' => 'file',
    'SESSION_LIFETIME' => '120',
    'SESSION_SECURE_COOKIE' => 'false'
];

foreach ($configs as $key => $value) {
    $envContent .= "\n{$key}={$value}";
}

// Clean up multiple newlines
$envContent = preg_replace("/\n{3,}/", "\n\n", $envContent);
$envContent = trim($envContent) . "\n";

// Write back to .env file
file_put_contents($envPath, $envContent);

echo "Environment file updated successfully.\n";
