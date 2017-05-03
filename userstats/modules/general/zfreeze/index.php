<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if ($us_config['AF_ENABLED']) {
    // freeze options
    $freezeprice = $us_config['AF_FREEZPRICE'];
    $allowed_tariffs_raw = $us_config['AF_TARIFFSALLOWED'];
    $allowed_tariffs = explode(',', $allowed_tariffs_raw);
    $af_cahtypeid = $us_config['AF_CASHTYPEID'];
    $af_currency = $us_config['currency'];

    $userdata = zbs_UserGetStargazerData($user_login);
    $usercash = zbs_CashGetUserBalance($user_login);
    $user_tariff = $userdata['Tariff'];

    $passive_current = $userdata['Passive'];

    //check is tariff allowed?
    if (in_array($user_tariff, $allowed_tariffs)) {
        //is user really active now?
        if ($usercash >= 0) {
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


                    //show some forms and notices
                    $af_message = __('Service "account freeze" will allow you to suspend the charge of the monthly fee during your long absence - such as holidays or vacations. The cost of this service is:') . ' ';
                    $af_message.=la_tag('b') . $freezeprice . ' ' . $af_currency . la_tag('b', true) . '. ';
                    $af_message.=__('Be aware that access to the network will be limited to immediately after you confirm your desire to freeze the account. To unfreeze the account you need to contact the nearest office.');
                    // terms of service
                    show_window(__('Account freezing'), $af_message);

                    //account freezing form
                    $inputs = la_CheckInput('afagree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
                    $inputs.= la_HiddenInput('dofreeze', 'true');
                    $inputs.= la_delimiter();
                    $inputs.= la_Submit(__('I want to freeze my account right now'));
                    $af_form = la_Form('', 'POST', $inputs);

                    show_window('', $af_form);
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
