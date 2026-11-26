<?php
set_time_limit(0);

function showHelp() {
    $help = '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '║                    🦄 Ubilling Database Check tool 🦄                        ║' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    $help .= 'Performs database integrity check using MySQL credentials.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php dbcheck.php [options]' . PHP_EOL . PHP_EOL;
    $help .= 'Options:' . PHP_EOL;
    $help .= '    --repair    Attempt to repair corrupted tables' . PHP_EOL;
    $help .= '    --optimize  Optimize tables after check' . PHP_EOL;
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

function checkTables($mysqli, $repair = false, $optimize = false) {
    $tables = array();
    $result = $mysqli->query("SHOW TABLES");
    if (!$result) {
        die("❌ Error: Cannot get table list: " . $mysqli->error . PHP_EOL);
    }

    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    if (empty($tables)) {
        die("❌ Error: No tables found in database" . PHP_EOL);
    }

    print("🔍 Found " . count($tables) . " tables to check" . PHP_EOL);

    $hasErrors = false;
    foreach ($tables as $table) {
        print("🔍 Checking '$table'... ");
        
        $checkResult = $mysqli->query("CHECK TABLE `$table`");
        if (!$checkResult) {
            print("❌ Check failed - " . $mysqli->error . PHP_EOL);
            $hasErrors = true;
            continue;
        }

        $row = $checkResult->fetch_assoc();
        if ($row['Msg_type'] === 'error' || $row['Msg_type'] === 'warning') {
            print("⚠️ " . $row['Msg_text']);
            $hasErrors = true;

            if ($repair) {
                print(" [Repairing... ");
                $repairResult = $mysqli->query("REPAIR TABLE `$table`");
                if (!$repairResult) {
                    print("❌ Failed]");
                } else {
                    $repairRow = $repairResult->fetch_assoc();
                    print($repairRow['Msg_type'] === 'status' ? "✅]" : "⚠️]");
                }
            }
            print(PHP_EOL);
        } else {
            print("✅ OK");
            
            if ($optimize) {
                print(" [Optimizing... ");
                $optimizeResult = $mysqli->query("OPTIMIZE TABLE `$table`");
                if (!$optimizeResult) {
                    print("❌]");
                } else {
                    $optimizeRow = $optimizeResult->fetch_assoc();
                    print($optimizeRow['Msg_type'] === 'status' ? "✅]" : "⚠️]");
                }
            }
            print(PHP_EOL);
        }
    }

    return $hasErrors;
}

function formatDuration($seconds) {
    if ($seconds < 60) {
        return round($seconds, 2) . "s";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = fmod($seconds, 60);
        return $minutes . "m " . round($remainingSeconds, 2) . "s";
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(fmod($seconds, 3600) / 60);
        $remainingSeconds = fmod($seconds, 60);
        return $hours . "h " . $minutes . "m " . round($remainingSeconds, 2) . "s";
    }
}

function main() {
    global $argv;
    showHelp();
    
    $repair = in_array('--repair', $argv);
    $optimize = in_array('--optimize', $argv);
    
    $startTime = microtime(true);
    $config = loadMysqlConfig();
    $mysqli = connectToDatabase($config);
    $hasErrors = checkTables($mysqli, $repair, $optimize);
    $mysqli->close();

    $executionTime = microtime(true) - $startTime;
    print("⏱️ Completed in " . formatDuration($executionTime) . PHP_EOL);
    
    if ($hasErrors) {
        print("⚠️ Check completed with issues" . PHP_EOL);
        exit(1);
    } else {
        print("✅ All tables OK" . PHP_EOL);
        exit(0);
    }
}

main();
