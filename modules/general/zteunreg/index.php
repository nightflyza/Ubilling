<?php

$altcfg = $ubillingConfig->getAlter();

if (@$altcfg['ONUREG_ZTE']) {
    if (cfr('ONUREGZTE')) {
        $register = new OnuRegister();
        $avidity = $register->getAvidity();
        if (!empty($avidity)) {
            $avidity_z = $avidity['M']['LUCIO'];
            $avidity_w = $avidity['M']['REAPER'];
            show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());

            show_window('', wf_BackLink(PONizer::URL_ME));

            if (wf_CheckGet(array('oltip', 'interface', 'type'))) {
                if (isset($_GET['maconu'])) {
                    show_window(__('Register'), $register->RegisterOnuForm($_GET['type'], $_GET['interface'], $_GET['oltip'], $_GET['maconu']));
                }
                if (isset($_GET['serial'])) {
                    show_window(__('Register'), $register->RegisterOnuForm($_GET['type'], $_GET['interface'], $_GET['oltip'], $_GET['serial']));
                }
            }
            if (wf_CheckPost(array('type', 'interface', 'oltip', 'modelid', 'vlan'))) {
                if ($_POST['modelid'] != '======') {
                    $mac_onu = '';
                    $save = false;
                    $router = false;
                    $login = '';
                    $PONizerAdd = false;
                    if (!empty($_POST['login'])) {
                        $login = $_POST['login'];
                    }
                    if (isset($_POST['router'])) {
                        $router = $_POST['router'];
                    }
                    if (isset($_POST['mac'])) {
                        $onuIdentifier = $_POST['mac'];
                    }
                    if (isset($_POST['sn'])) {
                        $onuIdentifier = $_POST['sn'];
                    }
                    if (isset($_POST['mac_onu'])) {
                        $mac_onu = $_POST['mac_onu'];
                    }
                    if (isset($_POST['random_mac'])) {
                        $mac_onu = $register->generateRandomOnuMac();
                    }
                    if (isset($_POST['save'])) {
                        $save = $_POST['save'];
                    }
                    if (isset($_POST['ponizer_add'])) {
                        $PONizerAdd = true;
                    }
                    show_window(__('Result'), $register->$avidity_w($_POST['oltip'], $_POST['type'], $_POST['interface'], $onuIdentifier, $_POST['modelid'], $_POST['vlan'], $login, $save, $router, $mac_onu, $PONizerAdd));
                }
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
