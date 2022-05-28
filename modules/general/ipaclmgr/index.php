<?php

if (cfr('ROOT')) {

    $aclMgr = new IpACLMgr();
    debarr($aclMgr);
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}