<?php

debarr($_POST);

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['VLAN_MANAGEMENT_ENABLED']) {
    if (cfr('VLANMANAGEMENT')) {
        $vlan = new VlanManagement();
        $routing = new ubRouting();

        if ($routing->checkGet('ajaxOltList')) {
            $vlan->oltListAjaxRender();
        }
        if (!$routing->checkGet('oltid')) {
            show_window('', wf_BackLink(VlanManagement::MODULE));
            show_window(__('Available switches'), $vlan->oltListShow());
        } else {
            show_window('', wf_BackLink(VlanManagement::MODULE_ONU_APPLY));
            $change = new VlanChange($routing->get('oltid', 'mres'), $routing->get('username', 'mres'));
            if ($routing->checkGet('ajaxOnuList')) {
                $change->onuListAjaxRender();
            }
            show_window(__('Available') . " ONU", $change->onuListShow());
        }
    }
}