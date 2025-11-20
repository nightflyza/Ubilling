<?php
set_time_limit(0);

$targetEncoding = 'utf8mb3_general_ci';
$targetEngine='MyISAM';

function showHelp() {
    $help = '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '║                    🦄 Ubilling DB encoding check tool 🦄                     ║' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    $help .= 'Checks database tables for encoding and engine compliance.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php dbencodingcheck.php' . PHP_EOL;
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

function isUtf8Encoding($collation) {
    if (empty($collation)) {
        return false;
    }
    $collationLower = strtolower($collation);
    return (strpos($collationLower, 'utf8') === 0);
}

function checkTableEncoding($mysqli, $table, $targetEncoding, $targetEngine) {
    $ignoredTables = array(
        'cardbank',
        'nethosts'
    );

    if (in_array($table, $ignoredTables)) {
        return array('engine' => array(), 'encoding' => array());
    }
    
    $engineQueries = array();
    $encodingQueries = array();
    $issues = array();

    $dbName = $mysqli->query("SELECT DATABASE()")->fetch_row()[0];
    $stmt = $mysqli->prepare("SELECT TABLE_COLLATION, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    $stmt->bind_param("ss", $dbName, $table);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        print("❌ Error: Table '$table' not found in information_schema" . PHP_EOL);
        return array('engine' => array(), 'encoding' => array());
    }
    
    $row = $result->fetch_assoc();
    if ($row['TABLE_TYPE'] !== 'BASE TABLE') {
        return array('engine' => array(), 'encoding' => array());
    }
    
    $currentCollation = $row['TABLE_COLLATION'];
    $currentEngine = $row['ENGINE'];
    
    if ($currentEngine === null) {
        $currentEngine = '';
    }
    
    $needsEncodingFix = !isUtf8Encoding($currentCollation);
    $needsEngineFix = (strtoupper(trim($currentEngine)) !== strtoupper(trim($targetEngine)));
    
    if ($needsEncodingFix or $needsEngineFix) {
        if ($needsEngineFix) {
            $issues[] = "engine: $currentEngine (target: $targetEngine)";
            $engineQueries[] = "ALTER TABLE `$table` ENGINE=$targetEngine;";
        }
        
        if ($needsEncodingFix) {
            $issues[] = "encoding: $currentCollation (target: $targetEncoding)";
            $targetCharset = (strpos(strtolower($targetEncoding), 'utf8mb4') === 0) ? 'utf8mb4' : 'utf8';
            $encodingQueries[] = "ALTER TABLE `$table` CONVERT TO CHARACTER SET $targetCharset COLLATE $targetEncoding;";
        }
        
        print("⚠️ Table '$table' has issues: " . implode(', ', $issues) . PHP_EOL);
    } else {
        print("✅ Table '$table' has correct encoding ($currentCollation) and engine ($currentEngine)" . PHP_EOL);
    }

    return array('engine' => $engineQueries, 'encoding' => $encodingQueries);
}

function main() {
    global $targetEncoding, $targetEngine;
    
    showHelp();
    
    $startTime = microtime(true);
    $config = loadMysqlConfig();
    $mysqli = connectToDatabase($config);
    
    $dbName = $mysqli->query("SELECT DATABASE()")->fetch_row()[0];
    $stmt = $mysqli->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        die("❌ Error: Cannot get table list: " . $mysqli->error . PHP_EOL);
    }

    $tables = array();
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row['TABLE_NAME'];
    }

    if (empty($tables)) {
        die("❌ Error: No tables found in database" . PHP_EOL);
    }

    print("🔍 Found " . count($tables) . " tables to check" . PHP_EOL . PHP_EOL);

    $allEngineQueries = array();
    $allEncodingQueries = array();
    foreach ($tables as $table) {
        $queries = checkTableEncoding($mysqli, $table, $targetEncoding, $targetEngine);
        $allEngineQueries = array_merge($allEngineQueries, $queries['engine']);
        $allEncodingQueries = array_merge($allEncodingQueries, $queries['encoding']);
    }

    $mysqli->close();

    if (!empty($allEngineQueries) or !empty($allEncodingQueries)) {
        print(PHP_EOL . "🔧 All conversion queries:" . PHP_EOL);
        print("=========================" . PHP_EOL);
        foreach ($allEngineQueries as $query) {
            print($query . PHP_EOL);
        }
        foreach ($allEncodingQueries as $query) {
            print($query . PHP_EOL);
        }
        print("=========================" . PHP_EOL);
    }

    $executionTime = microtime(true) - $startTime;
    print(PHP_EOL . "⏱️ Completed in " . round($executionTime, 2) . " seconds" . PHP_EOL);
}

main(); 