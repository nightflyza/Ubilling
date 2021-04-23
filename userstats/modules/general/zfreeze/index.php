<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$FreezeDaysChargeEnabled = ( isset($us_config['FREEZE_DAYS_CHARGE_ENABLED']) && $us_config['FREEZE_DAYS_CHARGE_ENABLED'] );
$FreezingAvailable = true;

if ($us_config['AF_ENABLED']) {
    // freeze options
    $freezeprice = $us_config['AF_FREEZPRICE'];
    $freezepriceperiod = (empty($us_config['AF_FREEZPRICE_PERIOD'])) ? '' : $us_config['AF_FREEZPRICE_PERIOD'];
    $allowed_tariffs_raw = $us_config['AF_TARIFFSALLOWED'];
    $allowed_tariffs = explode(',', $allowed_tariffs_raw);
    $allowed_any_tariff = (isset($us_config['AF_TARIFF_ALLOW_ANY'])) ? $us_config['AF_TARIFF_ALLOW_ANY'] : 0;
    $af_cahtypeid = $us_config['AF_CASHTYPEID'];
    $af_currency = $us_config['currency'];
    $AllowFreezeOnNegativeBal = ( isset($us_config['FREEZE_ALLOW_ON_NEGATIVE_BALANCE']) && $us_config['FREEZE_ALLOW_ON_NEGATIVE_BALANCE'] );

    $userdata = zbs_UserGetStargazerData($user_login);
    $usercash = zbs_CashGetUserBalance($user_login);
    $user_tariff = $userdata['Tariff'];

    $passive_current = $userdata['Passive'];

    //check is tariff allowed?
    if ($allowed_any_tariff or in_array($user_tariff, $allowed_tariffs)) {
        //is user really active now?
        if ($usercash >= 0 || $AllowFreezeOnNegativeBal) {
            //check for prevent dual freeze
            if ($passive_current != '1') {

                // freezing subroutine
                if (isset($_POST['dofreeze'])) {
                    if (isset($_POST['afagree'])) {
                        //all ok, lets freeze account
                        billing_freeze($user_login);

                        //push cash fee anyway
                        zbs_PaymentLog($user_login, '-' . $freezeprice, $af_cahtypeid, "AFFEE");
                        billing_addcash($user_login, '-' . $freezeprice);
                        log_register('CHANGE Passive ('.$user_login.') ON 1');
                        rcms_redirect("index.php");
                    } else {
                        show_window(__('Error'), __('You must accept our policy'));
                    }
                } else {
                    $inputs = '';

                    if ($FreezeDaysChargeEnabled) {
                        $FrozenAll = zbs_getFreezeDaysChargeData($user_login);

                        if (!empty($FrozenAll)) {
                            foreach ($FrozenAll as $usr => $usrlogin) {
                                $FrzDaysAmount           = $usrlogin['freeze_days_amount'];
                                $FrzDaysUsed             = $usrlogin['freeze_days_used'];
                                $DaysWorked              = $usrlogin['days_worked'];
                                $WrkDaysToRestoreFrzDays = $usrlogin['work_days_restore'];

                                $inputs.= la_delimiter();

                                if ( ($FrzDaysUsed >= $FrzDaysAmount) && ($DaysWorked <= $WrkDaysToRestoreFrzDays) ) {
                                    $FreezingAvailable = false;

                                    $inputs.= la_tag('h3', false, '', 'style="color:#e95802; font-weight:600"');
                                    $inputs.= __('Changing freeze status is unavailable: total amount of freeze days used up');
                                    $inputs.= la_tag('h3', true);
                                    $inputs.= la_delimiter();
                                }

                                $cells = la_TableCell(__('Freeze days used'), '', 'row2');
                                $cells.= la_TableCell($FrzDaysUsed, '', 'row2');
                                $rows = la_TableRow($cells);

                                $cells = la_TableCell(__('Freeze days available'), '', 'row2');
                                $cells.= la_TableCell($FrzDaysAmount - $FrzDaysUsed, '', 'row2');
                                $rows.= la_TableRow($cells);

                                $cells = la_TableCell(__('Days worked after freeze days used up'), '', 'row2');
                                $cells.= la_TableCell($DaysWorked, '', 'row2');
                                $rows.= la_TableRow($cells);

                                $cells = la_TableCell(__('Workdays left to restore'), '', 'row2');
                                $cells.= la_TableCell($WrkDaysToRestoreFrzDays - $DaysWorked, '', 'row2');
                                $rows.= la_TableRow($cells);

                                $cells = la_TableCell(__('Freeze days total amount'), '', 'row2');
                                $cells.= la_TableCell($FrzDaysAmount, '', 'row2');
                                $rows.= la_TableRow($cells);

                                $cells = la_TableCell(__('Workdays amount to restore freeze days'), '', 'row2');
                                $cells.= la_TableCell($WrkDaysToRestoreFrzDays, '', 'row2');
                                $rows.= la_TableRow($cells);

                                $table = la_TableBody($rows, '100%', 0, '');

                                $inputs.= $table;
                            }
                        }
                    }

                    //show some forms and notices
                    $af_message = __('Service "account freeze" will allow you to suspend the charge of the monthly fee during your long absence - such as holidays or vacations. The cost of this service is:') . ' ';
                    $af_message.= la_tag('b') . $freezeprice . ' ' . $af_currency . ' ' . $freezepriceperiod . la_tag('b', true) . '. ';
                    $af_message.= __('Be aware that access to the network will be limited to immediately after you confirm your desire to freeze the account. To unfreeze the account you need to contact the nearest office.');
                    // terms of service
                    show_window(__('Account freezing'), $af_message . $inputs);

                    if ($FreezingAvailable) {
                        //account freezing form
                        $inputs = la_CheckInput('afagree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
                        $inputs .= la_HiddenInput('dofreeze', 'true');
                        $inputs .= la_delimiter();
                        $inputs .= la_Submit(__('I want to freeze my account right now'));
                        $af_form = la_Form('', 'POST', $inputs);

                        show_window('', $af_form);
                    }
                }
            } else {
                show_window('', __('Your account has been frozen'));
            }
        } else {
            show_window(__('Sorry'), __('Your account is now a negative amount'));
        }
    } else {
        show_window(__('Sorry'), __('Your tariff does not provide this service'));
    }
} else {
    show_window(__('Sorry'), __('Unfortunately account freeze is now disabled'));
}
?>
