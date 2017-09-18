<?php

if (cfr('MAC')) {

    $altercfg = $ubillingConfig->getAlter();
    $newmac_report = $altercfg['NMREP_INMACCHG'];
    $newmacselector = $altercfg['SIMPLENEWMACSELECTOR'];

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        // change mac if need
        if (isset($_POST['newmac'])) {
            $mac = trim($_POST['newmac']);
            $allUsedMacs = zb_getAllUsedMac();
            //check mac for free
            if (zb_checkMacFree($mac, $allUsedMacs)) {
                //validate mac format
                if (check_mac_format($mac)) {
                    $ip = zb_UserGetIP($login);
                    $old_mac = zb_MultinetGetMAC($ip);
                    multinet_change_mac($ip, $mac);
                    log_register("MAC CHANGE (" . $login . ") " . $ip . " FROM  " . $old_mac . " ON " . $mac);
                    multinet_rebuild_all_handlers();
                    // need reset after mac change
                    $billing->resetuser($login);
                    log_register("RESET User (" . $login . ")");
                    //ressurect user if required
                    if (@$altercfg['RESETHARD']) {
                        zb_UserResurrect($login);
                    }
                    if (isset($altercfg['MACCHGDOUBLEKILL'])) {
                        if ($altercfg['MACCHGDOUBLEKILL']) {
                            $billing->resetuser($login);
                            log_register("RESET User (" . $login . ") DOUBLEKILL");
                        }
                    }
                } else {
                    //show error when MAC haz wrong format
                    show_error(__('This MAC have wrong format'));
                    //debuglog
                    log_register("MACINVALID TRY (" . $login . ")");
                }
            } else {
                //show error when MAC is in usage
                show_error(__('This MAC is currently used'));
                //debuglog
                log_register("MACDUPLICATE TRY (" . $login . ")");
            }
        }

        $userip = zb_UserGetIP($login);
        $current_mac = zb_MultinetGetMAC($userip);
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


// Edit form construct
        $fieldnames = array('fieldname1' => __('Current MAC'), 'fieldname2' => __('New MAC'));
        $fieldkey = 'newmac';
        if (($newmacselector) AND ( !isset($_GET['oldform']))) {
            // new mac selector
            $form = web_EditorStringDataFormMACSelect($fieldnames, $fieldkey, $useraddress, $current_mac);
        } else {
            // old school mac input
            $form = web_EditorStringDataFormMAC($fieldnames, $fieldkey, $useraddress, $current_mac);
        }

        $form.=wf_Link('?module=macedit&username=' . $login, wf_img('skins/done_icon.png') . ' ' . __('Simple MAC selector'), false, 'ubButton');
        $form.=wf_Link('?module=macedit&username=' . $login . '&oldform=true', wf_img('skins/categories_icon.png') . ' ' . __('Manual MAC input'), false, 'ubButton');
        $form.=wf_delimiter();

        if ($newmac_report) {
            $form.= wf_tag('h2') . __('Unknown MAC address') . wf_tag('h2', true) . zb_NewMacShow();
        }
        $form.=web_UserControls($login);

        show_window(__('Edit MAC'), $form);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
