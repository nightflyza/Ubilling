<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['VLAN_MANAGEMENT_ENABLED']) {
    if (cfr('VLANMANAGEMENT')) {
        $vlan = new VlanManagement();
        $realms = new Realms();

        if ($realms->routing->checkGet('realms')) {
            switch ($realms->routing->get('action')) {
                case 'add':
                    $realms->add();
                    break;
                case 'edit':
                    $realms->edit();
                    break;
                case 'delete':
                    $realms->delete();
                    break;
                case 'ajax':
                    $realms->ajaxData();
                    break;
                case 'ajaxedit':
                    die($realms->ajaxEdit($realms->routing->post('realm_encode')));
                    break;
            }

            $realms->links();
            $realms->showAll();
        } elseif ($vlan->routing->checkGet('svlan')) {
            switch ($vlan->routing->get('action')) {
                case 'add':
                    $vlan->addSvlan();
                    break;
                case 'edit':
                    $vlan->editSvlan();
                    break;
                case 'delete':
                    $vlan->deleteSvlan();
                    break;
                case 'ajax':
                    $vlan->ajaxSvlanData();
                    break;
                case 'ajaxedit':
                    die($vlan->ajaxEditSvlan($vlan->routing->post('svlan_encode')));
                    break;
            }

            $vlan->linksSvlan();
            $vlan->showSvlanAll();
        } else {
            if ($vlan->routing->get('action')) {
                switch ($vlan->routing->get('action')) {
                    case 'realm_id_select':
                        die($vlan->svlanSelector($vlan->routing->get('ajrealmid')));
                    case 'ajax':
                        die($vlan->ajaxChooseForm());
                    case 'ajaxcustomer':
                        die($vlan->ajaxCustomer());
                    case 'ajaxswitch':
                        die($vlan->ajaxSwitch());
                    case 'ajaxolt':
                        die($vlan->ajaxOlt());
                    case 'chooseoltcard':
                        die($vlan->cardSelector());
                    case 'choosecardport':
                        die($vlan->portCardSelector());
                    case 'choosetype':
                        die($vlan->types());
                    case 'add':
                        $vlan->addNewBinding();
                        break;
                    case 'deleteswitchbinding':
                        $vlan->deleteSwitchBinding();
                        break;
                    case 'deleteoltbinding':
                        $vlan->deleteOltBinding();
                        break;
                }
            } else {
                if (!$vlan->routing->get('realm_id', 'int') and ! $vlan->routing->get('svlan_id')) {
                    rcms_redirect($vlan::MODULE . '&realm_id=' . $vlan->defaultRealm . '&svlan_id=' . $vlan->defaultSvlan);
                }
            }


            $vlan->linksMain();
            $vlan->realmAndSvlanSelectors();
            $vlan->cvlanMatrix();
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}