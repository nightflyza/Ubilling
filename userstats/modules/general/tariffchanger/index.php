<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();



//tariff changing options
$us_currency = $us_config['currency'];
$tc_enabled = $us_config['TC_ENABLED'];
$tc_priceup = $us_config['TC_PRICEUP'];
$tc_pricedown = $us_config['TC_PRICEDOWN'];
$tc_pricesimilar = $us_config['TC_PRICESIMILAR'];
$tc_credit = $us_config['TC_CREDIT'];
$tc_cashtypeid = $us_config['TC_CASHTYPEID'];
$user_data = zbs_UserGetStargazerData($user_login);
$user_cash = $user_data['Cash'];
$user_credit = $user_data['Credit'];
$user_credit_expire = $user_data['CreditExpire'];
$user_tariff = zbs_UserGetTariff($user_login);
$user_tariffnm = $user_data['TariffChange'];

/////////////// Loading tariff move matrix API

/**
 * Returns user move-allowed tariffs in tariff-matrix mode
 * 
 * @param string $user_tariff
 * @return bool/array
 */
function zbs_MatrixGetAllowed($user_tariff) {
    $matrix = parse_ini_file('config/tariffmatrix.ini');
    $result = false;
    if (!empty($matrix)) {
        if (isset($matrix[$user_tariff])) {
            //extract tariff movement rules
            $result = explode(',', $matrix[$user_tariff]);
        } else {
            //no tariff match
            $result = false;
        }
    } else {
        //no matrix entries
        $result = false;
    }
    return ($result);
}

/**
 * Returns array of allowed to move matrix tariffs
 * 
 * @return string
 */
function zbs_MatrixGetAllowedFrom() {
    $matrix = parse_ini_file('config/tariffmatrix.ini');
    $result = array();
    if (!empty($matrix)) {
        foreach ($matrix as $io => $each) {
            $result[] = $io;
        }
    }
    return ($result);
}

//////////////// allowed tariffs permissions
$tc_extended_matrix = $us_config['TC_EXTENDED_MATRIX'];
if (!$tc_extended_matrix) {
    //old 2 opts style
    $tc_tariffsallowed = explode(',', $us_config['TC_TARIFFSALLOWED']);
    $tc_tariffenabledfrom = explode(',', $us_config['TC_TARIFFENABLEDFROM']);
} else {
    //extended tariffs move matrix
    $tc_tariffsallowed = zbs_MatrixGetAllowed($user_tariff);
    $tc_tariffenabledfrom = zbs_MatrixGetAllowedFrom();
}


////////////////////////////////

/**
 * Returns selector of allowed tariffs to move, except current tariff
 * 
 * @param string $tc_tariffsallowed
 * @param string $user_tariff
 * @return string
 */
function zbs_TariffSelector($tc_tariffsallowed, $user_tariff) {
    $params = array();
    if (!empty($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $io => $eachtariff) {
            if ($eachtariff != $user_tariff) {
                $params[trim($eachtariff)] = __($eachtariff);
            }
        }
    }

    $result = la_Selector('newtariff', $params, '', '', false);
    return ($result);
}

/**
 * Returns array of tariff change prices for allowed tariffs
 * 
 * @param array  $tc_tariffsallowed
 * @param string $user_tariff
 * @param float  $tc_priceup
 * @param float  $tc_pricedown
 * @param float  $tc_pricesimilar
 * @return float
 */
function zbs_TariffGetChangePrice($tc_tariffsallowed, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar) {
    $allprices = zbs_TariffGetAllPrices();
    $current_fee = $allprices[$user_tariff];
    $result = array();
    if (!empty($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $eachtariff) {
            //if higer then current fee
            if ($allprices[$eachtariff] > $current_fee) {
                $result[$eachtariff] = $tc_priceup;
            }
            //if lower then current
            if ($allprices[$eachtariff] < $current_fee) {
                $result[$eachtariff] = $tc_pricedown;
            }
            // if eq
            if ($allprices[$eachtariff] == $current_fee) {
                $result[$eachtariff] = $tc_pricesimilar;
            }
        }
    }
    return ($result);
}

/**
 * Returns table with tariff change pricing
 * 
 * @param array $tc_tariffsallowed
 * @param string $us_currency
 * @param string $user_tariff
 * @param float $tc_priceup
 * @param float $tc_pricedown
 * @param float $tc_pricesimilar
 * @return string
 */
function zbs_TariffGetShowPrices($tc_tariffsallowed, $us_currency, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar) {
    $allprices = zbs_TariffGetAllPrices();
    $allcosts = zbs_TariffGetChangePrice($tc_tariffsallowed, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar);

    $cells = la_TableCell(__('Tariff'));
    $cells.= la_TableCell(__('Monthly fee'));
    $cells.= la_TableCell(__('Cost of change'));
    $rows = la_TableRow($cells, 'row1');

    if (!empty($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $eachtariff) {
            $cells = la_TableCell(__($eachtariff));
            $cells.= la_TableCell(@$allprices[$eachtariff] . ' ' . $us_currency);
            $cells.= la_TableCell(@$allcosts[$eachtariff] . ' ' . $us_currency);
            $rows.= la_TableRow($cells, 'row2');
        }
    }
    $result = la_TableBody($rows, '100%', 0);
    return ($result);
}

