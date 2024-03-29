<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['VLAN_MANAGEMENT_ENABLED']) {
    if (cfr('VLANMANAGEMENT')) {
        $vlan = new VlanManagement();
        $routing = new ubRouting();
        show_window('', $vlan->vlanChangeModal());
        $oltId = $routing->get('oltid', 'mres');
        $change = new VlanChange($oltId);

        if ($routing->checkGet('ajax_username_validate')) {
            $universalqinq = new UniversalQINQ();
            $userCheck = $universalqinq->isUserExists();
            if ($userCheck) {
                die($change->changeVlanForm($routing->get('onuid', 'mres'), $routing->get('port', 'mres'), $routing->get('vlan', 'mres'), $routing->get('type', 'mres'), $routing->get('interface', 'mres'), $routing->get('interface_olt', 'mres'), $routing->get('snmp_index', 'vf')));
            } else {
                die("error: user doesn't exist");
            }
        }

        if ($routing->checkPost('change_cvlan')) {
            $change->changeVlan();
        }

        if ($routing->checkGet('ajaxOltList')) {
            $vlan->oltListAjaxRender();
        }
        if (!$routing->checkGet('oltid')) {
            show_window('', wf_BackLink(VlanManagement::MODULE));
            show_window(__('Available switches'), $vlan->oltListShow());
        } else {
            show_window('', wf_BackLink(VlanManagement::MODULE_ONU_APPLY));
            if ($routing->checkGet('ajaxOnuList')) {
                $change->onuListAjaxRender();
            }
            show_window(__('Available') . " ONU", $change->onuListShow());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}