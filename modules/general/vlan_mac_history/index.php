<?php

global $ubillingConfig;
$altCfg = $ubillingConfig->getAlter();
if ($altCfg['VLANMACHISTORY']) {
    if (cfr('VLANMACHISTORY')) {
        $login = $_GET['username'];
        $history = new VlanMacHistory;
        $history->RenderHistory($login);
        show_window('', web_UserControls($login));
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module is disabled'));
}