/**
 * Returns complete tariff moving form
 * 
 * @param string $login
 * @param array  $tc_tariffsallowed
 * @param float  $tc_priceup
 * @param float  $tc_pricedown
 * @param float  $tc_pricesimilar
 * @param string $us_currency
 * @return string
 */
function zbs_TariffChangeForm($login, $tc_tariffsallowed, $tc_priceup, $tc_pricedown, $tc_pricesimilar, $us_currency) {
    global $us_config;
    $user_tariff = zbs_UserGetTariff($login);
    $alltariffs = zbs_TariffGetAllPrices();
    $form = '
        ' . __('Your current tariff is') . ': ' . __($user_tariff) . ' ' . __('with monthly fee') . ' ' . $alltariffs[$user_tariff] . ' ' . $us_currency . '<br>
        ' . __('The cost of switching to a lower rate monthly fee') . ': ' . $tc_pricedown . ' ' . $us_currency . '<br>
        ' . __('The cost of switching to a higher monthly fee tariff') . ': ' . $tc_priceup . ' ' . $us_currency . '<br>
        ' . __('The cost of the transition rate for the same monthly fee') . ': ' . $tc_pricesimilar . ' ' . $us_currency . '<br>
        ' . la_tag('br') . '
        ' . zbs_TariffGetShowPrices($tc_tariffsallowed, $us_currency, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar) . '
        ' . la_tag('br') . '
        ';


    $inputs = __('New tariff') . ' ' . zbs_TariffSelector($tc_tariffsallowed, $user_tariff) . la_delimiter();
    $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
    $inputs.= la_delimiter();

    $nmChangeFlag = true;
    if (isset($us_config['TC_RIGHTNOW'])) {
        if ($us_config['TC_RIGHTNOW']) {
            $nmChangeFlag = false;
        }
    }

    $sumbitLabel = ($nmChangeFlag) ? __('I want this tariff next month') : __('I want this tariff right now');

    $inputs.= la_Submit($sumbitLabel);


    $form.= la_Form('', 'POST', $inputs, '');

    return ($form);
}

//check is tariff changing is enabled? 
if ($tc_enabled) {
    //check is TC allowed for current user tariff plan
    if (in_array($user_tariff, $tc_tariffenabledfrom)) {
        //tariff change subroutines
        if (isset($_POST['newtariff'])) {
            $change_prices = zbs_TariffGetChangePrice($tc_tariffsallowed, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar);
            if (in_array($_POST['newtariff'], $tc_tariffsallowed)) {
                // agreement check
                if (isset($_POST['agree'])) {

                    // and not enought money, set credit
                    if ($user_cash < $change_prices[$_POST['newtariff']]) {
                        //if TC_CREDIT option enabled
                        if ($tc_credit) {

                            $newcredit = $change_prices[$_POST['newtariff']] + $user_credit;
                            billing_setcredit($user_login, $newcredit);

                            // check for current credit expirity - added in 0.5.7
                            // without this check this conflicts with SC_ module
                            if (!$user_credit_expire) {
                                //set credit expire date for month from this moment
                                $timestamp = time();
                                $monthOffset = $timestamp + 2678400; // 31 days in seconds
                                $creditend = date("Y-m-d", $monthOffset);
                                billing_setcreditexpire($user_login, $creditend);
                            }
                        }
                    }

                    //TC change fee anyway
                    zbs_PaymentLog($user_login, '-' . $change_prices[$_POST['newtariff']], $tc_cashtypeid, "TCHANGE:" . $_POST['newtariff']);
                    billing_addcash($user_login, '-' . $change_prices[$_POST['newtariff']]);

                    //nm set tariff routine
                    $nextMonthTc = true;
                    if (isset($us_config['TC_RIGHTNOW'])) {
                        if ($us_config['TC_RIGHTNOW']) {
                            $nextMonthTc = false;
                        }
                    }
                    if ($nextMonthTc) {
                        billing_settariffnm($user_login, loginDB_real_escape_string($_POST['newtariff']));
                        log_register('CHANGE TariffNM (' . $user_login . ') ON `' . $_POST['newtariff'] . '`');
                    } else {
                        billing_settariff($user_login, loginDB_real_escape_string($_POST['newtariff']));
                        log_register('CHANGE Tariff (' . $user_login . ') ON `' . $_POST['newtariff'] . '`');
                    }



                    rcms_redirect("index.php");
                } else {
                    // agreement check fail
                    show_window(__('Sorry'), __('You must accept our policy'));
                }
                //die if tariff not allowed  
            } else {
                die("oO");
            }
        } // end of tariff change subroutine (POST processing)

        if (empty($user_tariffnm)) {
            show_window(__('Tariff change'), zbs_TariffChangeForm($user_login, $tc_tariffsallowed, $tc_priceup, $tc_pricedown, $tc_pricesimilar, $us_currency));
        } else {
            show_window(__('Sorry'), __('You already have planned tariff change'));
        }
    } else {
        show_window(__('Sorry'), __('Your current tariff does not allow tariff change on your own'));
    }
    //end of TC enabled check
// or not enabled at all?
} else {
    show_window(__('Sorry'), __('Unfortunately self tariff change is disabled'));
}
?>
