<?php

//BGP peers periodic polling
if (ubRouting::get('action') == 'getbgpstats') {
    if ($alterconf['BGPMON_ENABLED']) {
        $bgpMon = new BGPMon();
        $allPeerStats=$bgpMon->getAllPeersStats();
        
        header('Content-Type: application/json');
        die(json_encode($allPeerStats));
    } else {
        die('ERROR: BGPMON DISABLED');
    }
}
