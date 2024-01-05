<?php

//TrinityTV userstats control options
if ($_GET['action'] == 'trinitytvcontrol') {
    if ($alterconf['TRINITYTV_ENABLED']) {
        $trinityClass = new TrinityTv();
        if (wf_CheckGet(array('param', 'userlogin'))) {
            // Subscribe
            if (wf_CheckGet(array('tariffid')) and $_GET['param'] == 'subscribe') {
                $subResult = $trinityClass->createSubscribtion($_GET['userlogin'], $_GET['tariffid']);
                die($subResult);
            }
            // Unsubscribe
            if ($_GET['param'] == 'unsubscribe') {
                $subResult = $trinityClass->deleteSubscribtion($_GET['userlogin']);
                die($subResult);
            }
            // Add device MAC
            if (wf_CheckGet(array('mac')) and $_GET['param'] == 'adddevice') {
                $subResult = $trinityClass->addDevice($_GET['userlogin'], $_GET['mac']);
                die($subResult);
            }
            // Add device by Code
            if (wf_CheckGet(array('code')) and $_GET['param'] == 'adddevice') {
                $subResult = $trinityClass->addDeviceByCode($_GET['userlogin'], $_GET['code']);
                die($subResult);
            }
            // Delete device by MAC
            if (wf_CheckGet(array('mac')) and $_GET['param'] == 'deldevice') {
                $subResult = $trinityClass->deleteDevice($_GET['userlogin'], $_GET['mac']);
                die($subResult);
            }
            // Delete device by ID
            if (ubRouting::checkGet(array('devid', 'userlogin')) AND ubRouting::get('param') == 'deldeviceid') {
                $subResult = $trinityClass->deleteDeviceByIdProtected(ubRouting::get('devid'), ubRouting::get('userlogin'));
                die($subResult);
            }
        }
        if (wf_CheckGet(array('param'))) {
            if ($_GET['param'] == 'chargefee') {
                $trinityClass->subscriptionFeeProcessing();
                die('TRINITYTV_CHARGE_DONE');
            }
            if ($_GET['param'] == 'resurrect') {
                $trinityClass->resurrectAllSubscribers();
                die('TRINITYTV_RESURRECT_DONE');
            }
        }
    } else {
        die('ERROR: TRINITYTV DISABLED');
    }
}
