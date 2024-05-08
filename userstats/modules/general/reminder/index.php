<?php
$usRemider = new USReminder();

//functions and variables

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$usConfig = new UserStatsConfig();

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

    if ($usConfig->getUstasParam('REMINDER_CHANGE_NUMBER', 0)) {
        show_window('', zbs_ShowChangeMobileForm());
    }

    if ($usConfig->getUstasParam('REMINDER_EMAIL_ENABLED', 0) and
        $usConfig->getUstasParam('REMINDER_EMAIL_CHANGE_ALLOWED', 0)) {

        show_window('Your E-mail', zbs_ShowChangeEmailForm($user_login));
    }

    if ($check) {
        $license_text = __("You already enabled payments sms reminder") . ". ";

        if ($us_config['REMINDER_ENABLED'] != 2) {
            $license_text.= la_delimiter() . __('You will be reminded within') . ' ' . $days . ' ' . __('days') . ' ' . __('until the expiration of the service') . '. ';
        }

        if (!empty($us_config['REMINDER_CONSIDER_CREDIT'])) {
            $daysCredit = (empty($us_config['REMINDER_DAYS_THRESHOLD_CREDIT'])) ? $days : $us_config['REMINDER_DAYS_THRESHOLD_CREDIT'];
            $license_text.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysCredit . ' ' . __('days') . ' ' . __('before the credit expire date') . '. ';
        }

        if (!empty($us_config['REMINDER_CONSIDER_CAP'])) {
            $daysCAP = (empty($us_config['REMINDER_DAYS_THRESHOLD_CAP'])) ? $days : $us_config['REMINDER_DAYS_THRESHOLD_CAP'];
            $license_text.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysCAP . ' ' . __('days') . ' ' . __('before inactiveness penalty will be applied') . '. ';
        }

        if (!empty($us_config['REMINDER_CONSIDER_FROZEN'])) {
            $daysFrozen = (empty($us_config['REMINDER_DAYS_THRESHOLD_FROZEN'])) ? $days : $us_config['REMINDER_DAYS_THRESHOLD_FROZEN'];
            $license_text.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysFrozen . ' ' . __('days') . ' ' . __('before available freeze days run out') . '. ';
        }

        if ($turnOffable) {
            $license_text.= la_delimiter(2) . __("Disable payments sms reminder") . "?";
        }

        show_window(__("Reminder"), $license_text);

        if ($turnOffable) {
            show_window("", zbs_ShowDisableReminderForm());
        }
    } else {
        if (!empty($mobile)) {

            $checkNumber = trim($mobile);
            $checkNumber = str_replace($prefix, '', $checkNumber);
            $checkNumber = vf($checkNumber, 3);
            $checkNumber = $prefix . $checkNumber;
            $prefixSize = strlen($prefix);
            $checkSize = $prefixSize + $length_number;
            if (strlen($checkNumber) == $checkSize) {
                $license_text = __("You can enable payments sms reminder") . '. ';
                $license_text.= __("It costs") . " " . $rr_price . ' ' . $us_currency . " " . __("per month") . "." . la_tag('br');
                if ($forceFee) {
                    $license_text.= __("Attention") . "," . " " . __("activation cost is") . " " . $rr_price . " " . $us_currency . " " . __("at once") . ".";
                }
                show_window(__("Reminder"), $license_text);
                show_window('', zbs_ShowEnableReminderForm());
            } else {
                $license_text = __('Wrong mobile format');
                show_window(__("Reminder"), $license_text);
            }
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
            rcms_redirect("?module=reminder");
        } else {
            show_window(__('Sorry'), __('You must accept our policy'));
        }
    }
    if (isset($_POST['deleteremind'])) {
        if ($turnOffable) {
            if (isset($_POST['agree'])) {
                stg_del_user_tagid($user_login, $tagid);
                rcms_redirect("?module=reminder");
            } else {
                show_window(__('Sorry'), __('You must accept our policy'));
            }
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

    if (ubRouting::checkPost(array('changemail', 'email'))) {
            $set_mail = ubRouting::post('email');

            if (!empty($set_mail)) {
                $set_mail = preg_replace('/\0/s', '', $set_mail);
                $set_mail = strip_tags($set_mail);
                $set_mail = mysql_real_escape_string($set_mail);
                $set_mail = trim($set_mail);
                if (empty($set_mail)) {
                    show_window(__('Sorry'), __('Wrong e-mail format'));
                } else {
                    zbs_UserChangeEmail($user_login, $set_mail);
                    rcms_redirect("?module=reminder");
                }
            }
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}