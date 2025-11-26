<?php

require_once (__DIR__ . '/libs/api.compat.php');
require_once (__DIR__ . '/libs/api.workaround.php');
require_once (__DIR__ . '/../modules/system/filesystem.php');
require_once (__DIR__ . '/libs/api.ubrouting.php');


$upstreamLibsBasePath= __DIR__ . '/../../';

$upstreamLibsPaths=array(
    'NyanORM/api/libs/api.nyanorm.php',
    'WolfDispatcher/api.wolfdispatcher.php',
    'PixelCraft/src/api.pixelcraft.php',
    'ChartMancer/src/api.chartmancer.php',
);

$ignoreList=array(
    'OpenSans-Regular.ttf',
);

if (ubRouting::optionCliCheck('run', false)) {

    $diffDumpFlag = ubRouting::optionCliCheck('dumpdiff', false);
    $errorCount=0;

    $ubillingLibsPath = __DIR__ . '/../../ubilling/api/libs/';

    foreach ($upstreamLibsPaths as $upstreamLibPath) {
        $fullUpstreamPath = $upstreamLibsBasePath . $upstreamLibPath;
        $libFileName = basename($upstreamLibPath);
        $ubillingLibPath = $ubillingLibsPath . $libFileName;

        if (file_exists($fullUpstreamPath) and file_exists($ubillingLibPath)) {
            $diffResult = shell_exec('diff --ignore-all-space "' . $fullUpstreamPath . '" "' . $ubillingLibPath . '"');
            if (!empty($diffResult)) {
                $diffLines = explode(PHP_EOL, $diffResult);
                $significantLines = array();
                
                foreach ($diffLines as $line) {
                    $line = trim($line);
                    if (empty($line) or preg_match('/^[0-9,]+[acd][0-9,]*$/', $line) or preg_match('/^---$/', $line)) {
                        continue;
                    }
                    
                    $isIgnored = false;
                    foreach ($ignoreList as $ignorePattern) {
                        if (strpos($line, $ignorePattern) !== false) {
                            $isIgnored = true;
                            break;
                        }
                    }
                    
                    if (!$isIgnored) {
                        $significantLines[] = $line;
                    }
                }
                
                if (empty($significantLines)) {
                    print('OK: ' . $libFileName . ' (ignored differences only)' . PHP_EOL);
                } else {
                    $errorCount++;
                    print('FAILED: ' . $libFileName . PHP_EOL);
                    if ($diffDumpFlag) {
                        print('=========================' . PHP_EOL);
                        print_r($diffResult);
                        print('=========================' . PHP_EOL);
                    }
                }
            } else {
                print('OK: ' . $libFileName . PHP_EOL);
            }
        } else {
            print('ERROR: ' . $libFileName . ' lib at specified path not found'. PHP_EOL);
        }
    }

    print('=========================' . PHP_EOL);
    if ($errorCount>0) {
        print('Found '.$errorCount.' issues with libs freshness'.PHP_EOL);
    } else {
        print('Everything is Ok'.PHP_EOL);
    }
} else {
    print('Usage: php ./api/checkfreshness.php --run [--dumpdiff]' . PHP_EOL);
}
