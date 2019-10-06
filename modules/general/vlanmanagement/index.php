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
            }

            $vlan->linksSvlan();
            $vlan->showSvlanAll();
        } else {
            switch ($vlan->routing->get('action')) {
                case 'realm_id_select':
                    die($vlan->svlanSelector($vlan->routing->get('ajrealmid')));
                    break;
                case 'add':
                    break;
            }
            $vlan->linksMain();
            $vlan->realmAndSvlanSelectors();
            $vlan->cvlanMatrix();
        }
    }
}