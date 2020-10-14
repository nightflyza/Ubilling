<?php

if (cfr('ZBSMAN')) {

    /**
     * Returns array of users which denied from userstats usage
     * 
     * @return array
     */
    function zb_GetUserStatsDeniedAll() {
        $access_raw = zb_StorageGet('ZBS_DENIED');
        $result = array();
        if (!empty($access_raw)) {
            $access_raw = base64_decode($access_raw);
            $access_raw = unserialize($access_raw);
            $result = $access_raw;
        } else {
            //first access
            $newarray = serialize($result);
            $newarray = base64_encode($newarray);
            zb_StorageSet('ZBS_DENIED', $newarray);
        }
        return ($result);
    }

    /**
     * Sets user as denied for using userstats
     * 
     * @param string $login
     * 
     * @return void
     */
    function zb_SetUserStatsDenied($login) {
        $access = zb_GetUserStatsDeniedAll();
        if (!empty($login)) {
            $access[$login] = 'NOP';
            $newarray = serialize($access);
            $newarray = base64_encode($newarray);
            zb_StorageSet('ZBS_DENIED', $newarray);
            log_register("ZBSMAN SET DENIED (" . $login . ")");
        }
    }

    /**
     * Sets user as allowed for usage of userstats
     * 
     * @param string $login
     * 
     * @return void
     */
    function zb_SetUserStatsUnDenied($login) {
        $access = zb_GetUserStatsDeniedAll();
        if (!empty($login)) {
            if (isset($access[$login])) {
                unset($access[$login]);
                $newarray = serialize($access);
                $newarray = base64_encode($newarray);
                zb_StorageSet('ZBS_DENIED', $newarray);
                log_register("ZBSMAN SET ALLOWED (" . $login . ")");
            }
        }
    }

    /**
     * Returns array of users which is denied from usage of helpdesk
     * 
     * @return array
     */
    function zb_GetHelpdeskDeniedAll() {
        $access_raw = zb_StorageGet('ZBS_HELP_DENIED');
        $result = array();
        if (!empty($access_raw)) {
            $access_raw = base64_decode($access_raw);
            $access_raw = unserialize($access_raw);
            $result = $access_raw;
        } else {
            //first access
            $newarray = serialize($result);
            $newarray = base64_encode($newarray);
            zb_StorageSet('ZBS_HELP_DENIED', $newarray);
        }
        return ($result);
    }

    /**
     * Sets user as denied for helpdesk usage
     * 
     * @param string $login
     * 
     * @return void
     */
    function zb_SetHelpdeskDenied($login) {
        $access = zb_GetHelpdeskDeniedAll();
        if (!empty($login)) {
            $access[$login] = 'NOP';
            $newarray = serialize($access);
            $newarray = base64_encode($newarray);
            zb_StorageSet('ZBS_HELP_DENIED', $newarray);
            log_register("ZBSMAN SET HELPDESKDENIED (" . $login . ")");
        }
    }

    /**
     * Sets user as allowed for helpdesk usage
     * 
     * @param string $login
     * 
     * @return void
     */
    function zb_SetHelpdeskUnDenied($login) {
        $access = zb_GetHelpdeskDeniedAll();
        if (!empty($login)) {
            if (isset($access[$login])) {
                unset($access[$login]);
                $newarray = serialize($access);
                $newarray = base64_encode($newarray);
                zb_StorageSet('ZBS_HELP_DENIED', $newarray);
                log_register("ZBSMAN SET ALLOWED (" . $login . ")");
            }
        }
    }

    /**
     * Renders userstats/helpdesk access modification form
     * 
     * @param string $login
     * 
     * @return string
     */
    function web_ZbsManEditForm($login) {
        $access = zb_GetUserStatsDeniedAll();
        $helpdesk = zb_GetHelpdeskDeniedAll();

        if (isset($access[$login])) {
            $checked_us = true;
        } else {
            $checked_us = false;
        }

        if (isset($helpdesk[$login])) {
            $checked_hd = true;
        } else {
            $checked_hd = false;
        }

        $inputs = wf_CheckInput('access_denied', __('Userstats access denied for this user'), true, $checked_us);
        $inputs .= wf_CheckInput('helpdesk_denied', __('Helpdesk access denied for this user'), true, $checked_hd);
        $inputs .= wf_HiddenInput('zbsman_change', 'true');
        $inputs .= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Renders lists of users denied to use of userstats/helpdesk
     * 
     * @return void
     */
    function web_ZbsManUserLists() {
        $access = zb_GetUserStatsDeniedAll();
        $access = array_keys($access);
        $helpdesk = zb_GetHelpdeskDeniedAll();
        $helpdesk = array_keys($helpdesk);
        show_window(__('Users that cant access Userstats'), web_UserArrayShower($access));
        show_window(__('Users that cant access ticketing service'), web_UserArrayShower($helpdesk));
    }

    /**
     * Controller part
     */
    if (ubRouting::checkGet('username')) {
        $altCfg = $ubillingConfig->getAlter();
        $login = ubRouting::get('username', 'mres');


        if (ubRouting::checkPost('zbsman_change')) {
            //set user denied
            if (ubRouting::checkPost('access_denied')) {
                zb_SetUserStatsDenied($login);
                ubRouting::nav("?module=pl_zbsman&username=" . $login);
            } else {
                zb_SetUserStatsUnDenied($login);
                ubRouting::nav("?module=pl_zbsman&username=" . $login);
            }

            //set user helpdesk denied
            if (ubRouting::checkPost('helpdesk_denied')) {
                zb_SetHelpdeskDenied($login);
                ubRouting::nav("?module=pl_zbsman&username=" . $login);
            } else {
                zb_SetHelpdeskUnDenied($login);
                ubRouting::nav("?module=pl_zbsman&username=" . $login);
            }
        }

        //userstats permissions
        if (!ubRouting::checkGet('showzbsdenied') AND ! ubRouting::checkGet('showopdenied')) {
            $zbsDeniedControls = wf_Link('?module=pl_zbsman&username=' . $login . '&showzbsdenied=true', web_icon_charts('Who?'));
            show_window(__('Userstats access controls') . ' ' . $zbsDeniedControls, web_ZbsManEditForm($login));
        } else {
            if (ubRouting::checkGet('showzbsdenied')) {
                show_window('', wf_BackLink('?module=pl_zbsman&username=' . $login));
                web_ZbsManUserLists();
            }
        }


        //openpayz access management
        if (@$altCfg['OPENPAYZ_SUPPORT']) {
            $opDenied = new OpDenied();
            //changing state if required
            if (ubRouting::checkPost($opDenied::PROUTE_DENY_LOGIN)) {
                $opDenied->setUserDenyState(ubRouting::post($opDenied::PROUTE_DENY_LOGIN), ubRouting::checkPost($opDenied::PROUTE_DENY_FLAG));
                ubRouting::nav("?module=pl_zbsman&username=" . $login);
            }

            //render form
            if (!ubRouting::checkGet('showzbsdenied') AND ! ubRouting::checkGet('showopdenied')) {
                $opDeniedControls = wf_Link('?module=pl_zbsman&username=' . $login . '&showopdenied=true', web_icon_charts('Who?'));
                show_window(__('OpenPayz access') . ' ' . $opDeniedControls, $opDenied->renderModifyForm($login));
            } else {
                //render denied list
                if (ubRouting::checkGet('showopdenied')) {
                    $allOpDenied = $opDenied->getAllDenied();
                    show_window('', wf_BackLink('?module=pl_zbsman&username=' . $login));
                    show_window(__('Users which denied from OpenPayz usage'), web_UserArrayShower($allOpDenied));
                }
            }
        }

        //backlinks
        show_window('', web_UserControls($login));
    } else {
        show_error(__('Strange exeption'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
