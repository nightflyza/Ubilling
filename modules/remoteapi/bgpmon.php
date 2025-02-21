<?php

//BGP peers periodic polling
if (ubRouting::get('action') == 'bgpmon') {
    if ($alterconf['BGPMON_ENABLED']) {
        $bgpMon = new BGPMon();
        $bgpMon->pollAllDevsStats();
        die('OK: BGPMON');
    } else {
        die('ERROR: BGPMON DISABLED');
    }
}
