<?php

if (cfr('USEREDIT')) {
    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);

        function web_UserEditShowForm($login) {

            global $ubillingConfig;
            $alter_conf = $ubillingConfig->getAlter();

            $stgdata = zb_UserGetStargazerData($login);
            $address = zb_UserGetFullAddress($login);
            $realname = zb_UserGetRealName($login);
            $phone = zb_UserGetPhone($login);
            $contract = zb_UserGetContract($login);
            $mobile = zb_UserGetMobile($login);
            $mail = zb_UserGetEmail($login);
            $notes = zb_UserGetNotes($login);
            $ip = $stgdata['IP'];
            $mac = zb_MultinetGetMAC($stgdata['IP']);
            $speedoverride = zb_UserGetSpeedOverride($login);
            $tariff = $stgdata['Tariff'];
            $credit = $stgdata['Credit'];
            $cash = $stgdata['Cash'];
            $password = $stgdata['Password'];
            $aonline = $stgdata['AlwaysOnline'];
            $dstatdisable = $stgdata['DisabledDetailStat'];
            $passive = $stgdata['Passive'];
            $down = $stgdata['Down'];
            $creditexpire = $stgdata['CreditExpire'];


            if ($alter_conf['PASSWORDSHIDE']) {
                $password = __('Hidden');
            }


            if ($speedoverride == '0') {
                $speedoverride = __('No');
            }


            if ($creditexpire > 0) {
                $creditexpire = date("Y-m-d", $creditexpire);
            } else {
                $creditexpire = __('No');
            }


            $cells = wf_TableCell(__('Parameter'));
            $cells.=wf_TableCell(__('Current value'));
            $cells.=wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row2');

            //express card
            if ($alter_conf['CRM_MODE']) {
                $cells = wf_TableCell(__('Express card'));
                $cells.=wf_TableCell('');
                $cells.=wf_TableCell(wf_Link('?module=expresscard&username=' . $login, wf_img('skins/express.gif') . ' ' . __('Edit')));
                $rows.= wf_TableRow($cells, 'row3');
            }

            //default fields editing
            $cells = wf_TableCell(__('Full address'));
            $cells.= wf_TableCell($address);
            $cells.= wf_TableCell(wf_Link('?module=binder&username=' . $login, wf_img('skins/icon_build.gif') . ' ' . __('Occupancy')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Password'));
            $cells.= wf_TableCell($password);
            $cells.= wf_TableCell(wf_Link('?module=passwordedit&username=' . $login, wf_img('skins/icon_key.gif') . ' ' . __('Change') . ' ' . __('password')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell($realname);
            $cells.= wf_TableCell(wf_Link('?module=realnameedit&username=' . $login, wf_img('skins/icon_user.gif') . ' ' . __('Change') . ' ' . __('Real Name')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Phone'));
            $cells.= wf_TableCell($phone);
            $cells.= wf_TableCell(wf_Link('?module=phoneedit&username=' . $login, wf_img('skins/icon_phone.gif') . ' ' . __('Change') . ' ' . __('phone')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Mobile'));
            $cells.= wf_TableCell($mobile);
            $cells.= wf_TableCell(wf_Link('?module=mobileedit&username=' . $login, wf_img('skins/icon_mobile.gif') . ' ' . __('Change') . ' ' . __('mobile')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Contract'));
            $cells.= wf_TableCell($contract);
            $cells.= wf_TableCell(wf_Link('?module=contractedit&username=' . $login, wf_img('skins/icon_link.gif') . ' ' . __('Change') . ' ' . __('contract')));
            $rows.= wf_TableRow($cells, 'row3');

            if ($alter_conf['BRANCHES_ENABLED']) {
                global $branchControl;
                $cells = wf_TableCell(__('Branch'));
                $cells.= wf_TableCell($branchControl->userGetBranchName($login));
                $cells.= wf_TableCell(wf_Link('?module=branches&userbranch=' . $login, wf_img('skins/icon_branch.png') . ' ' . __('Change branch')));
                $rows.= wf_TableRow($cells, 'row3');
            }

            if ($alter_conf['CORPS_ENABLED']) {
                $greed = new Avarice();
                $corpsRuntime = $greed->runtime('CORPS');
                if (!empty($corpsRuntime)) {
                    $corps = new Corps();
                    $corpsCheck = $corps->userIsCorporate($login);
                    $cells = wf_TableCell(__('User type'));
                    if (cfr('CORPS')) {
                        $corpControls = wf_Link(Corps::URL_USER_MANAGE . $login, wf_img('skins/corporate_small.gif') . ' ' . __('Change') . ' ' . __('user type'));
                    } else {
                        $corpControls = '';
                    }
                    if ($corpsCheck) {
                        $cells.= wf_TableCell(__('Corporate user'));
                        $cells.= wf_TableCell($corpControls);
                    } else {
                        $cells.= wf_TableCell(__('Private user'));

                        $cells.= wf_TableCell($corpControls);
                    }
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Email'));
            $cells.= wf_TableCell($mail);
            $cells.= wf_TableCell(wf_Link('?module=mailedit&username=' . $login, wf_img('skins/icon_mail.gif') . ' ' . __('Change') . ' ' . __('email')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell($tariff);
            $cells.= wf_TableCell(wf_Link('?module=tariffedit&username=' . $login, wf_img('skins/icon_tariff.gif') . ' ' . __('Change') . ' ' . __('tariff')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Speed override'));
            $cells.= wf_TableCell($speedoverride);
            $cells.= wf_TableCell(wf_Link('?module=speededit&username=' . $login, wf_img('skins/icon_speed.gif') . ' ' . __('Change') . ' ' . __('speed override')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Credit'));
            $cells.= wf_TableCell($credit);
            $cells.= wf_TableCell(wf_Link('?module=creditedit&username=' . $login, wf_img('skins/icon_credit.gif') . ' ' . __('Change') . ' ' . __('credit limit')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Credit expire'));
            $cells.= wf_TableCell($creditexpire);
            $cells.= wf_TableCell(wf_Link('?module=creditexpireedit&username=' . $login, wf_img('skins/icon_calendar.gif') . ' ' . __('Change') . ' ' . __('credit expire date')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Balance'));
            $cells.= wf_TableCell($cash);
            $cells.= wf_TableCell(wf_Link('?module=addcash&username=' . $login . '#cashfield', wf_img('skins/icon_dollar.gif') . ' ' . __('Finance operations')));
            $rows.= wf_TableRow($cells, 'row3');

            if (isset($alter_conf['SIGNUP_PAYMENTS']) && !empty($alter_conf['SIGNUP_PAYMENTS'])) {
                $payment = zb_UserGetSignupPrice($login);
                $paid = zb_UserGetSignupPricePaid($login);
                if ($payment != $paid && $payment > 0) {
                    $cells = wf_TableCell(__('Signup paid'));
                    $cells .= wf_TableCell(zb_UserGetSignupPricePaid($login) . '/' . zb_UserGetSignupPrice($login));
                    $cells .= wf_TableCell(wf_Link('?module=signupprices&username=' . $login, wf_img('skins/icons/register.png', __('Edit signup price')) . ' ' . __('Edit signup price')));
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('IP'));
            $cells.= wf_TableCell($ip);
            $cells.= wf_TableCell(wf_Link('?module=pl_ipchange&username=' . $login, wf_img('skins/icon_ip.gif') . ' ' . __('Change') . ' ' . __('IP')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('MAC'));
            $cells.= wf_TableCell($mac);
            $cells.= wf_TableCell(wf_Link('?module=macedit&username=' . $login, wf_img('skins/icon_ether.gif') . ' ' . __('Change') . ' ' . __('MAC')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('AlwaysOnline'));
            $cells.= wf_TableCell(web_trigger($aonline));
            $cells.= wf_TableCell(wf_Link('?module=aoedit&username=' . $login, wf_img('skins/icon_online.gif') . ' ' . __('AlwaysOnline')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Disable detailed stats'));
            $cells.= wf_TableCell(web_trigger($dstatdisable));
            $cells.= wf_TableCell(wf_Link('?module=dstatedit&username=' . $login, wf_img('skins/icon_stats.gif') . ' ' . __('Disable detailed stats')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('User passive'));
            $cells.= wf_TableCell(web_trigger($passive));
            $cells.= wf_TableCell(wf_Link('?module=passiveedit&username=' . $login, wf_img('skins/icon_passive.gif') . ' ' . __('User passive')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('User down'));
            $cells.= wf_TableCell(web_trigger($down));
            $cells.= wf_TableCell(wf_Link('?module=downedit&username=' . $login, wf_img('skins/icon_down.gif') . ' ' . __('User down')));
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Passport data'));
            $cells.= wf_TableCell('');
            $cells.= wf_TableCell(wf_Link('?module=pdataedit&username=' . $login, wf_img('skins/icon_passport.gif') . ' ' . __('Change') . ' ' . __('passport data')));
            $rows.= wf_TableRow($cells, 'row3');

            if ($alter_conf['CONDET_ENABLED']) {
                $conDet = new ConnectionDetails();
                $cells = wf_TableCell(__('Connection details'));
                $cells.= wf_TableCell($conDet->renderData($login));
                $cells.= wf_TableCell(wf_Link('?module=condetedit&username=' . $login, wf_img('skins/cableseal_small.png') . ' ' . __('Change') . ' ' . __('Connection details')));
                $rows.= wf_TableRow($cells, 'row3');
            }

            //additional comments indication
            if ($alter_conf['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('USERNOTES');
                $indicatorIcon = ' ' . wf_Link('?module=notesedit&username=' . $login, $adcomments->getCommentsIndicator($login), false, '');
            } else {
                $indicatorIcon = '';
            }

            $cells = wf_TableCell(__('Notes'));
            $cells.= wf_TableCell($notes . $indicatorIcon);
            $cells.= wf_TableCell(wf_Link('?module=notesedit&username=' . $login, wf_img('skins/icon_note.gif') . ' ' . __('Notes')));
            $rows.= wf_TableRow($cells, 'row3');

            $form = wf_TableBody($rows, '100%', '0');


            show_window(__('Edit user') . ' ' . $address, $form);
            cf_FieldEditor($login);
            show_window('', web_UserControls($login));
        }

        web_UserEditShowForm($login);
    }
} else {
    show_error(__('You cant control this module'));
}
?>