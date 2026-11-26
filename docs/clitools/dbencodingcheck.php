<?php
set_time_limit(0);

function showHelp($targetEngine, $targetEncoding) {
    $help = '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '║                    🦄 Ubilling DB encoding check tool 🦄                     ║' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    $help .= 'Checks database tables for encoding and engine compliance.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php dbencodingcheck.php [options]' . PHP_EOL . PHP_EOL;
    $help .= 'Options:' . PHP_EOL;
    $help .= '    --engine=ENGINE        Target storage engine (default: ' . $targetEngine . ')' . PHP_EOL;
    $help .= '    --encoding=COLLATION   Target table collation (default: ' . $targetEncoding . ')' . PHP_EOL . PHP_EOL;
    $help .= 'Examples:' . PHP_EOL;
    $help .= '    php dbencodingcheck.php' . PHP_EOL;
    $help .= '    php dbencodingcheck.php --engine=InnoDB' . PHP_EOL;
    $help .= '    php dbencodingcheck.php --encoding=utf8mb4_general_ci' . PHP_EOL;
    $help .= '    php dbencodingcheck.php --engine=InnoDB --encoding=utf8mb4_general_ci' . PHP_EOL;
    $help .= '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    print($help);
}

function parseCliOptions($argv) {
    $result = array(
        'engine' => 'MyISAM',
        'encoding' => 'utf8mb3_general_ci'
    );

    foreach ($argv as $arg) {
        if (strpos($arg, '--engine=') === 0) {
            $value = substr($arg, 9);
            if ($value !== '') {
                $result['engine'] = $value;
            }
        } else {
            if (strpos($arg, '--encoding=') === 0) {
                $value = substr($arg, 11);
                if ($value !== '') {
                    $result['encoding'] = $value;
                }
            }
        }
    }

    return ($result);
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

function getCharsetFromCollation($collation) {
    $result = '';
    if (!empty($collation)) {
        $parts = explode('_', strtolower($collation));
        $result = $parts[0];
        // utf8 and utf8mb3 are aliases in MySQL
        if ($result === 'utf8') {
            $result = 'utf8mb3';
        }
    }
    return ($result);
}

function getConvertCharset($targetEncoding) {
    $charset = getCharsetFromCollation($targetEncoding);
    $result = 'utf8';
    if ($charset === 'utf8mb4') {
        $result = 'utf8mb4';
    }
    return ($result);
}

function needsEncodingFix($currentCollation, $targetEncoding) {
    $currentCharset = getCharsetFromCollation($currentCollation);
    $targetCharset = getCharsetFromCollation($targetEncoding);
    $result = true;
    if ($currentCharset !== '' and $currentCharset === $targetCharset) {
        $result = false;
    }
    return ($result);
}

function checkTableEncoding($mysqli, $table, $targetEncoding, $targetEngine) {
    $ignoredTables = array(
        'cardbank',
        'nethosts'
    );

    $engineQueries = array();
    $encodingQueries = array();

    if (in_array($table, $ignoredTables)) {
        $result = array('engine' => $engineQueries, 'encoding' => $encodingQueries);
    } else {
        $issues = array();

        $dbNameRow = $mysqli->query("SELECT DATABASE()")->fetch_row();
        $dbName = $dbNameRow[0];
        $stmt = $mysqli->prepare("SELECT TABLE_COLLATION, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $stmt->bind_param("ss", $dbName, $table);
        $stmt->execute();
        $tableResult = $stmt->get_result();

        if ($tableResult->num_rows == 0) {
            print("❌ Error: Table '$table' not found in information_schema" . PHP_EOL);
            $result = array('engine' => $engineQueries, 'encoding' => $encodingQueries);
        } else {
            $row = $tableResult->fetch_assoc();
            if ($row['TABLE_TYPE'] !== 'BASE TABLE') {
                $result = array('engine' => $engineQueries, 'encoding' => $encodingQueries);
            } else {
                $currentCollation = $row['TABLE_COLLATION'];
                $currentEngine = $row['ENGINE'];

                if ($currentEngine === null) {
                    $currentEngine = '';
                }

                $encodingNeedsFix = needsEncodingFix($currentCollation, $targetEncoding);
                $engineNeedsFix = (strtoupper(trim($currentEngine)) !== strtoupper(trim($targetEngine)));

                if ($encodingNeedsFix or $engineNeedsFix) {
                    if ($engineNeedsFix) {
                        $issues[] = "engine: $currentEngine (target: $targetEngine)";
                        $engineQueries[] = "ALTER TABLE `$table` ENGINE=$targetEngine;";
                    }

                    if ($encodingNeedsFix) {
                        $issues[] = "encoding: $currentCollation (target: $targetEncoding)";
                        $targetCharset = getConvertCharset($targetEncoding);
                        $encodingQueries[] = "ALTER TABLE `$table` CONVERT TO CHARACTER SET $targetCharset COLLATE $targetEncoding;";
                    }

                    print("⚠️ Table '$table' has issues: " . implode(', ', $issues) . PHP_EOL);
                } else {
                    print("✅ Table '$table' has correct encoding ($currentCollation) and engine ($currentEngine)" . PHP_EOL);
                }

                $result = array('engine' => $engineQueries, 'encoding' => $encodingQueries);
            }
        }
    }

    return ($result);
}

function main() {
    global $argv;

    $options = parseCliOptions($argv);
    $targetEngine = $options['engine'];
    $targetEncoding = $options['encoding'];

    showHelp($targetEngine, $targetEncoding);
    print("🎯 Target engine: $targetEngine" . PHP_EOL);
    print("🎯 Target encoding: $targetEncoding" . PHP_EOL . PHP_EOL);

    $startTime = microtime(true);
    $config = loadMysqlConfig();
    $mysqli = connectToDatabase($config);

    $dbNameRow = $mysqli->query("SELECT DATABASE()")->fetch_row();
    $dbName = $dbNameRow[0];
    $stmt = $mysqli->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $tablesResult = $stmt->get_result();

    if (!$tablesResult) {
        die("❌ Error: Cannot get table list: " . $mysqli->error . PHP_EOL);
    }

    $tables = array();
    while ($row = $tablesResult->fetch_assoc()) {
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
