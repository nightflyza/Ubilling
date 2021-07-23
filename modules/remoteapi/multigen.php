<?php

// multigen attributes regeneration
$remoteApiAction = ubRouting::get('action');
if (($remoteApiAction == 'multigen') OR ( $remoteApiAction == 'multigentotal') OR ( $remoteApiAction == 'multigentraff') OR ( $remoteApiAction == 'multigenpod')) {
    if ($alterconf['MULTIGEN_ENABLED']) {
        $multigen = new MultiGen();
        if ($remoteApiAction == 'multigen') {
            $multigen->generateNasAttributes();
            die('OK: MULTIGEN');
        }

        if ($remoteApiAction == 'multigentotal') {
            $multigen->flushAllScenarios();
            $multigen->generateNasAttributes();
            die('OK: MULTIGEN_TOTAL');
        }

        if ($remoteApiAction == 'multigentraff') {
            $multigen->aggregateTraffic();
            die('OK: MULTIGEN_TRAFF');
        }

        if ($remoteApiAction == 'multigenpod') {
            $login = ubRouting::get('param');
            if (!empty($login)) {
                $userData = zb_UserGetAllData($login);
                $userData = $userData[$login];
                $newUserData = $userData;
                if ($alterconf['MULTIGEN_POD_ON_MAC_CHANGE']) {
                    if ($alterconf['MULTIGEN_POD_ON_MAC_CHANGE'] == 2) {
                        $multigen->podOnExternalEvent($login, $userData, $newUserData);
                        $multigen->podOnExternalEvent($login, $newUserData);
                    }
                    if ($alterconf['MULTIGEN_POD_ON_MAC_CHANGE'] == 1) {
                        $multigen->podOnExternalEvent($login, $newUserData);
                    }
                    die('OK: MULTIGENPOD');
                } else {
                    die('ERROR: MULTIGEN_POD_ON_MAC_CHANGE DISABLED');
                }
            } else {
                die('ERROR: NO_LOGIN');
            }
        }
    } else {
        die('ERROR: MULTIGEN DISABLED');
    }
}