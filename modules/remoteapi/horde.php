<?php

if (ubRouting::get('action') == 'horde') {
    if (ubRouting::checkGet('devid')) {
        $devId = ubRouting::get('devid', 'int');
        if (!empty($devId)) {
            $allTemplates = sp_SnmpGetAllModelTemplates();
            $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
            $allDeadSwitches = zb_SwitchesGetAllDead();
            $allUserMacs = array();
            $allAddress = array();

            $devData = zb_SwitchGetData($devId);
            if (!empty($devData)) {
                if (ispos($devData['desc'], 'SWPOLL')) {
                    if (isset($allTemplatesAssoc[$devData['modelid']])) {
                        //dont poll dead devices
                        if (!isset($alldeadswitches[$devData['ip']])) {
                            $deviceTemplate = $allTemplatesAssoc[$devData['modelid']];
                            sp_SnmpPollDevice($devData['ip'], $devData['snmp'], $allTemplates, $deviceTemplate, $allUserMacs, $allAddress, $devData['snmpwrite'], true);
                        }
                    }
                }
            }
            die('OK:HORDE');
        } else {
            die('ERROR:EMPTY_DEVID');
        }
    } else {
        die('ERROR:NO_DEVID');
    }
}