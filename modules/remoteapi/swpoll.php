<?php

/*
 * SNMP switch polling
 */
if ($_GET['action'] == 'swpoll') {
    $allDevices = sp_SnmpGetAllDevices();
    $allTemplates = sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
    $allusermacs = zb_UserGetAllMACs();
    $alladdress = zb_AddressGetFullCityaddresslist();
    $alldeadswitches = zb_SwitchesGetAllDead();
    $swpollLogData = '';
    $swpollLogPath = 'exports/swpolldata.log';
    if (!empty($allDevices)) {
        //start new polling
        file_put_contents($swpollLogPath, date("Y-m-d H:i:s") . ' [SWPOLLSTART]' . "\n", FILE_APPEND);
        foreach ($allDevices as $io => $eachDevice) {
            $swpollLogData = '';
            if (!empty($allTemplatesAssoc)) {
                if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                    //dont poll dead devices
                    if (!isset($alldeadswitches[$eachDevice['ip']])) {
                        //dont poll NP devices - commented due testing
                        // if (!ispos($eachDevice['desc'], 'NP')) {
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
        }
        die('OK:SWPOLL');
    } else {
        die('ERROR:SWPOLL_NODEVICES');
    }
}