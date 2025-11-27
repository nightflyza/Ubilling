<?php

if (cfr('PASSIVE')) {

    $alterCfg = $ubillingConfig->getAlter();
    $FreezeDaysChargeEnabled = ( isset($alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) && $alterCfg['FREEZE_DAYS_CHARGE_ENABLED'] );
    $makeRedirect = false;
    $FreezingAvailable = true;

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);

        if ($FreezeDaysChargeEnabled) {
            if (wf_CheckPost(array('newfreezedaysamnt'))) {
                simple_update_field('frozen_charge_days', 'freeze_days_amount', $_POST['newfreezedaysamnt'], "WHERE `login`='" . $login . "' ");
                log_register('FREEZE DAYS AMOUNT CHANGE (' . $login . ') ON `' . $_POST['newfreezedaysamnt'] . '`');
                $makeRedirect = true;
            }

            if (wf_CheckPost(array('newwrkdaystorestorefrzdays'))) {
                simple_update_field('frozen_charge_days', 'work_days_restore', $_POST['newwrkdaystorestorefrzdays'], "WHERE `login`='" . $login . "' ");
                log_register('WORKDAYS AMOUNT TO RESTORE FREEZE DAYS CHANGE (' . $login . ') ON `' . $_POST['newwrkdaystorestorefrzdays'] . '`');
                $makeRedirect = true;
            }
        }

        // change passive  if need
        if (isset($_POST['newpassive'])) {
            $passive = $_POST['newpassive'];
            $billing->setpassive($login, $passive);
            log_register('PASSIVE CHANGE (' . $login . ') ON `' . $passive . '`');
            $makeRedirect = true;
        }

        if ($makeRedirect) {
            rcms_redirect("?module=passiveedit&username=" . $login);
        }

        $current_passive = zb_UserGetStargazerData($login);
        $current_passive = $current_passive['Passive'];
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';

        $form = '';
        $form2 = '';

        if ($FreezeDaysChargeEnabled) {
            $FrozenAllQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $login . "';";
            $FrozenAll = simple_queryall($FrozenAllQuery);

            if (!empty($FrozenAll)) {
                foreach ($FrozenAll as $usr => $usrlogin) {
                    $FrzDaysAmount = $usrlogin['freeze_days_amount'];
                    $FrzDaysUsed = $usrlogin['freeze_days_used'];
                    $DaysWorked = $usrlogin['days_worked'];
                    $WrkDaysToRestoreFrzDays = $usrlogin['work_days_restore'];

                    $inputs = '';

                    if (($FrzDaysUsed >= $FrzDaysAmount) && ($DaysWorked <= $WrkDaysToRestoreFrzDays)) {
                        $FreezingAvailable = false;

                        $inputs .= wf_tag('h3', false, '', 'style="color:#e95802; font-weight:600"');
                        $inputs .= __('Changing freeze status is unavailable: total amount of freeze days used up');
                        $inputs .= wf_tag('h3', true);
                    }

                    $cells = wf_TableCell(__('Freeze days used'), '', 'row2');
                    $cells .= wf_TableCell($FrzDaysUsed, '', 'row2');
                    $rows = wf_TableRow($cells);

                    $cells = wf_TableCell(__('Freeze days available'), '', 'row2');
                    $cells .= wf_TableCell($FrzDaysAmount - $FrzDaysUsed, '', 'row2');
                    $rows .= wf_TableRow($cells);

                    $cells = wf_TableCell(__('Days worked after freeze days used up'), '', 'row2');
                    $cells .= wf_TableCell($DaysWorked, '', 'row2');
                    $rows .= wf_TableRow($cells);

                    $cells = wf_TableCell(__('Workdays left to restore'), '', 'row2');
                    $cells .= wf_TableCell($WrkDaysToRestoreFrzDays - $DaysWorked, '', 'row2');
                    $rows .= wf_TableRow($cells);

                    $cells = wf_TableCell(__('Freeze days total amount'), '', 'row2');
                    $cells .= wf_TableCell($FrzDaysAmount, '', 'row2');
                    $cells .= wf_TableCell(wf_TextInput('newfreezedaysamnt', '', '', false, 40), '', 'row3');
                    $rows .= wf_TableRow($cells);

                    $cells = wf_TableCell(__('Workdays amount to restore freeze days'), '', 'row2');
                    $cells .= wf_TableCell($WrkDaysToRestoreFrzDays, '', 'row2');
                    $cells .= wf_TableCell(wf_TextInput('newwrkdaystorestorefrzdays', '', '', false, 40), '', 'row3');
                    $rows .= wf_TableRow($cells);

                    $table = wf_TableBody($rows, '100%', 0, '');

                    $inputs .= $table;
                    $inputs .= wf_Submit(__('Change'));
                    $inputs .= wf_delimiter();
                    $form2 = wf_Form("", 'POST', $inputs, '');
                }
            }
        }

        $freezingAllowed = true;
        $freezingDenyReason = '';
        if (@$alterCfg['DDT_ANTIFREEZE']) {
//                                            .  .
//                                            |\_|\
//                                            | a_a\
//                                            | | "]
//                                        ____| '-\___
//                                       /.----.___.-'\
//                                      //        _    \
//                                     //   .-. (~v~) /|
//                                    |'|  /\:  .--  / \
//                                   // |-/  \_/____/\/~|
//                                  |/  \ |  []_|_|_] \ |
//                                  | \  | \ |___   _\ ]_}
//                                  | |  '-' /   '.'  |
//                                  | |     /    /|:  | 
//                                  | |     |   / |:  /\
//                                  | |     /  /  |  /  \
//                                  | |    |  /  /  |    \
//                                  \ |    |/\/  |/|/\    \
//                                   \|\ |\|  |  | / /\/\__\
//                                    \ \| | /   | |__
//                                         / |   |____)
//                                         |_/

            if (!cfr('SWRTZNGRFREEZE')) {
                $ddt = new DoomsDayTariffs(true);
                $protectedTariffs = $ddt->getCurrentTariffsDDT();
                $userData = zb_UserGetStargazerData($login);
                $userTariff = $userData['Tariff'];
                if (isset($protectedTariffs[$userTariff])) {
                    $freezingAllowed = false;
                    $freezingDenyReason .= __('This user uses one of doomsday tariffs') . '. ' . __('Freezing denied') . '.';
                }
            }
        }

        if ($freezingAllowed) {
// Edit form construct
            $fieldname = __('Current passive state');
            $fieldkey = 'newpassive';
            $form .= web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_passive, !$FreezingAvailable);
            $form .= $form2;
            $form .= web_UserControls($login);
        } else {
            $messages = new UbillingMessageHelper();
            $form = $messages->getStyledMessage($freezingDenyReason, 'error');
            $form .= wf_delimiter();
            $form .= web_UserControls($login);
        }
// show form
        show_window(__('Edit passive'), $form);
    } else {
        show_error(__('Something went wrong') . ': EX_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}
?>
