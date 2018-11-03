<?php

$altcfg = $ubillingConfig->getAlter();

if (@$altcfg['ONUREG_ZTE']) {
    if (cfr('ONUREGZTE')) {
        $register = new OnuRegister();
        $avidity = $register->getAvidity();
        $onuIdentifier = '';
        if (!empty($avidity)) {
            $avidity_z = $avidity['M']['LUCIO'];
            $avidity_w = $avidity['M']['REAPER'];
            show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());

            show_window('', wf_BackLink(PONizer::URL_ME));

            if (wf_CheckGet(array('oltip', 'interface', 'type'))) {
                if (wf_CheckGet(array('maconu'))) {
                    $onuIdentifier = $_GET['maconu'];
                }
                if (wf_CheckGet(array('serial'))) {
                    $onuIdentifier = $_GET['serial'];
                }
                if (!empty($onuIdentifier)) {
                    $register->currentOltIp = $_GET['oltip'];
                    $register->currentOltInterface = $_GET['interface'];
                    $register->currentPonType = $_GET['type'];
                    $register->onuIdentifier = $onuIdentifier;
                    show_window(__('Register'), $register->registerOnuForm());
                }
            }
            if (wf_CheckPost(array('type', 'interface', 'oltip', 'modelid', 'vlan'))) {
                if ($_POST['modelid'] != '======') {
                    $mac_onu = '';
                    $save = false;
                    $router = false;
                    $login = '';
                    $PONizerAdd = false;
                    if (wf_CheckGet(array('login'))) {
                        $login = $_POST['login'];
                    }
                    if (wf_CheckGet(array('mac'))) {
                        $onuIdentifier = $_POST['mac'];
                    }
                    if (wf_CheckGet(array('sn'))) {
                        $onuIdentifier = $_POST['sn'];
                    }
                    if (isset($_POST['router'])) {
                        $router = $_POST['router'];
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
                    $register->currentOltIp = $_POST['oltip'];
                    $register->currentOltInterface = $_POST['interface'];
                    $register->currentPonType = $_POST['type'];
                    $register->onuIdentifier = $onuIdentifier;
                    show_window(__('Result'), $register->$avidity_w($_POST['modelid'], $_POST['vlan'], $login, $save, $router, $mac_onu, $PONizerAdd));
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
