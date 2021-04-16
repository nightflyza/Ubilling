<?php

/**
 * SNMP switch polling
 */
if (ubRouting::get('action') == 'swpoll') {
    $allDevices = sp_SnmpGetAllDevices();
    $allTemplates = sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
    $allusermacs = zb_UserGetAllMACs();
    $alladdress = zb_AddressGetFullCityaddresslist();
    $alldeadswitches = zb_SwitchesGetAllDead();
    $swpollLogData = '';
    $swpollLogPath = 'exports/swpolldata.log';
    $altCfg = $ubillingConfig->getAlter();
    $hordeTimeout = 0;
    if (@$altCfg['HORDE_OF_SWITCHES'] > 1) {
        $hordeTimeout = ubRouting::filters($altCfg['HORDE_OF_SWITCHES'], 'int');
    }

    if (!empty($allDevices)) {
        //start new polling
        file_put_contents($swpollLogPath, date("Y-m-d H:i:s") . ' [SWPOLLSTART]' . "\n", FILE_APPEND);
        foreach ($allDevices as $io => $eachDevice) {
            if (!@$altCfg['HORDE_OF_SWITCHES']) {
                $swpollLogData = '';
                if (!empty($allTemplatesAssoc)) {
                    if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                        //dont poll dead devices
                        if (!isset($alldeadswitches[$eachDevice['ip']])) {
                            $deviceTemplate = $allTemplatesAssoc[$eachDevice['modelid']];
                            sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate, $allusermacs, $alladdress, $eachDevice['snmpwrite'], true);
                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [OK]' . "\n";
                            print($swpollLogData);
                        } else {
                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] SWITCH DEAD' . "\n";
                            print($swpollLogData);
                        }
                    } else {
                        $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] NO TEMPLATE' . "\n";
                        print($swpollLogData);
                    }
                }

                //put some log data about polling
                file_put_contents($swpollLogPath, $swpollLogData, FILE_APPEND);
            } else {
                //For the horde!
                $pipes = array();
                proc_close(proc_open('/bin/ubapi "horde&devid=' . $eachDevice['id'] . '"> /dev/null 2>/dev/null &', array(), $pipes));
                if ($hordeTimeout) {
                    sleep($hordeTimeout);
                }
            }
        }
        die('OK:SWPOLL');
    } else {
        die('ERROR:SWPOLL_NODEVICES');
    }
}