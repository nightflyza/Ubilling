<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if ($us_config['REMINDER_ENABLED']) {

    if (isset($us_config['REMINDER_PRICE'])) {
        $rr_price = $us_config['REMINDER_PRICE'];
    } else {
        die('REMINDER_PRICE not set');
    }
    if (isset($us_config['REMINDER_TAGID'])) {
        $tagid = $us_config['REMINDER_TAGID'];
    } else {
        die('REMINDER TAGID not set');
    }
    $us_currency = $us_config['currency'];

    function stg_check_user_tag($login, $tagid) {
        $login = mysql_real_escape_string($login);
        $tagid = vf($tagid, 3);
        $query = "SELECT `id` FROM `tags` WHERE `login`= '" . $login . "' AND `tagid`= '" . $tagid . "'";
        $check = simple_queryall($query);
        if (!empty($check)) {
            return(true);
        } else {
            return(false);
        }
    }

    /**
     * 
     * add sms tag for user to remind him about apropos payment
     * @param type $login string
     * @param type $tagid integer
     */
    function stg_add_user_tag($login, $tagid) {
        $login = mysql_real_escape_string($login);
        $tagid = vf($tagid, 3);
        $query = "INSERT INTO `tags` (`id` ,`login` ,`tagid`) VALUES (NULL , '" . $login . "', '" . $tagid . "'); ";
        nr_query($query);
        log_register('TAGADD (' . $login . ') TAGID [' . $tagid . ']');
    }

    function stg_del_user_tagid($login, $tagid) {
        $login = mysql_real_escape_string($login);
        $tagid = vf($tagid, 3);
        $query = "DELETE from `tags` WHERE `login`='" . $login . "' AND`tagid`='" . $tagid . "'";
        nr_query($query);
        log_register('TAGDEL LOGIN (' . $login . ') TAGID [' . $tagid . ']');
    }

    /**
     * 
     * @return string main form of Reminder
     */
    function zbs_ShowEnableReminderForm() {
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('setremind', 'true');
        $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Remind me please'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    function zbs_ShowDisableReminderForm() {
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('deleteremind', 'true');
        $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Don\'t remind me'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    $check = stg_check_user_tag($user_login, $tagid);
    if ($check) {
        $license_text = __("You already enabled payments sms reminder") . ". ";
        $license_text.= __("Disable payments sms reminder") . "?";
        show_window(__("Reminder"), $license_text);
        show_window("", zbs_ShowDisableReminderForm());
    } else {
        $license_text = __("You can enable payments sms reminder") . '. ';
        $license_text.= __("It costs") . " " . $rr_price . ' ' . $us_currency . " " . __("per month") . ".";
        show_window(__("Reminder"), $license_text);
        show_window('', zbs_ShowEnableReminderForm());
    }
    if (isset($_POST['setremind'])) {
        stg_add_user_tag($user_login, $tagid);
        rcms_redirect("?module=reminder");
    }
    if (isset($_POST['deleteremind'])) {
        stg_del_user_tagid($user_login, $tagid);
        rcms_redirect("?module=reminder");
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}