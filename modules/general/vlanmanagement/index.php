<?php

if (cfr('UNIVERSALQINQCONFIG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['VLAN_MANAGEMENT_ENABLED']) {
        $vlan = new VlanManagement();
    }
}