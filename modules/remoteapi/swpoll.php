<?php

/**
 * SNMP switch polling
 */
if (ubRouting::get('action') == 'swpoll') {
    $switchPollingProcess = new StarDust('SWPOLL_ALL');
    $allDevices = sp_SnmpGetAllDevices();
    $allTemplates = sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
    $alldeadswitches = zb_SwitchesGetAllDead();
    $swpollLogPath = 'exports/swpolldata.log';
    $altCfg = $ubillingConfig->getAlter();
    $hordeTimeout = 0;
    $hordeBatchSize = 2;
    if (@$altCfg['HORDE_OF_SWITCHES'] > 1) {
        $hordeTimeout = ubRouting::filters($altCfg['HORDE_OF_SWITCHES'], 'int');
    }
    if (isset($altCfg['HORDE_BATCH_SIZE'])) {
        $hordeBatchSize = ubRouting::filters($altCfg['HORDE_BATCH_SIZE'], 'int');
        if ($hordeBatchSize < 1) {
            $hordeBatchSize = 2;
        }
    }

    if (!empty($allDevices)) {
        if ($switchPollingProcess->notRunning()) {
            //start new polling
            $switchPollingProcess->start();
            @file_put_contents($swpollLogPath, '', LOCK_EX);
            $swpollLogEvent = date("Y-m-d H:i:s") . ' [SWPOLLSTART]' . PHP_EOL;
            @file_put_contents($swpollLogPath, $swpollLogEvent, FILE_APPEND | LOCK_EX);
            if (!@$altCfg['HORDE_OF_SWITCHES']) {
                foreach ($allDevices as $io => $eachDevice) {
                    $swpollLogEvent = '';
                    if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                            //dont poll dead devices
                            if (!isset($alldeadswitches[$eachDevice['ip']])) {
                                $deviceTemplate = $allTemplatesAssoc[$eachDevice['modelid']];
                                sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate, $eachDevice['snmpwrite']);
                                $swpollLogEvent = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [OK]' . PHP_EOL;
                                print($swpollLogEvent);
                            } else {
                                $swpollLogEvent = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [SKIP] SWITCH DEAD' . PHP_EOL;
                                print($swpollLogEvent);
                            }
                        } else {
                            $swpollLogEvent = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] NO TEMPLATE' . PHP_EOL;
                            print($swpollLogEvent);
                        }
                    }
                    if (!empty($swpollLogEvent)) {
                        @file_put_contents($swpollLogPath, $swpollLogEvent, FILE_APPEND | LOCK_EX);
                    }
                }
            } else {
                $hordeQueue = array();
                $hordeRunning = array();
                foreach ($allDevices as $io => $eachDevice) {
                    if (!isset($alldeadswitches[$eachDevice['ip']])) {
                        $hordeQueue[] = $eachDevice;
                    } else {
                        $swpollLogEvent = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [SKIP] SWITCH DEAD' . PHP_EOL;
                        @file_put_contents($swpollLogPath, $swpollLogEvent, FILE_APPEND | LOCK_EX);
                        print($swpollLogEvent);
                    }
                }

                while (!empty($hordeQueue) or !empty($hordeRunning)) {
                    $hordeLaunchedNow = false;

                    while (count($hordeRunning) < $hordeBatchSize and !empty($hordeQueue)) {
                        $nextDevice = array_shift($hordeQueue);
                        if (!empty($nextDevice['id']) and !empty($nextDevice['ip'])) {
                            $switchPollingProcess->runBackgroundProcess('/bin/ubapi "horde&devid=' . $nextDevice['id'] . '"', $hordeTimeout);
                            $hordeRunning[$nextDevice['ip']] = $nextDevice['id'];
                            $hordeLaunchedNow = true;
                        }
                    }

                    if ($hordeLaunchedNow) {
                        sleep(1);
                    }

                    $hordeHasFinished = false;
                    if (!empty($hordeRunning)) {
                        foreach ($hordeRunning as $runningIp => $runningId) {
                            $hordeProcess = new StarDust('SWPOLL_' . $runningIp);
                            if (!$hordeProcess->isRunning()) {
                                unset($hordeRunning[$runningIp]);
                                $hordeHasFinished = true;
                            }
                        }
                    }

                    if (!$hordeHasFinished and !empty($hordeRunning)) {
                        sleep(1);
                    }
                }
            }

            $swpollLogEvent = date("Y-m-d H:i:s") . ' [SWPOLLFINISHED]' . PHP_EOL;
            @file_put_contents($swpollLogPath, $swpollLogEvent, FILE_APPEND | LOCK_EX);
            $switchPollingProcess->stop();
            die('OK:SWPOLL');
        } else {
            die('SKIP:SWPOLL_ALREADY_RUNNING');
        }
    } else {
        die('ERROR:SWPOLL_NODEVICES');
    }
}