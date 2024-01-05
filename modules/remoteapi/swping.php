<?php

/**
 * Switch ICMP reping to fill dead cache
 */
if (ubRouting::get('action') == 'swping') {
    $switchRepingProcess = new StarDust('SWPING');
    if ($switchRepingProcess->notRunning()) {
        $currenttime = time();
        $deadSwitches = zb_SwitchesRepingAll();
        zb_StorageSet('SWPINGTIME', $currenttime);
        //store dead switches log data
        if (!empty($deadSwitches)) {
            zb_SwitchesDeadLog($currenttime, $deadSwitches);
        }
        die('OK:SWPING');
    } else {
        die('SKIP:SWPING_ALREADY_RUNNING');
    }
}
