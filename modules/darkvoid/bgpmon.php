<?php

$result = '';

if (isset($darkVoidContext['altCfg']['BGPMON_ENABLED'])) {
    if ($darkVoidContext['altCfg']['BGPMON_ENABLED']) {
        $bgpMon = new BGPMon();
        $result .= $bgpMon->getPeersAlerts();
    }
}

return ($result);
