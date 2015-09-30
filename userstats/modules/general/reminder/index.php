<?php

//functions and variables

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if ($us_config['REMINDER_ENABLED']) {

    if (isset($us_config['REMINDER_PRICE'])) {
        $rr_price = $us_config['REMINDER_PRICE'];
    } else {
        die('REMINDER:PRICE not set');
    }

    if (isset($us_config['REMINDER_TAGID'])) {
        $tagid = $us_config['REMINDER_TAGID'];
    } else {
        die('REMINDER:TAGID not set');
    }

    if (isset($us_config['REMINDER_NUMBER_LENGTH'])) {
        $length_number = $us_config['REMINDER_NUMBER_LENGTH'];
    } else {
        die('REMINDER:NUMBER_LENGTH not set');
    }

    if (isset($us_config['REMINDER_DAYS_THRESHOLD'])) {
        $days = $us_config['REMINDER_DAYS_THRESHOLD'];
    } else {
        die('REMINDER:DAYS not set');
    }

    if (isset($us_config['REMINDER_PREFIX'])) {
        $prefix = $us_config['REMINDER_PREFIX'];
    } else {
        die('REMINDER:PREFIX not set');
    }

    if (isset($us_config['REMINDER_FEE'])) {
        $forceFee = $us_config['REMINDER_FEE'];
    } else {
        die('REMINDER:FEE not set');
    }
    if (isset($us_config['REMINDER_CASHTYPEID'])) {
        $rr_cashtypeid = $us_config['REMINDER_CASHTYPEID'];
    } else {
        die('REMINDER:CASHTYPEID not set');
    }


    if (isset($us_config['REMINDER_TURNOFF'])) {
        $turnOffable = $us_config['REMINDER_TURNOFF'];
    } else {
        die('REMINDER:TURNOFF not set');
    }

    $us_currency = $us_config['currency'];

    /**
     * Check if user already has tag
     * @param type $login string
     * @param type $tagid int
     * @return type boolean
     */
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
     * Change user mobile
     * @param type $login string
     * @param type $mobile int
     */
    function zbs_UserChangeMobile($login, $mobile) {
        $login = vf($login);
        $query = "UPDATE `phones` SET `mobile` = '" . $mobile . "' WHERE `login`= '" . $login . "' ;";
        nr_query($query);
        log_register('CHANGE UserMobile (' . $login . ') `' . $mobile . '`');
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

    /**
     * delete sms tag
     * @param type $login
     * @param type $tagid
     */
    function stg_del_user_tagid($login, $tagid) {
        $login = mysql_real_escape_string($login);
        $tagid = vf($tagid, 3);
        $query = "DELETE from `tags` WHERE `login`='" . $login . "' AND`tagid`='" . $tagid . "'";
        nr_query($query);
        log_register('TAGDEL LOGIN (' . $login . ') TAGID [' . $tagid . ']');
    }

    /**
     * Adding user tag form
     * @return main form for tag add
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

    /**
     * Deleting user tag form
     * @return type for for tag delete
     */
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

    /**
     * 
     * @return type form for changin mobile
     */
    function zbs_ShowChangeMobileForm() {
        global $us_config;
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('changemobile', 'true');
        $inputs.= @$us_config['REMINDER_PREFIX'] . ' ';
        $inputs.= la_TextInput('mobile');
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Change mobile'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    //main part of module

    $check = stg_check_user_tag($user_login, $tagid);
    $mobile = zbs_UserGetMobile($user_login);

    if (!empty($mobile)) {
        $m_text = __("Your current mobile number is") . ": " . $mobile;
    } else {
        $m_text = __("Your have empty mobile") . "." . " ";
        if ($us_config['REMINDER_CHANGE_NUMBER']) {
            $m_text.=__("Please enter it below") . ":";
        }
    }

    show_window(__("Mobile"), $m_text);
    if ($us_config['REMINDER_CHANGE_NUMBER']) {
        show_window('', zbs_ShowChangeMobileForm());
    }
    if ($check) {
        $license_text = __("You already enabled payments sms reminder") . ". " . __('You will be reminded within') . ' ' . $days . ' ' . __('days') . ' ' . __('until the expiration of the service') . '. ';
        if ($turnOffable) {
            $license_text.= __("Disable payments sms reminder") . "?";
        }
        show_window(__("Reminder"), $license_text);
        if ($turnOffable) {
            show_window("", zbs_ShowDisableReminderForm());
        }
    } else {
        if (!empty($mobile)) {
            $license_text = __("You can enable payments sms reminder") . '. ';
            $license_text.= __("It costs") . " " . $rr_price . ' ' . $us_currency . " " . __("per month") . "." . la_tag('br');
            if ($forceFee) {
                $license_text.= __("Attention") . "," . " " . __("activation cost is") . " " . $rr_price . " " . $us_currency . " " . __("at once") . ".";
            }
            show_window(__("Reminder"), $license_text);
            show_window('', zbs_ShowEnableReminderForm());
        } else {
            $license_text = __("You can't enable payments sms reminder") . "." . " " . __("Your have empty mobile") . ".";
            show_window(__("Reminder"), $license_text);
        }
    }

    //catch POST's parametrs

    if (isset($_POST['setremind'])) {
        if (isset($_POST['agree'])) {
            stg_add_user_tag($user_login, $tagid);
            if ($forceFee) {
                zbs_PaymentLog($user_login, '-' . $rr_price, $rr_cashtypeid, "REMINDER");
                billing_addcash($user_login, '-' . $rr_price);
            }
        }
        rcms_redirect("?module=reminder");
    }
    if (isset($_POST['deleteremind'])) {
        if ($turnOffable) {
            stg_del_user_tagid($user_login, $tagid);
            rcms_redirect("?module=reminder");
        }
    }
    if (isset($_POST['changemobile'])) {
        if (isset($_POST['mobile'])) {
            $set_mobile = $_POST['mobile'];
            if (!empty($_POST['mobile'])) {
                $set_mobile = preg_replace('/\0/s', '', $set_mobile);
                $set_mobile = strip_tags($set_mobile);
                $set_mobile = str_replace($prefix, '', $set_mobile);
                $set_mobile = mysql_real_escape_string($set_mobile);
                $set_mobile = vf($set_mobile, 3);
                $set_mobile = trim($set_mobile);
                if (strlen($set_mobile) == $length_number) {
                    $set_mobile = $prefix . $set_mobile;
                    zbs_UserChangeMobile($user_login, $set_mobile);
                    rcms_redirect("?module=reminder");
                } else {
                    show_window(__('Sorry'), __('Wrong mobile format'));
                }
            }
        }
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}