<?php

function updateFilesInDirectory($dir, $oldDbName, $newDbName) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $extensions = ['php', 'env', 'json', 'yaml', 'yml', 'md', 'sql'];
    $excludedDirs = ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache'];
    $excludedFiles = ['.env'];

    $count = 0;

    foreach ($files as $file) {
        $path = $file->getPathname();
        $relativePath = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $path);
        
        // Skip excluded directories
        $skip = false;
        foreach ($excludedDirs as $excludedDir) {
            if (strpos($relativePath, $excludedDir) === 0) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        
        // Skip excluded files
        if (in_array(basename($path), $excludedFiles)) {
            continue;
        }

        // Check file extension
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, $extensions)) {
            continue;
        }

        // Read file content
        $content = file_get_contents($path);
        if ($content === false) continue;

        // Replace database name
        $newContent = str_replace($oldDbName, $newDbName, $content, $replaceCount);
        
        if ($replaceCount > 0) {
            // Backup original file
            if (!file_exists($path . '.bak')) {
                file_put_contents($path . '.bak', $content);
            }
            
            // Update file
            file_put_contents($path, $newContent);
            $count++;
            echo "Updated: $path\n";
        }
    }

    return $count;
}

// Main execution
$oldDbName = 'neepco_ams';
$newDbName = 'neepco_ams';

$projectDir = __DIR__;

// Update config files
echo "Updating database name from '$oldDbName' to '$newDbName'...\n";
$filesUpdated = updateFilesInDirectory($projectDir, $oldDbName, $newDbName);

echo "\nUpdate complete! $filesUpdated files were updated.\n";
echo "Note: Original files were backed up with .bak extension.\n";

// Update .env file if it exists
$envFile = $projectDir . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $newEnvContent = preg_replace(
        '/^DB_DATABASE=.*$/m',
        'DB_DATABASE=' . $newDbName,
        $envContent
    );
    
    if ($newEnvContent !== $envContent) {
        file_put_contents($envFile . '.bak', $envContent);
        file_put_contents($envFile, $newEnvContent);
        echo "Updated: $envFile\n";
        $filesUpdated++;
    }
}

echo "\nTotal files updated: $filesUpdated\n";
