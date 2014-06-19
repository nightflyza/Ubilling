<?php

if (cfr('REPORTCREXP')) {


    function zb_GetAllCreditExpireUsers() {
        $query = "SELECT `login`,`Tariff`,`Cash`,`Credit`,`CreditExpire` from `users` WHERE `CreditExpire`!='0'";
        $result = simple_queryall($query);
        return ($result);
    }

    function zb_GetAllCreditNoExpireUsers() {
        $query = "SELECT `login`,`Tariff`,`Cash`,`Credit`,`CreditExpire` from `users` WHERE `CreditExpire`='0' AND `Credit`!='0'";
        $result = simple_queryall($query);
        return ($result);
    }
    
    function zb_CreditSetZero($login) {
        global $billing;
        $login=  mysql_real_escape_string($login);
        $credit=0;
        $billing->setcredit($login,$credit);
        log_register('CHANGE FIX Credit ('.$login.') ON '.$credit);
    }

    function web_ShowAllCrExpireUsers($creditUserList) {

        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();

        $cells = wf_TableCell(__('Login'));
        $cells.= wf_TableCell(__('Tariff'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('Credit'));
        $cells.= wf_TableCell(__('Credit expire'));
        $cells.= wf_TableCell(__('Address'));
        $cells.= wf_TableCell(__('Real name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($creditUserList)) {
            foreach ($creditUserList as $io => $eachuser) {
                $cells = wf_TableCell($eachuser['login']);
                $cells.= wf_TableCell($eachuser['Tariff']);
                $cells.= wf_TableCell($eachuser['Cash']);
                $cells.= wf_TableCell($eachuser['Credit']);
                if ($eachuser['CreditExpire'] != '0') {
                    $expireDate = date("Y-m-d", $eachuser['CreditExpire']);
                } else {
                    $expireDate = __('Forever and ever');
                }
                $cells.= wf_TableCell($expireDate);
                $cells.= wf_TableCell(@$alladdress[$eachuser['login']]);
                $cells.= wf_TableCell(@$allrealnames[$eachuser['login']]);
                $actlinks = '';
                if (cfr('CREDIT')) {
                    $actlinks.=wf_Link('?module=report_creditexpire&fastfix='.$eachuser['login'], wf_img('skins/icon_repair.gif', __('Fix')), false, '');
                }
                $actlinks.= wf_Link('?module=userprofile&username=' . $eachuser['login'], web_profile_icon(), false, '');
                $actlinks.= wf_Link('?module=creditexpireedit&username=' . $eachuser['login'], wf_img('skins/icon_calendar.gif', __('Change') . ' ' . __('Credit expire')), false, '');

                $cells.= wf_TableCell($actlinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');

        return($result);
    }

    
    //credit fast cleanup
    if (wf_CheckGet(array('fastfix'))) {
        zb_CreditSetZero($_GET['fastfix']);
        rcms_redirect('?module=report_creditexpire');
    }
    
    $creditExpireUsers = zb_GetAllCreditExpireUsers();
    $creditNoExpireUsers = zb_GetAllCreditNoExpireUsers();
    show_window(__('Users with their credits expires'), web_ShowAllCrExpireUsers($creditExpireUsers));
    show_window(__('Users credit limit which has no expiration date'), web_ShowAllCrExpireUsers($creditNoExpireUsers));
} else {
    show_error(__('You cant control this module'));
}
?>
