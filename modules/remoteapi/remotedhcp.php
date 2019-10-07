<?php

//remote DHCP configs fetching
if (ubRouting::get('action') == 'remotedhcp') {
    if (@$alterconf['REMOTEDHCP_ENABLED']) {
        $remoteDhcp=new UbillingDHCP();
        die($remoteDhcp->getConfigsRemote());
    } else {
        die('ERROR:REMOTEDHCP_DISABLED');
    }
}