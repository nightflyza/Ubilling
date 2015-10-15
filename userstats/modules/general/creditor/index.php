<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

/**
 * 
 * needs SC_VSCREDIT=1 in userstats.ini
 * @param type $login user's login
 * @return price for all virtual services if such exists for user
 */
function zbs_VServicesGetPrice($login) {
    $us_config = zbs_LoadConfig();
    $price = 0;
    if (isset($us_config['SC_VSCREDIT'])) {
        if ($us_config['SC_VSCREDIT']) {
            $tag_query = "SELECT * FROM `tags` WHERE `login` =  '" . $login . "' ";
            $alltags = simple_queryall($tag_query);
            $VS_query = "SELECT * FROM `vservices`";
            $allVS = simple_queryall($VS_query);

            if (!empty($alltags)) {
                foreach ($alltags as $io => $eachtag) {
                    foreach ($allVS as $each => $ia) {
                        if ($eachtag['tagid'] == $ia['tagid']) {
                            $price += $ia['price'];
                        }
                    }
                }
            }
        }
    }
    return($price);
}

/**
 * returns main self-credit module form
 * 
 * @return string
 */
function zbs_ShowCreditForm() {
    $inputs = la_tag('center');
    $inputs.= la_HiddenInput('setcredit', 'true');
    $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
    $inputs.= la_delimiter();
    $inputs.= la_Submit(__('Set me credit please'));
    $inputs.= la_tag('center', true);
    $form = la_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * logs succeful self credit fact into database
 * 
 * @param  string $login existing users login
 * 
 * @return void
 */
function zbs_CreditLogPush($login) {
    $login = mysql_real_escape_string($login);
    $date = curdatetime();
    $query = "INSERT INTO `zbssclog` (`id` , `date` , `login` ) VALUES ( NULL , '" . $date . "', '" . $login . "');";
    nr_query($query);
}

/**
 * checks is user current month use SC module and returns false if used or true if feature available
 * 
 * @param  string $login existing users login
 * 
 * @return bool
 */
function zbs_CreditLogCheckMonth($login) {
    $login = mysql_real_escape_string($login);
    $pattern = date("Y-m");
    $query = "SELECT `id` from `zbssclog` WHERE `login` LIKE '" . $login . "' AND `date` LIKE '" . $pattern . "%';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Checks is user tariff allowed for use of credit feature
 * 
 * @param array  $sc_allowed
 * @param string $usertariff
 * @return bool
 */
function zbs_CreditCheckAllowed($sc_allowed, $usertariff) {
    $result = true;
    if (!empty($sc_allowed)) {
        if (isset($sc_allowed[$usertariff])) {
            $result = true;
        } else {
            $result = false;
        }
    }
    return ($result);
}

/**
 * Sets credit for user, logs it, sets expire date and redirects in main profile
 * 
 * @param string $user_login
 * @param float  $tariffprice
 * @param int    $sc_price
 * @param string $scend
 * @param int $sc_cashtypeid
 * 
 *  @return void
 */
function zbs_CreditDoTheCredit($user_login, $tariffprice, $sc_price, $scend, $sc_cashtypeid) {
    zbs_CreditLogPush($user_login);
    billing_setcredit($user_login, $tariffprice + $sc_price);
    billing_setcreditexpire($user_login, $scend);
    zbs_PaymentLog($user_login, '-' . $sc_price, $sc_cashtypeid, "SCFEE");
    billing_addcash($user_login, '-' . $sc_price);
    show_window('', __('Now you have a credit'));
    rcms_redirect("index.php");
}

// if SC enabled
if ($us_config['SC_ENABLED']) {

// let needed params
    $current_credit = zbs_CashGetUserCredit($user_login);
    $current_cash = zbs_CashGetUserBalance($user_login);
    $current_credit_expire = zbs_CashGetUserCreditExpire($user_login);
    $us_currency = $us_config['currency'];
    $sc_minday = $us_config['SC_MINDAY'];
    $sc_maxday = $us_config['SC_MAXDAY'];
    $sc_term = $us_config['SC_TERM'];
    $sc_price = $us_config['SC_PRICE'];
    $sc_cashtypeid = $us_config['SC_CASHTYPEID'];
    $sc_monthcontrol = $us_config['SC_MONTHCONTROL'];
    $sc_allowed = array();
    $vs_price = zbs_VServicesGetPrice($user_login);
//allowed tariffs option
    if (isset($us_config['SC_TARIFFSALLOWED'])) {
        if (!empty($us_config['SC_TARIFFSALLOWED'])) {
            $sc_allowed = explode(',', $us_config['SC_TARIFFSALLOWED']);
            $sc_allowed = array_flip($sc_allowed);
        }
    }
    $tariff = zbs_UserGetTariff($user_login);
    $tariffprice = zbs_UserGetTariffPrice($tariff);
    $tariffprice+=$vs_price;
    $cday = date("d");

//welcome message
    $wmess = __('If you wait too long to pay for the service, here you can get credit for') . ' ' . $sc_term . ' ' . __('days. The price of this service is') . ': ' . $sc_price . ' ' . $us_currency . '. ';
    if (isset($us_config['SC_VSCREDIT'])) {
        if ($us_config['SC_VSCREDIT']) {
            $wmess.= __('Also you promise to pay for the current month, in accordance with your service plan') . ".";
        } else {
            $wmess.= __('Also you promise to pay for the current month, in accordance with your service plan') . "." . __('Additional services are not subject to credit') . ".";
        }
    }
    show_window(__('Credits'), $wmess);

//if day is something like that needed
    if (($cday <= $sc_maxday) AND ( $cday >= $sc_minday)) {
        if (($current_credit <= 0) AND ( $current_credit_expire == 0)) {
            //ok, no credit now
            // allow user to set it
            if (!isset($_POST['setcredit'])) {
                show_window('', zbs_ShowCreditForm());
            } else {
                // set credit routine
                if (isset($_POST['agree'])) {
                    //calculate credit expire date
                    $nowTimestamp = time();
                    $creditSeconds = ($sc_term * 86400); //days*secs
                    $creditOffset = $nowTimestamp + $creditSeconds;
                    $scend = date("Y-m-d", $creditOffset);

                    if (abs($current_cash) <= $tariffprice) {
                        if ($current_cash < 0) {


                            if (zbs_CreditCheckAllowed($sc_allowed, $tariff)) {
                                //additional month contol enabled
                                if ($sc_monthcontrol) {
                                    if (zbs_CreditLogCheckMonth($user_login)) {
                                        //check for allow option
                                        zbs_CreditDoTheCredit($user_login, $tariffprice, $sc_price, $scend, $sc_cashtypeid);
                                    } else {
                                        show_window(__('Sorry'), __('You already used credit feature in current month. Only one usage per month is allowed.'));
                                    }
                                } else {
                                    zbs_CreditDoTheCredit($user_login, $tariffprice, $sc_price, $scend, $sc_cashtypeid);
                                }
                                //end of self credit main code
                            } else {
                                show_window(__('Sorry'), __('This feature is not allowed on your tariff'));
                            }
                        } else {
                            //to many money
                            show_window(__('Sorry'), __('Sorry, sum of money in the account is enought to use service without credit'));
                        }
                    } else {
                        //no use self credit
                        show_window(__('Sorry'), __('Sorry, your debt does not allow to continue working in the credit'));
                    }
                } else {
                    // agreement check
                    show_window(__('Sorry'), __('You must accept our policy'));
                }
            }
        } else {
            //you alredy have it 
            show_window(__('Sorry'), __('You already have a credit'));
        }
    } else {
        show_window(__('Sorry'), __('You can take a credit only between') . ' ' . $sc_minday . __(' and ') . $sc_maxday . ' ' . __('days of the month'));
    }



//and if disabled :(
} else {
    show_window(__('Sorry'), __('Unfortunately self credits is disabled'));
}
?>
