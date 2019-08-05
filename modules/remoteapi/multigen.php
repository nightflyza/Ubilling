<?php

// multigen attributes regeneration
if (($_GET['action'] == 'multigen') OR ( $_GET['action'] == 'multigentotal') OR ( $_GET['action'] == 'multigentraff')) {
    if ($alterconf['MULTIGEN_ENABLED']) {
        $multigen = new MultiGen();
        if ($_GET['action'] == 'multigen') {
            $multigen->generateNasAttributes();
            die('OK: MULTIGEN');
        }

        if ($_GET['action'] == 'multigentotal') {
            $multigen->flushAllScenarios();
            $multigen->generateNasAttributes();
            die('OK: MULTIGEN_TOTAL');
        }

        if ($_GET['action'] == 'multigentraff') {
            $multigen->aggregateTraffic();
            die('OK: MULTIGEN_TRAFF');
        }
    } else {
        die('ERROR: MULTIGEN DISABLED');
    }
}