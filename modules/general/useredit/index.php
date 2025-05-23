<?php

if (cfr('USEREDIT')) {
    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'login');

        /**
         * Renders basic user params editing interface
         * 
         * @global object $ubillingConfig
         * @global object $branchControl
         * @param string $login
         * 
         * @return void
         */
        function web_UserEditShowForm($login) {
            global $ubillingConfig;
            $altCfg = $ubillingConfig->getAlter();

            $stgData = zb_UserGetStargazerData($login);
            if (!empty($stgData)) {
                $address = zb_UserGetFullAddress($login);
                $realname = zb_UserGetRealName($login);
                $phone = zb_UserGetPhone($login);
                $contract = zb_UserGetContract($login);
                $mobile = zb_UserGetMobile($login);
                $mail = zb_UserGetEmail($login);
                $notes = zb_UserGetNotes($login);
                $ip = $stgData['IP'];
                $mac = zb_MultinetGetMAC($stgData['IP']);
                $speedoverride = zb_UserGetSpeedOverride($login);
                $tariff = $stgData['Tariff'];
                $credit = $stgData['Credit'];
                $cash = $stgData['Cash'];
                $password = $stgData['Password'];
                $aonline = $stgData['AlwaysOnline'];
                $dstatdisable = $stgData['DisabledDetailStat'];
                $passive = $stgData['Passive'];
                $down = $stgData['Down'];
                $creditexpire = $stgData['CreditExpire'];

                if ($altCfg['PASSWORDSHIDE']) {
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
                $cells .= wf_TableCell(__('Current value'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row2');



                //default fields editing
                $cells = wf_TableCell(__('Full address'));
                $cells .= wf_TableCell($address);
                $cells .= wf_TableCell(zb_rightControl('BINDER', wf_Link('?module=binder&username=' . $login, wf_img('skins/icon_build.gif') . ' ' . __('Occupancy'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Password'));
                $cells .= wf_TableCell($password);
                $cells .= wf_TableCell(zb_rightControl('PASSWORD', wf_Link('?module=passwordedit&username=' . $login, wf_img('skins/icon_key.gif') . ' ' . __('Change') . ' ' . __('password'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Real Name'));
                $cells .= wf_TableCell($realname);
                $cells .= wf_TableCell(zb_rightControl('REALNAME', wf_Link('?module=realnameedit&username=' . $login, wf_img('skins/icon_user_16.gif') . ' ' . __('Change') . ' ' . __('Real Name'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Phone'));
                $cells .= wf_TableCell($phone);
                $cells .= wf_TableCell(zb_rightControl('PHONE', wf_Link('?module=phoneedit&username=' . $login, wf_img('skins/icon_phone.gif') . ' ' . __('Change') . ' ' . __('phone'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Mobile'));
                $cells .= wf_TableCell($mobile);
                $cells .= wf_TableCell(zb_rightControl('MOBILE', wf_Link('?module=mobileedit&username=' . $login, wf_img('skins/icon_mobile.gif') . ' ' . __('Change') . ' ' . __('mobile'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Contract'));
                $cells .= wf_TableCell($contract);
                $cells .= wf_TableCell(zb_rightControl('CONTRACT', wf_Link('?module=contractedit&username=' . $login, wf_img('skins/icon_link.gif') . ' ' . __('Change') . ' ' . __('contract'))));
                $rows .= wf_TableRow($cells, 'row3');

                if ($altCfg['BRANCHES_ENABLED']) {
                    global $branchControl;
                    $cells = wf_TableCell(__('Branch'));
                    $cells .= wf_TableCell($branchControl->userGetBranchName($login));
                    $cells .= wf_TableCell(zb_rightControl('BRANCHESUSERMOD', wf_Link('?module=branches&userbranch=' . $login, wf_img('skins/icon_branch.png') . ' ' . __('Change branch'))));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                if ($altCfg['CORPS_ENABLED']) {
                    $greed = new Avarice();
                    $corpsRuntime = $greed->runtime('CORPS');
                    if (!empty($corpsRuntime)) {
                        $corps = new Corps();
                        $corpsCheck = $corps->userIsCorporate($login);
                        $cells = wf_TableCell(__('User type'));
                        $corpControls = zb_rightControl('CORPS', wf_Link(Corps::URL_USER_MANAGE . $login, wf_img('skins/corporate_small.gif') . ' ' . __('Change') . ' ' . __('user type')));

                        if ($corpsCheck) {
                            $cells .= wf_TableCell(__('Corporate user'));
                            $cells .= wf_TableCell($corpControls);
                        } else {
                            $cells .= wf_TableCell(__('Private user'));

                            $cells .= wf_TableCell($corpControls);
                        }
                        $rows .= wf_TableRow($cells, 'row3');
                    }
                }

                $cells = wf_TableCell(__('Email'));
                $cells .= wf_TableCell($mail);
                $cells .= wf_TableCell(zb_rightControl('EMAIL', wf_Link('?module=mailedit&username=' . $login, wf_img('skins/icon_mail_16.png') . ' ' . __('Change') . ' ' . __('email'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Tariff'));
                $cells .= wf_TableCell($tariff);
                $cells .= wf_TableCell(zb_rightControl('TARIFFEDIT', wf_Link('?module=tariffedit&username=' . $login, wf_img('skins/icon_tariff.gif') . ' ' . __('Change') . ' ' . __('tariff'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Speed override'));
                $cells .= wf_TableCell($speedoverride);
                $cells .= wf_TableCell(zb_rightControl('USERSPEED', wf_Link('?module=speededit&username=' . $login, wf_img('skins/icon_speed.gif') . ' ' . __('Change') . ' ' . __('speed override'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Credit'));
                $cells .= wf_TableCell($credit);
                $cells .= wf_TableCell(zb_rightControl('CREDIT', wf_Link('?module=creditedit&username=' . $login, wf_img('skins/icon_credit.gif') . ' ' . __('Change') . ' ' . __('credit limit'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Credit expire'));
                $cells .= wf_TableCell($creditexpire);
                $cells .= wf_TableCell(zb_rightControl('CREDIT', wf_Link('?module=creditexpireedit&username=' . $login, wf_img('skins/icon_calendar.gif') . ' ' . __('Change') . ' ' . __('credit expire date'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Balance'));
                $cells .= wf_TableCell($cash);
                $cells .= wf_TableCell(zb_rightControl('CASH', wf_Link('?module=addcash&username=' . $login . '#cashfield', wf_img('skins/icon_dollar_16.gif') . ' ' . __('Finance operations'))));
                $rows .= wf_TableRow($cells, 'row3');

                if ($altCfg['DISCOUNTS_ENABLED']) {
                    $discounts = new Discounts();
                    $currentUserDiscount = $discounts->getUserDiscount($login);
                    $renderUserDiscount = ($currentUserDiscount) ? $currentUserDiscount . '%' : __('No');
                    $cells = wf_TableCell(__('Discount'));
                    $cells .= wf_TableCell($renderUserDiscount);
                    $cells .= wf_TableCell(zb_rightControl('DISCOUNTS', wf_Link('?module=discountedit&username=' . $login, wf_img('skins/icon_discount_16.png') . ' ' . __('Change discount'))));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                if ($altCfg['TAXSUP_ENABLED']) {
                    $taxa = new TaxSup();
                    $currentUserFee = $taxa->getUserFee($login);
                    $renderUserFee = ($currentUserFee) ? $currentUserFee : __('No');
                    $cells = wf_TableCell(__('Additional fee'));
                    $cells .= wf_TableCell($renderUserFee);
                    $cells .= wf_TableCell(zb_rightControl('TAXSUP', wf_Link($taxa::URL_ME . '&' . $taxa::ROUTE_USERNAME . '=' . $login, wf_img('skins/icon_tax_16.png') . ' ' . __('Change additional fee'))));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                if (isset($altCfg['SIGNUP_PAYMENTS']) && !empty($altCfg['SIGNUP_PAYMENTS'])) {
                    $payment = zb_UserGetSignupPrice($login);
                    $paid = zb_UserGetSignupPricePaid($login);
                    if ($payment != $paid && $payment > 0) {
                        $cells = wf_TableCell(__('Signup paid'));
                        $cells .= wf_TableCell(zb_UserGetSignupPricePaid($login) . '/' . zb_UserGetSignupPrice($login));
                        $cells .= wf_TableCell(zb_rightControl('SIGNUPPRICES', wf_Link('?module=signupprices&username=' . $login, wf_img('skins/icons/register.png', __('Edit signup price')) . ' ' . __('Edit signup price'))));
                        $rows .= wf_TableRow($cells, 'row3');
                    }
                }

                $cells = wf_TableCell(__('IP'));
                $cells .= wf_TableCell($ip);
                $cells .= wf_TableCell(zb_rightControl('PLIPCHANGE', wf_Link('?module=pl_ipchange&username=' . $login, wf_img('skins/icon_ip.gif') . ' ' . __('Change') . ' ' . __('IP'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('MAC'));
                $cells .= wf_TableCell($mac);
                $cells .= wf_TableCell(zb_rightControl('MAC', wf_Link('?module=macedit&username=' . $login, wf_img('skins/icon_ether_16.png') . ' ' . __('Change') . ' ' . __('MAC'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('AlwaysOnline'));
                $cells .= wf_TableCell(web_trigger($aonline));
                $cells .= wf_TableCell(zb_rightControl('ALWAYSONLINE', wf_Link('?module=aoedit&username=' . $login, wf_img('skins/icon_online.gif') . ' ' . __('AlwaysOnline'))));
                $rows .= wf_TableRow($cells, 'row3');

                if (@$altCfg['DSTAT_ENABLED']) {
                    $cells = wf_TableCell(__('Disable detailed stats'));
                    $cells .= wf_TableCell(web_trigger($dstatdisable));
                    $cells .= wf_TableCell(zb_rightControl('DSTAT', wf_Link('?module=dstatedit&username=' . $login, wf_img('skins/icon_stats_16.gif') . ' ' . __('Disable detailed stats'))));
                    $rows .= wf_TableRow($cells, 'row3');
                }
                $cells = wf_TableCell(__('User passive'));
                $cells .= wf_TableCell(web_trigger($passive));
                $cells .= wf_TableCell(zb_rightControl('PASSIVE', wf_Link('?module=passiveedit&username=' . $login, wf_img('skins/icon_passive.gif') . ' ' . __('User passive'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('User down'));
                $cells .= wf_TableCell(web_trigger($down));
                $cells .= wf_TableCell(zb_rightControl('DOWN', wf_Link('?module=downedit&username=' . $login, wf_img('skins/icon_down.gif') . ' ' . __('User down'))));
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Passport data'));
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell(zb_rightControl('PDATA', wf_Link('?module=pdataedit&username=' . $login, wf_img('skins/icon_passport.gif') . ' ' . __('Change') . ' ' . __('passport data'))));
                $rows .= wf_TableRow($cells, 'row3');

                if ($altCfg['CONDET_ENABLED']) {
                    $conDet = new ConnectionDetails();
                    $cells = wf_TableCell(__('Connection details'));
                    $cells .= wf_TableCell($conDet->renderData($login));
                    $cells .= wf_TableCell(zb_rightControl('CONDET', wf_Link('?module=condetedit&username=' . $login, wf_img('skins/cableseal_small.png') . ' ' . __('Change') . ' ' . __('Connection details'))));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                //additional comments indication
                if ($altCfg['ADCOMMENTS_ENABLED']) {
                    $adcomments = new ADcomments('USERNOTES');
                    if (cfr('NOTES')) {
                        $indicatorIcon = ' ' . wf_Link('?module=notesedit&username=' . $login, $adcomments->getCommentsIndicator($login), false, '');
                    } else {
                        $indicatorIcon = ' ' . $adcomments->getCommentsIndicator($login);
                    }
                } else {
                    $indicatorIcon = '';
                }

                $cells = wf_TableCell(__('Notes'));
                $cells .= wf_TableCell($notes . $indicatorIcon);
                $cells .= wf_TableCell(zb_rightControl('NOTES', wf_Link('?module=notesedit&username=' . $login, wf_img('skins/icon_note.gif') . ' ' . __('Notes'))));
                $rows .= wf_TableRow($cells, 'row3');

                $form = wf_TableBody($rows, '100%', 0, 'useredittable');

                //primary user options
                show_window(__('Edit user') . ' ' . $address, $form);
                //custom fields editor here
                $cf = new CustomFields($login);
                $cfEditForm = $cf->renderUserFieldEditor();
                if (!empty($cfEditForm)) {
                    show_window(__('Additional profile fields'), $cfEditForm);
                }

                //basic profile controls
                show_window('', web_UserControls($login));
            } else {
                show_error(__('Strange exception') . ': ' . __('User not exists'));
                show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
            }
        }

        web_UserEditShowForm($login);
    } else {
        show_error(__('Strange exception') . ': ' . __('Empty login'));
        show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
    }
} else {
    show_error(__('You cant control this module'));
}
