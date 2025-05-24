<?php

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'databases' => ['neepco_ams', 'neepco_asset_management']
];

// Function to get database connection
function getConnection($config, $dbName) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Error connecting to database {$dbName}: " . $e->getMessage() . "\n";
        return null;
    }
}

// Function to get all tables in a database
function getTables($pdo) {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Function to get table structure
function getTableStructure($pdo, $table) {
    $structure = [];
    $result = $pdo->query("DESCRIBE `$table`");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $structure[$row['Field']] = [
            'type' => $row['Type'],
            'null' => $row['Null'],
            'key' => $row['Key'],
            'default' => $row['Default'],
            'extra' => $row['Extra']
        ];
    }
    return $structure;
}

// Function to compare two arrays recursively
function arrayRecursiveDiff($a1, $a2) {
    $result = [];
    foreach ($a1 as $key => $value) {
        if (array_key_exists($key, $a2)) {
            if (is_array($value) && is_array($a2[$key])) {
                $diff = arrayRecursiveDiff($value, $a2[$key]);
                if (count($diff)) {
                    $result[$key] = $diff;
                }
            } elseif ($value !== $a2[$key]) {
                $result[$key] = $value;
            }
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

// Main execution
echo "Comparing databases...\n";

// Connect to both databases
$dbs = [];
$tables = [];
$structures = [];

foreach ($config['databases'] as $dbName) {
    $pdo = getConnection($config, $dbName);
    if ($pdo) {
        $dbs[$dbName] = $pdo;
        $tables[$dbName] = getTables($pdo);
        
        // Get structure for each table
        $structures[$dbName] = [];
        foreach ($tables[$dbName] as $table) {
            $structures[$dbName][$table] = getTableStructure($pdo, $table);
        }
        
        echo "Found " . count($tables[$dbName]) . " tables in {$dbName}\n";
    } else {
        echo "Skipping database: {$dbName} (connection failed)\n";
    }
}

// Compare tables
if (count($dbs) < 2) {
    echo "Need at least 2 databases to compare.\n";
    exit(1);
}

$dbNames = array_keys($dbs);
$allTables = array_unique(array_merge($tables[$dbNames[0]], $tables[$dbNames[1]]));

echo "\n=== Table Comparison ===\n";

$diffFound = false;
foreach ($allTables as $table) {
    $inDb1 = in_array($table, $tables[$dbNames[0]]);
    $inDb2 = in_array($table, $tables[$dbNames[1]]);
    
    if ($inDb1 && !$inDb2) {
        echo "- Table '{$table}' exists in {$dbNames[0]} but not in {$dbNames[1]}\n";
        $diffFound = true;
    } elseif (!$inDb1 && $inDb2) {
        echo "- Table '{$table}' exists in {$dbNames[1]} but not in {$dbNames[0]}\n";
        $diffFound = true;
    } else {
        // Compare table structures
        $struct1 = $structures[$dbNames[0]][$table] ?? [];
        $struct2 = $structures[$dbNames[1]][$table] ?? [];
        
        $diff = arrayRecursiveDiff($struct1, $struct2);
        if (!empty($diff)) {
            echo "- Differences in table '{$table}':\n";
            print_r($diff);
            $diffFound = true;
        }
    }
}

if (!$diffFound) {
    echo "No differences found between the databases.\n";
}

// Close connections
foreach ($dbs as $pdo) {
    $pdo = null;
}

echo "\nComparison complete.\n";
