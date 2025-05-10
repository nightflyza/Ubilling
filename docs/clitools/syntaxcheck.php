<?php

require_once(__DIR__ . '/../../api/libs/api.compat.php');
require_once(__DIR__ . '/../../api/libs/api.workaround.php');
require_once(__DIR__ . '/../../modules/system/filesystem.php');
require_once(__DIR__ . '/../../api/libs/api.ubrouting.php');


if (ubRouting::optionCliCheck('run', false)) {
    $errorCount = 0;
    $ubLibsPath = __DIR__ . '/../../api/libs/';
    $failedLibs = array();

    $allUbLibs = rcms_scandir($ubLibsPath, '*.php');
    if (!empty($allUbLibs)) {
        $allUbLibs = array_flip($allUbLibs);
        foreach ($allUbLibs as $eachUbLib => $index) {
            $lintResult = shell_exec('php -l ' . $ubLibsPath . $eachUbLib.' 2>&1');
            if (ispos($lintResult, 'PHP ')) {
                $errorCount++;
                $failedLibs[] = $eachUbLib;
                print('⚠️ FAILED: ' . $eachUbLib . PHP_EOL);
                print('🔍 Details:' . PHP_EOL);
                print('=========================' . PHP_EOL);
                print($lintResult.PHP_EOL);
                print('=========================' . PHP_EOL);
            } else {
                print('✅ OK: ' . $eachUbLib . PHP_EOL);
            }
        }
    }

    //summary here
    print('📊 Summary:' . PHP_EOL);
    print('=========================' . PHP_EOL);
    if ($errorCount > 0) {
        print('❌ Found ' . $errorCount . ' issues with libs syntax' . PHP_EOL);
        print('📋 Failed libraries:' . PHP_EOL);
        foreach ($failedLibs as $lib) {
            print('  ⚠️ ' . $lib . PHP_EOL);
        }
    } else {
        print('✨ Everything is Ok' . PHP_EOL);
    }
} else {
    print('ℹ️ Usage: php ./docs/clitools/syntaxcheck.php --run' . PHP_EOL);
}
