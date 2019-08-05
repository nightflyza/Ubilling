<?php

if ($_GET['action'] == 'writevlanmachistory') {
    if ($alterconf['VLANMACHISTORY']) {
        $history = new VlanMacHistory;
        $history->WriteVlanMacData();
        die('OK:WRITING NEW MACS');
    } else {
        die('ERROR:NO_VLAN_MAC_HISTORY ENABLED');
    }
}
