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

$childLibsDirs=array(
    'docs/signup3/modules/engine',
    'docs/openpayz/libs',
    'userstats/modules/engine',
    'docs/uhw/libs',
    'docs/uhw_mlg/libs',
    'docs/opt82_uhw/libs',
);

$coreLibsNames=array(
    'api.ubrouting.php',
    'api.astral.php',
    'api.omaeurl.php',
    'api.nyanorm.php',
    'api.snmphelper.php',
    'api.ubcache.php',
);

$ignoreList=array(
    'OpenSans-Regular.ttf',
);

/**
 * Compares two library files and reports freshness status
 *
 * @param string $label
 * @param string $pathA
 * @param string $pathB
 * @param array $ignoreList
 * @param bool $diffDumpFlag
 * @param bool $requireBothExist
 *
 * @return int
 */
function checkLibFreshness($label, $pathA, $pathB, $ignoreList, $diffDumpFlag, $requireBothExist = true) {
    $result = 0;
    if (file_exists($pathA) and file_exists($pathB)) {
        $diffResult = shell_exec('diff --ignore-all-space "' . $pathA . '" "' . $pathB . '"');
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
                print('OK: ' . $label . ' (ignored differences only)' . PHP_EOL);
            } else {
                $result = 1;
                print('FAILED: ' . $label . PHP_EOL);
                if ($diffDumpFlag) {
                    print('=========================' . PHP_EOL);
                    print_r($diffResult);
                    print('=========================' . PHP_EOL);
                }
            }
        } else {
            print('OK: ' . $label . PHP_EOL);
        }
    } else {
        if ($requireBothExist) {
            print('ERROR: ' . $label . ' lib at specified path not found' . PHP_EOL);
        }
    }
    return ($result);
}

if (ubRouting::optionCliCheck('run', false)) {

    $diffDumpFlag = ubRouting::optionCliCheck('dumpdiff', false);
    $errorCount=0;

    $ubillingLibsPath = __DIR__ . '/../../ubilling/api/libs/';
    $ubillingRoot = __DIR__ . '/../../ubilling';

    print('Upstream libs check' . PHP_EOL);
    print('=========================' . PHP_EOL);
    foreach ($upstreamLibsPaths as $upstreamLibPath) {
        $fullUpstreamPath = $upstreamLibsBasePath . $upstreamLibPath;
        $libFileName = basename($upstreamLibPath);
        $ubillingLibPath = $ubillingLibsPath . $libFileName;
        $errorCount += checkLibFreshness($libFileName, $fullUpstreamPath, $ubillingLibPath, $ignoreList, $diffDumpFlag, true);
    }

    print('=========================' . PHP_EOL);
    print('Childs libs check' . PHP_EOL);
    print('=========================' . PHP_EOL);
    foreach ($childLibsDirs as $childLibsDir) {
        foreach ($coreLibsNames as $coreLibName) {
            $childLibPath = $ubillingRoot . '/' . $childLibsDir . '/' . $coreLibName;
            $ubillingLibPath = $ubillingLibsPath . $coreLibName;
            if (file_exists($childLibPath)) {
                if (file_exists($ubillingLibPath)) {
                    $label = $coreLibName . ' @ ' . $childLibsDir;
                    $errorCount += checkLibFreshness($label, $childLibPath, $ubillingLibPath, $ignoreList, $diffDumpFlag, true);
                } else {
                    print('ERROR: ' . $coreLibName . ' lib at specified path not found' . PHP_EOL);
                }
            }
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
