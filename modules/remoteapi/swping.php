<?php

/*
 * Switch ICMP reping to fill dead cache
 */
if ($_GET['action'] == 'swping') {
    $currenttime = time();
    $deadSwitches = zb_SwitchesRepingAll();
    zb_StorageSet('SWPINGTIME', $currenttime);
    //store dead switches log data
    if (!empty($deadSwitches)) {
        zb_SwitchesDeadLog($currenttime, $deadSwitches);
    }
    die('OK:SWPING');
}
