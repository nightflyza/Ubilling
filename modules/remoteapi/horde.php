<?php

if (ubRouting::get('action') == 'horde') {
    $swpollLogPath = 'exports/swpolldata.log';
    $swpollLogData = '';
    if (ubRouting::checkGet('devid')) {
        $devId = ubRouting::get('devid', 'int');
        if (!empty($devId)) {
            $allTemplates = sp_SnmpGetAllModelTemplates();
            $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
            $allDeadSwitches = zb_SwitchesGetAllDead();

            $devData = zb_SwitchGetData($devId);
            if (!empty($devData)) {
                if (ispos($devData['desc'], 'SWPOLL')) {
                    if (isset($allTemplatesAssoc[$devData['modelid']])) {
                        //dont poll dead devices
                        if (!isset($allDeadSwitches[$devData['ip']])) {
                            $deviceTemplate = $allTemplatesAssoc[$devData['modelid']];
                            sp_SnmpPollDevice($devData['ip'], $devData['snmp'], $allTemplates, $deviceTemplate, $devData['snmpwrite']);
                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $devData['ip'] . ' [OK]' . PHP_EOL;
                        } else {
                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $devData['ip'] . ' [SKIP] SWITCH DEAD' . PHP_EOL;
                        }
                    } else {
                        $swpollLogData = date("Y-m-d H:i:s") . ' ' . $devData['ip'] . ' [FAIL] NO TEMPLATE' . PHP_EOL;
                    }
                } else {
                    $swpollLogData = date("Y-m-d H:i:s") . ' ' . $devData['ip'] . ' [SKIP] NO SWPOLL TAG' . PHP_EOL;
                }
            } else {
                $swpollLogData = date("Y-m-d H:i:s") . ' devid=' . $devId . ' [FAIL] DEVICE NOT FOUND' . PHP_EOL;
            }
            if (!empty($swpollLogData)) {
                @file_put_contents($swpollLogPath, $swpollLogData, FILE_APPEND);
            }
            die('OK:HORDE');
        } else {
            die('ERROR:EMPTY_DEVID');
        }
    } else {
        die('ERROR:NO_DEVID');
    }
}