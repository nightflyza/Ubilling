<?php

$altcfg = $ubillingConfig->getAlter();

if (@$altcfg[OnuRegister::MODULE_CONFIG]) {
    if (cfr(OnuRegister::MODULE_RIGHTS)) {
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

            if (isset($_GET[$avidity['P']['EAC']])) {
                show_window(__('OLT'), $register->listZteDevice($_GET[$avidity['P']['EAC']]));
                show_window('', wf_BackLink(OnuRegister::MODULE_URL));
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
                    show_window('', $register->editZteCardForm($_GET[$avidity['P']['EAC']], $_GET['slot_number'], $_GET['card_name']));
                }
            } elseif (isset($_GET[$avidity['P']['EAI']])) {
                if (wf_CheckGet(array('delete'))) {
                    $register->deleteZteBind($_GET[$avidity['P']['EAI']], $_GET['slot_number'], $_GET['port_number']);
                }
                if (wf_CheckGet(array('edit', 'vlan'))) {
                    $avidity_i = $avidity['M']['DVA'];
                    show_window('', $register->$avidity_i($_GET[$avidity['P']['EAI']], $_GET['slot_number'], $_GET['port_number'], $_GET['vlan']));
                }
                show_window(__('OLT'), $register->listZteDevice($_GET[$avidity['P']['EAI']]));
                show_window('', wf_BackLink(OnuRegister::MODULE_URL));
                show_window('', $register->listZteBind($_GET[$avidity['P']['EAI']]));
                $avidity_h = $avidity['M']['LITRE'];
                show_window('', $register->$avidity_h($_GET[$avidity['P']['EAI']]));
            } else {
                show_window(__('All ZTE OLTs'), $register->$avidity_b());
                show_window('', wf_BackLink(PONizer::URL_ONULIST));
            }
            if (wf_CheckPost(array('createZteCard', 'swid', 'card_name'))) {
                $register->createZteCard($_POST['swid'], $_POST['chasis_number'], $_POST['slot_number'], $_POST['card_name']);
            }
            if (wf_CheckPost(array('editZteCard', 'swid', 'card_name'))) {
                $register->editZteCard($_POST['swid'], $_POST['slot_number'], $_POST['card_name']);
            }
            if (wf_CheckPost(array('createZteBind', 'swid', 'vlan'))) {
                if ($_POST['port_number'] != '======') {
                    $register->createZteBind($_POST['swid'], $_POST['slot_number'], $_POST['port_number'], $_POST['vlan']);
                }
            }
            if (wf_CheckPost(array('editZteBind', 'swid', 'vlan'))) {
                $register->editZteBind($_POST['swid'], $_POST['slot_number'], $_POST['port_number'], $_POST['vlan']);
            }
            zb_BillingStats(true, 'zteonureg');
        } else {
            show_error(__(OnuRegister::ERROR_NO_LICENSE));
        }
    } else {
        show_error(__(OnuRegister::ERROR_NO_RIGHTS));
    }
} else {
    show_error(__(OnuRegister::ERROR_NOT_ENABLED));
}