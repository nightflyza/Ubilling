<?php

if (cfr('UNIVERSALQINQCONFIG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['VLAN_MANAGEMENT_ENABLED']) {
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
                        break;
                    case 'ajax':
                        die($vlan->ajaxChooseForm());
                        break;
                    case 'choosetype':
                        die($vlan->types());
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
    }
}