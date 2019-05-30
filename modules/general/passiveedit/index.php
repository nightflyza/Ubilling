<?php
if (cfr('PASSIVE')) {

$alterCfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
$FreezeDaysChargeEnabled = ( isset($alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) && $alterCfg['FREEZE_DAYS_CHARGE_ENABLED'] );
$makeRedirect = false;
$FreezingAvailable = true;

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);

    if ($FreezeDaysChargeEnabled) {
        if ( wf_CheckPost(array('newfreezedaysamnt')) ) {
            simple_update_field('frozen_charge_days', 'freeze_days_amount', $_POST['newfreezedaysamnt'], "WHERE `login`='" . $login . "' ");
            log_register('CHANGE Freeze days amount ('.$login.') ON '. $_POST['newfreezedaysamnt']);
            $makeRedirect = true;
        }

        if ( wf_CheckPost(array('newwrkdaystorestorefrzdays')) ) {
            simple_update_field('frozen_charge_days', 'work_days_restore', $_POST['newwrkdaystorestorefrzdays'], "WHERE `login`='" . $login . "' ");
            log_register('CHANGE Workdays amount to restore freeze days ('.$login.') ON '. $_POST['newwrkdaystorestorefrzdays']);
            $makeRedirect = true;
        }
    }

   // change passive  if need
    if ( isset($_POST['newpassive']) ) {
        $passive=$_POST['newpassive'];
        $billing->setpassive($login,$passive);
        log_register('CHANGE Passive ('.$login.') ON '.$passive);
        $makeRedirect = true;
    }

    if ($makeRedirect) {rcms_redirect("?module=passiveedit&username=".$login);}

    $current_passive=zb_UserGetStargazerData($login);
    $current_passive=$current_passive['Passive'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';

    $form  = '';
    $form2 = '';

    if ($FreezeDaysChargeEnabled) {
        $FrozenAllQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $login . "';";
        $FrozenAll = simple_queryall($FrozenAllQuery);

        if (!empty($FrozenAll)) {
            foreach ($FrozenAll as $usr => $usrlogin) {
                $FrzDaysAmount           = $usrlogin['freeze_days_amount'];
                $FrzDaysUsed             = $usrlogin['freeze_days_used'];
                $DaysWorked              = $usrlogin['days_worked'];
                $WrkDaysToRestoreFrzDays = $usrlogin['work_days_restore'];

                $inputs = '';

                if ( ($FrzDaysUsed >= $FrzDaysAmount) && ($DaysWorked <= $WrkDaysToRestoreFrzDays) ) {
                    $FreezingAvailable = false;

                    $cells = wf_TableCell(__('User'), '', 'row2');
                    $cells.= wf_TableCell($useraddress, '', 'row3');
                    $rows = wf_TableRow($cells);

                    $cells = wf_TableCell(__('Current passive state'), '', 'row2');
                    $cells.= wf_TableCell(web_trigger($current_passive), '', 'row2');
                    $rows.= wf_TableRow($cells);
                    $table = wf_TableBody($rows, '100%', 0, '');
                    $inputs.= $table;
                    $inputs.= wf_tag('h3', false, '', 'style="color:#e95802; font-weight:600"');
                    $inputs.= __('Changing freeze status is unavailable: total amount of freeze days used up');
                    $inputs.= wf_tag('h3', true);
                    $inputs.= wf_delimiter();
                }

                $cells = wf_TableCell(__('Freeze days used'), '', 'row2');
                $cells.= wf_TableCell($FrzDaysUsed, '', 'row2');
                $rows = wf_TableRow($cells);

                $cells = wf_TableCell(__('Freeze days available'), '', 'row2');
                $cells.= wf_TableCell($FrzDaysAmount - $FrzDaysUsed, '', 'row2');
                $rows.= wf_TableRow($cells);

                $cells = wf_TableCell(__('Days worked after freeze days used up'), '', 'row2');
                $cells.= wf_TableCell($DaysWorked, '', 'row2');
                $rows.= wf_TableRow($cells);

                $cells = wf_TableCell(__('Workdays left to restore'), '', 'row2');
                $cells.= wf_TableCell($WrkDaysToRestoreFrzDays - $DaysWorked, '', 'row2');
                $rows.= wf_TableRow($cells);

                $cells = wf_TableCell(__('Freeze days total amount'), '', 'row2');
                $cells.= wf_TableCell($FrzDaysAmount, '', 'row2');
                $cells.= wf_TableCell(wf_TextInput('newfreezedaysamnt', '', '', false, 40), '', 'row3');
                $rows.= wf_TableRow($cells);

                $cells = wf_TableCell(__('Workdays amount to restore freeze days'), '', 'row2');
                $cells.= wf_TableCell($WrkDaysToRestoreFrzDays, '', 'row2');
                $cells.= wf_TableCell(wf_TextInput('newwrkdaystorestorefrzdays', '', '', false, 40), '', 'row3');
                $rows.= wf_TableRow($cells);

                $table = wf_TableBody($rows, '100%', 0, '');

                $inputs.= $table;
                $inputs.= wf_Submit(__('Change'));
                $inputs.= wf_delimiter();
                $form2 = wf_Form("", 'POST', $inputs, '');
            }
        }
    }

if ($FreezingAvailable) {
    // Edit form construct
    $fieldname = __('Current passive state');
    $fieldkey = 'newpassive';
    $form.= web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_passive);
}

$form.= wf_delimiter() . $form2;
$form.= web_UserControls($login);
// show form
show_window(__('Edit passive'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
