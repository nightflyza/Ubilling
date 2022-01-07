<?php

if (cfr('ROOT')) {
    $dhcpZen = new DHCPZen();
    $zenFlow = new ZenFlow($dhcpZen->getFlowId(), $dhcpZen->render(), $dhcpZen->getTimeout());
    show_window(__('DHCP') . ' ' . __('Zen'), $zenFlow->render());
    show_window('', wf_BackLink('?module=dhcp'));
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}