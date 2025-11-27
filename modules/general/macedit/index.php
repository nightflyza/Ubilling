<?php

if (cfr('MAC')) {

    $altCfg = $ubillingConfig->getAlter();
    $newMacReportFlag = $altCfg['NMREP_INMACCHG'];
    $simpleSelectorFlag = $altCfg['SIMPLENEWMACSELECTOR'];

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'callback', 'vf');
        // change mac if form data captured
        if (ubRouting::checkPost('newmac')) {
            $mac = trim(ubRouting::post('newmac'));
            $allUsedMacs = zb_getAllUsedMac();
            //check mac for free
            if (zb_checkMacFree($mac, $allUsedMacs)) {
                //validate mac format
                if (check_mac_format($mac)) {
                    $ip = zb_UserGetIP($login);
                    $old_mac = zb_MultinetGetMAC($ip);
                    $userData = zb_UserGetAllData($login);
                    $userData = $userData[$login];
                    multinet_change_mac($ip, $mac);
                    if ($altCfg['MULTIGEN_ENABLED']) {
                        $newUserData = $userData;
                        $newUserData['mac'] = strtolower($mac);
                        $mlg = new MultiGen();
                        if ($altCfg['MULTIGEN_POD_ON_MAC_CHANGE'] == 2) {
                            $mlg->podOnExternalEvent($login, $userData, $newUserData);
                            $mlg->podOnExternalEvent($login, $newUserData);
                        }
                        if ($altCfg['MULTIGEN_POD_ON_MAC_CHANGE'] == 1) {
                            $mlg->podOnExternalEvent($login, $newUserData);
                        }
                    }
                    log_register("MAC CHANGE (" . $login . ") " . $ip . " FROM  `" . $old_mac . "` ON `" . $mac . "`");
                    multinet_rebuild_all_handlers();
                    // need reset after mac change
                    $billing->resetuser($login);
                    log_register("RESET (" . $login . ")");
                    //ressurect user if required
                    if (@$altCfg['RESETHARD']) {
                        zb_UserResurrect($login);
                    }
                    if (isset($altCfg['MACCHGDOUBLEKILL'])) {
                        if ($altCfg['MACCHGDOUBLEKILL']) {
                            $billing->resetuser($login);
                            log_register("RESET (" . $login . ") DOUBLEKILL");
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
                log_register('MACDUPLICATE TRY (' . $login . ') `' . $mac . '`');
            }
        }

        $userIp = zb_UserGetIP($login);
        if (!empty($userIp)) {
            $current_mac = zb_MultinetGetMAC($userIp);
            $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';
            $useSelectorInput = (($simpleSelectorFlag) AND ( !ubRouting::checkGet('oldform'))) ? false : true;
            $form = web_MacEditForm($useraddress, $useSelectorInput, $current_mac);

            if ($simpleSelectorFlag) {
                $form .= wf_Link('?module=macedit&username=' . $login, wf_img('skins/done_icon.png') . ' ' . __('Simple MAC selector'), false, 'ubButton');
                $form .= wf_Link('?module=macedit&username=' . $login . '&oldform=true', wf_img('skins/categories_icon.png') . ' ' . __('Manual MAC input'), false, 'ubButton');
            }
            $form .= wf_delimiter();

            if ($newMacReportFlag) {
                $form .= wf_tag('h2') . __('Unknown MAC address') . wf_tag('h2', true) . zb_NewMacShow();
            }
            $form .= web_UserControls($login);

            show_window(__('Edit MAC'), $form);
        } else {
            show_error(__('Something went wrong') . ': ' . __('User not exists'));
        }
    } else {
        show_error(__('Something went wrong') . ': ' . __('Empty login'));
    }
} else {
    show_error(__('You cant control this module'));
}

