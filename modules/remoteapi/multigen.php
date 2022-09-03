<?php

// multigen attributes regeneration
$remoteApiAction = ubRouting::get('action');
if (($remoteApiAction == 'multigen') OR ( $remoteApiAction == 'multigentotal') OR ( $remoteApiAction == 'multigentraff') OR ( $remoteApiAction == 'multigenpod')) {
    if ($alterconf['MULTIGEN_ENABLED']) {
        $multigen = new MultiGen();
        if ($remoteApiAction == 'multigen') {
            //check for simultaneous process
            if (!$multigen->isMultigenRunning()) {
                //preventing further simultaneous runs
                $multigen->runPidStart();
                //automatic old data cleanup?
                if ($alterconf['MULTIGEN_AUTOCLEANUP_ENABLED']) {
                    $cleanupTimes = $alterconf['MULTIGEN_AUTOCLEANUP_TIME'];
                    if (!empty($cleanupTimes)) {
                        $cleanupTimes = explode(',', $cleanupTimes);
                        if (!empty($cleanupTimes)) {
                            $cleanupTimes = array_flip($cleanupTimes);
                            $nowTime = date("H:i");
                            //Now its cleanup time!
                            if (isset($cleanupTimes[$nowTime])) {
                                //accounting and postdata cleanup
                                $multigen->cleanupAccounting($alterconf['MULTIGEN_AUTOCLEANUP_ACCTDAYS'], $alterconf['MULTIGEN_AUTOCLEANUP_UNF']);

                                //flushing all scenarios attributes
                                $multigen->flushAllScenarios();
                                print('OK: MULTIGEN_AUTOCLEANUP' . PHP_EOL);
                            }
                        }
                    }
                }
                //regenerating attributes
                $multigen->generateNasAttributes();
                //releasing lock
                $multigen->runPidEnd();
                die('OK: MULTIGEN');
            } else {
                die('SKIP: MULTIGEN ALREADY RUNNING');
            }
        }

        //this callback left here just for legacy
        if ($remoteApiAction == 'multigentotal') {
            if (!$multigen->isMultigenRunning()) {
                $multigen->runPidStart();
                $multigen->flushAllScenarios();
                $multigen->generateNasAttributes();
                $multigen->runPidEnd();
                die('OK: MULTIGEN_TOTAL');
            } else {
                die('SKIP: MULTIGEN ALREADY RUNNING');
            }
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