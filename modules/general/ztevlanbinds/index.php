<?php

$altcfg = $ubillingConfig->getAlter();

if (@$altcfg['ONUREG_ZTE']) {
    if (cfr('ZTEVLANBINDS')) {
        $register = new OnuRegister();
        $avidity = $register->getAvidity();

        if (!empty($avidity)) {
            if (isset($_POST[$avidity['P']['JAI']])) {
                $avidity_a = $avidity['M']['PAI'];
                die($register->$avidity_a($_POST[$avidity['P']['JAI']], $_GET[$avidity['P']['EAI']]));
            }

            $avidity_b = $avidity['M']['LAI'];
            $avidity_d = $avidity['M']['RAIN'];
            $avidity_e = $avidity['M']['DRAIN'];
            $avidity_f = $avidity['M']['BRAIN'];
            $avidity_g = $avidity['M']['SLAIN'];
            show_window(__('All ZTE OLTs'), $register->$avidity_b());

            show_window('', wf_BackLink(PONizer::URL_ME));

            if (isset($_GET[$avidity['P']['EAC']])) {
                show_window(__('Create new card'), $register->$avidity_d($_GET[$avidity['P']['EAC']]));
                show_window(__('List all cards'), $register->$avidity_e($_GET[$avidity['P']['EAC']]));
                show_window(__('Show all installed cards'), $register->$avidity_f($_GET[$avidity['P']['EAC']]));
                if (isset($_GET['show_snmp'])) {
                    show_window(__('List of installed cards'), $register->$avidity_g($_GET[$avidity['P']['EAC']]));
                }
                if (isset($_GET[$avidity['P']['KILL']])) {
                    $avarice_c = $avidity['M']['MOTHERFUCKER'];
                    $register->$avarice_c($_GET[$avidity['P']['EAC']], $_GET['slot_number']);
                }
                if (isset($_GET[$avidity['P']['HAI']])) {
                    show_window('', $register->editZTECardForm($_GET[$avidity['P']['EAC']], $_GET['slot_number'], $_GET['card_name']));
                }
            }

            if (wf_CheckPost(array('createZTECard', 'swid', 'slot_number', 'card_name')) AND ( $_POST['chasis_number'] != '')) {
                $register->createZTECard($_POST['swid'], $_POST['chasis_number'], $_POST['slot_number'], $_POST['card_name']);
            }


            if (wf_CheckPost(array('editZTECard', 'swid', 'slot_number', 'card_name'))) {
                $register->editZTECard($_POST['swid'], $_POST['slot_number'], $_POST['card_name']);
            }

            if (wf_CheckPost(array('createZTEBind', 'swid', 'slot_number', 'port_number', 'vlan'))) {
                if ($_POST['port_number'] != '======') {
                    $register->createZTEBind($_POST['swid'], $_POST['slot_number'], $_POST['port_number'], $_POST['vlan']);
                }
            }

            if (wf_CheckPost(array('editZTEBind', 'swid', 'slot_number', 'port_number', 'vlan'))) {
                $register->editZTEBind($_POST['swid'], $_POST['slot_number'], $_POST['port_number'], $_POST['vlan']);
            }

            if (isset($_GET[$avidity['P']['EAI']])) {
                if (wf_CheckGet(array('delete', 'slot_number', 'port_number'))) {
                    $register->deleteZTEBind($_GET[$avidity['P']['EAI']], $_GET['slot_number'], $_GET['port_number']);
                }
                if (wf_CheckGet(array('edit', 'slot_number', 'port_number', 'vlan'))) {
                    $avidity_i = $avidity['M']['DVA'];
                    show_window('', $register->$avidity_i($_GET[$avidity['P']['EAI']], $_GET['slot_number'], $_GET['port_number'], $_GET['vlan']));
                }
                show_window('', $register->listZTEBind($_GET[$avidity['P']['EAI']]));
                $avidity_h = $avidity['M']['LITRE'];
                show_window('', $register->$avidity_h($_GET[$avidity['P']['EAI']]));
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
