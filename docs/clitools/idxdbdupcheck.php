<?php
set_time_limit(0);

function showHelp() {
    $help = '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '║                    🦄 Ubilling Index Check tool 🦄                          ║' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    $help .= 'Checks database tables for duplicate indexes.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php indexcheck.php' . PHP_EOL;
    $help .= '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    print($help);
}

function loadMysqlConfig() {
    $configFile = __DIR__ . '/../../config/mysql.ini';
    if (!file_exists($configFile)) {
        die("❌ Error: MySQL config file not found: $configFile" . PHP_EOL);
    }

    $config = parse_ini_file($configFile);
    if (!$config || empty($config['server']) || empty($config['username']) || !isset($config['password']) || empty($config['db'])) {
        die("❌ Error: Invalid MySQL configuration file format" . PHP_EOL);
    }

    return $config;
}

function connectToDatabase($config) {
    $mysqli = new mysqli(
        $config['server'],
        $config['username'],
        $config['password'],
        $config['db']
    );

    if ($mysqli->connect_error) {
        die("❌ Error: Connection failed: " . $mysqli->connect_error . PHP_EOL);
    }

    print("🔌 Connected to {$config['db']}" . PHP_EOL);
    return $mysqli;
}

function checkTableIndexes($mysqli, $table) {
    $ignoredTables = array(
        'cardbank',
        'nethosts'
    );

    if (in_array($table, $ignoredTables)) {
        return array();
    }
    
    $indexes = array();
    $duplicates = array();
    $cleanupQueries = array();

    
    
    $result = $mysqli->query("SHOW INDEX FROM `$table`");
    if (!$result) {
        print("❌ Error getting indexes for table '$table': " . $mysqli->error . PHP_EOL);
        return array();
    }

    while ($row = $result->fetch_assoc()) {
        $indexName = $row['Key_name'];
        $columnName = $row['Column_name'];
        
        if (!isset($indexes[$columnName])) {
            $indexes[$columnName] = array();
        }
        $indexes[$columnName][] = $indexName;
    }

    foreach ($indexes as $column => $indexList) {
        if (count($indexList) > 1) {
            $duplicates[$column] = $indexList;
            $firstIndex = array_shift($indexList);
            foreach ($indexList as $indexToRemove) {
                if ($indexToRemove !== 'PRIMARY') {
                    $cleanupQueries[] = "ALTER TABLE `$table` DROP INDEX `$indexToRemove`;";
                }
            }
        }
    }

    if (!empty($duplicates)) {
        print("⚠️ Table '$table' has duplicate indexes:" . PHP_EOL);
        foreach ($duplicates as $column => $indexList) {
            print("   Column '$column' is indexed by: " . implode(', ', $indexList) . PHP_EOL);
        }
    } else {
        print("✅ Table '$table' has no duplicate indexes" . PHP_EOL);
    }

    return $cleanupQueries;
}

function main() {
    showHelp();
    
    $startTime = microtime(true);
    $config = loadMysqlConfig();
    $mysqli = connectToDatabase($config);
    
    $result = $mysqli->query("SHOW TABLES");
    if (!$result) {
        die("❌ Error: Cannot get table list: " . $mysqli->error . PHP_EOL);
    }

    $tables = array();
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    if (empty($tables)) {
        die("❌ Error: No tables found in database" . PHP_EOL);
    }

    print("🔍 Found " . count($tables) . " tables to check" . PHP_EOL . PHP_EOL);

    $allCleanupQueries = array();
    foreach ($tables as $table) {
        $cleanupQueries = checkTableIndexes($mysqli, $table);
        $allCleanupQueries = array_merge($allCleanupQueries, $cleanupQueries);
    }

    $mysqli->close();

    if (!empty($allCleanupQueries)) {
        print(PHP_EOL . "🔧 All cleanup queries:" . PHP_EOL);
        print("=========================" . PHP_EOL);
        foreach ($allCleanupQueries as $query) {
            print($query . PHP_EOL);
        }
        print("=========================" . PHP_EOL);
    }

    $executionTime = microtime(true) - $startTime;
    print(PHP_EOL . "⏱️ Completed in " . round($executionTime, 2) . " seconds" . PHP_EOL);
}

main(); 