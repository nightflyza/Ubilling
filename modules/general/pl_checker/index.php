<?php

if (cfr('PLCHECKER')) {

    /**
     * Checks is field available in database?
     * 
     * @param string $table
     * @param string $login
     * 
     * @return bool
     */
    function zb_plcheckfield($table, $login) {
        $login = mysql_real_escape_string($login);
        $table = mysql_real_escape_string($table);
        $query = "SELECT `id` from `" . $table . "` where `login`='" . $login . "'";
        $result = simple_queryall($query);
        $result = (!empty($result)) ? 1 : 0;
        return ($result);
    }

    /**
     * Checks user nethost availablity
     * 
     * @param string $login
     * 
     * @return int 1 - ok, 0 - not exists, false - duplicate
     */
    function zb_plchecknethost($login) {
        $login = mysql_real_escape_string($login);
        $ip = zb_UserGetIP($login);
        $query = "SELECT `id` from `nethosts` where `ip`='" . $ip . "'";
        $all = simple_queryall($query);
        if (!empty($all)) {
            $result = 1;
        }
        if (empty($all)) {
            $result = 0;
        }
        if (sizeof($all) > 1) {
            $result = -1;
        }
        return ($result);
    }

    /**
     * Returns default field fixing form
     * 
     * @param string $login
     * @param string $field
     * @param bool $flag
     * 
     * @return string
     */
    function web_plfixerform($login, $field, $flag) {
        $result = '';
        if (($flag != 1)) {
            $inputs = wf_HiddenInput('fixme', $field);
            $inputs.= wf_Submit(__('Fix'));
            $result.=wf_Form('', 'POST', $inputs, '');
        }
        return($result);
    }

    /**
     * Performs fixing of some user database fields
     * 
     * @param string $login
     * @param string $field
     * 
     * @return void
     */
    function zb_plfixer($login, $field) {
        if ($field == 'emails') {
            zb_UserCreateEmail($login, '');
            rcms_redirect("?module=pl_checker&username=" . $login);
        }
        if ($field == 'contracts') {
            zb_UserCreateContract($login, '');
            rcms_redirect("?module=pl_checker&username=" . $login);
        }
        if ($field == 'phones') {
            zb_UserCreatePhone($login, '', '');
            rcms_redirect("?module=pl_checker&username=" . $login);
        }
        if ($field == 'realname') {
            zb_UserCreateRealName($login, '');
            rcms_redirect("?module=pl_checker&username=" . $login);
        }
        if ($field == 'userspeeds') {
            zb_UserCreateSpeedOverride($login, '0');
            rcms_redirect("?module=pl_checker&username=" . $login);
        }
        if ($field == 'nethosts') {
            $problemType = zb_plchecknethost($login);
            $userIp = zb_UserGetIP($login);
            if (!empty($userIp)) {
                $userNetwork = zb_NetworkGetByIp($userIp);
                $randommac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                if (zb_mac_unique($randommac)) {
                    $newMacvalue = $randommac;
                } else {
                    show_error('Oops');
                    $newMacvalue = '';
                }
                if ($userNetwork != false) {
                    switch ($problemType) {
                        case 0:
                            multinet_add_host($userNetwork, $userIp, $newMacvalue);
                            multinet_rebuild_all_handlers();
                            break;
                        case -1:
                            $currentUserMac = zb_MultinetGetMAC($userIp);
                            multinet_delete_host($userIp);
                            multinet_add_host($userNetwork, $userIp, $currentUserMac);
                            multinet_rebuild_all_handlers();
                            break;
                    }
                    rcms_redirect("?module=pl_checker&username=" . $login);
                } else {
                    show_error(__('No network detected'));
                }
            }
        }
    }

    /**
     * Renders module interface
     * 
     * @param string $login
     * 
     * @return string
     */
    function web_plchecker($login) {
        $login = mysql_real_escape_string($login);
        $result = '';
        $emails = zb_plcheckfield('emails', $login);
        $contracts = zb_plcheckfield('contracts', $login);
        $phones = zb_plcheckfield('phones', $login);
        $realname = zb_plcheckfield('realname', $login);
        $userspeeds = zb_plcheckfield('userspeeds', $login);
        $nethosts = zb_plchecknethost($login);

        $cells = wf_TableCell(__('Status'));
        $cells.= wf_TableCell(__('Parameter'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');


        $cells = wf_TableCell(web_bool_led($emails));
        $cells.= wf_TableCell(__('Email'));
        $cells.= wf_TableCell(web_plfixerform($login, 'emails', $emails));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(web_bool_led($contracts));
        $cells.= wf_TableCell(__('Contract'));
        $cells.= wf_TableCell(web_plfixerform($login, 'contracts', $contracts));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(web_bool_led($phones));
        $cells.= wf_TableCell(__('Phone') . '/' . __('Mobile'));
        $cells.= wf_TableCell(web_plfixerform($login, 'phones', $phones));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(web_bool_led($realname));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(web_plfixerform($login, 'realname', $realname));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(web_bool_led($userspeeds));
        $cells.= wf_TableCell(__('Speed override'));
        $cells.= wf_TableCell(web_plfixerform($login, 'userspeeds', $userspeeds));
        $rows.= wf_TableRow($cells, 'row3');

        switch ($nethosts) {
            case 0:
                $nhProblemType = web_bool_led(0).__('Not exists');
                break;
            case -1:
                $nhProblemType = web_bool_led(0).__('Duplicate');
                break;
            case 1:
                $nhProblemType = web_bool_led(1);
                break;
        }
        $cells = wf_TableCell($nhProblemType);
        $cells.= wf_TableCell(__('Network'));
        $cells.= wf_TableCell(web_plfixerform($login, 'nethosts', $nethosts));
        $rows.= wf_TableRow($cells, 'row3');

        $result.=wf_TableBody($rows, '100%', 0);
        $result.=web_UserControls($login);
        return($result);
    }

    if (isset($_GET['username'])) {
        $login = $_GET['username'];

        if (isset($_POST['fixme'])) {
            zb_plfixer($login, $_POST['fixme']);
        }

        show_window(__('User integrity checker'), web_plchecker($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
