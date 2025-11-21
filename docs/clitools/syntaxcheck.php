<?php

require_once(__DIR__ . '/../../api/libs/api.compat.php');
require_once(__DIR__ . '/../../api/libs/api.workaround.php');
require_once(__DIR__ . '/../../modules/system/filesystem.php');
require_once(__DIR__ . '/../../api/libs/api.ubrouting.php');

$errorCount = 0;
$baseLibsPath= __DIR__ . '/../../';
$mainLibsPath = 'api/libs/';
$userstatsLibsPath ='userstats/modules/engine/';
$failedLibs = array();
$allLibsToCheck = array();


if (ubRouting::optionCliCheck('main', false) or ubRouting::optionCliCheck('us', false) or ubRouting::optionCliCheck('runall', false)) {

    if (ubRouting::optionCliCheck('main', false)) {
        $mainLibsToCheck = rcms_scandir($baseLibsPath . $mainLibsPath, '*.php');
        if (!empty($mainLibsToCheck)) {
            foreach ($mainLibsToCheck as $index=>$eachUbLib) {
                $allLibsToCheck[$mainLibsPath.$eachUbLib] = $baseLibsPath . $mainLibsPath . $eachUbLib;
            }
        }
    }

    if (ubRouting::optionCliCheck('us', false)) {
        $userstatsLibsToCheck = rcms_scandir($baseLibsPath . $userstatsLibsPath, '*.php');
        if (!empty($userstatsLibsToCheck)) {
            foreach ($userstatsLibsToCheck as $index=>$eachUbLib) {
                $allLibsToCheck[$userstatsLibsPath.$eachUbLib] = $baseLibsPath . $userstatsLibsPath . $eachUbLib;
            }
        }
    }

    if (ubRouting::optionCliCheck('runall', false)) {
        $mainLibsToCheck = rcms_scandir($baseLibsPath . $mainLibsPath, '*.php');
        if (!empty($mainLibsToCheck)) {
            foreach ($mainLibsToCheck as $index=>$eachUbLib) {
                $subMain[$mainLibsPath.$eachUbLib] = $baseLibsPath . $mainLibsPath . $eachUbLib;
            }
        }

        $userstatsLibsToCheck = rcms_scandir($baseLibsPath . $userstatsLibsPath, '*.php');
        if (!empty($userstatsLibsToCheck)) {
            foreach ($userstatsLibsToCheck as $index=>$eachUbLib) {
                $subUs[$userstatsLibsPath.$eachUbLib] = $baseLibsPath . $userstatsLibsPath . $eachUbLib;
            }
        }

        $allLibsToCheck = array_merge($subMain, $subUs);
    }

    
    if (!empty($allLibsToCheck)) {
        foreach ($allLibsToCheck as $index=>$eachUbLib) {
            $lintResult = shell_exec('php -l ' . $eachUbLib.' 2>&1');
            $libLabel=$index;
            if (ispos($lintResult, 'PHP ')) {
                $errorCount++;
                $failedLibs[] = $libLabel;
                print('⚠️ FAILED: ' . $libLabel . PHP_EOL);
                print('🔍 Details:' . PHP_EOL);
                print('=========================' . PHP_EOL);
                print($lintResult.PHP_EOL);
                print('=========================' . PHP_EOL);
            } else {
                print('✅ OK: ' . $libLabel . PHP_EOL);
            }
        }
    } else {
        print('❌ No libs found' . PHP_EOL);
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
    print('ℹ️ Usage: php ./docs/clitools/syntaxcheck.php --[main|us|runall]' . PHP_EOL);
}
