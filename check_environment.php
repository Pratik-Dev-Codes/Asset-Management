<?php

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    die('Error: PHP 8.0.0 or higher is required. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required = ['pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json'];
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    die('Error: The following PHP extensions are missing: ' . implode(', ', $missing));
}

// Check .env file
if (!file_exists(__DIR__ . '/.env')) {
    die('Error: .env file not found. Please copy .env.example to .env and configure it.');
}

echo "Environment check passed. Starting the application...\n";

// Start the development server
exec('php -S 127.0.0.1:8000 -t public');
