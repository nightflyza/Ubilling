<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

/**
 * Returns total cost of all additional services. Needs SC_VSCREDIT=1 in userstats.ini
 * 
 * @param string $login user's login
 * @param array $tariffData
 *
 * @return float|int for all virtual services if such exists for user
 */
function zbs_VServicesGetPrice($login, $tariffData) {
    $us_config = zbs_LoadConfig();
    $price = 0;

    if (isset($us_config['SC_VSCREDIT'])) {
        if ($us_config['SC_VSCREDIT']) {
            $vservicesPeriodON = (!empty($us_config['VSERVICES_CONSIDER_PERIODS']));
            $price = ($vservicesPeriodON) ? zbs_vservicesGetUserPricePeriod($login, $tariffData['Fee']) : zbs_vservicesGetUserPrice($login);
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
    $inputs .= la_HiddenInput('setcredit', 'true');
    $inputs .= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
    $inputs .= la_delimiter();
    $inputs .= la_Submit(__('Set me credit please'));
    $inputs .= la_tag('center', true);
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
 * Checks if user use SC module without previous payment and returns false if used or true if feature available
 * 
 * @param  string $login existing users login
 * 
 * @return bool
 */
function zbs_CreditLogCheckHack($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT `note` FROM `payments` WHERE `login` = '" . $login . "' AND (`summ` > 0 OR `note` = 'SCFEE') ORDER BY `payments`.`date` DESC LIMIT 1";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } elseif (!empty($data) AND $data['note'] != 'SCFEE') {
        return (true);
    } else {
        return (false);
    }
}

/**
 * checks is user current month use SC module and returns false if used or true if feature available
 * 
 * @param  string $login existing users login
 * 
 * @return bool
 */
function zbs_CreditLogCheckMonth($login) {
    global $us_config;
    $login = mysql_real_escape_string($login);
    $pattern = date("Y-m");
    $query = "SELECT `id` from `zbssclog` WHERE `login` LIKE '" . $login . "' AND `date` LIKE '" . $pattern . "%';";

    //power tariffs specific handling
    if ($us_config['POWERTARIFFS_ENABLED']) {
        $userFeeOffset = zbs_GetPowerTariffDay($login);
        //implict power user detection
        if ($userFeeOffset) {
            $lowDay = date("Y-m-d", strtotime("-1 month", time()));
            $maxDay = date("Y-m-d");
            //once per month?
            $query = "SELECT `id` from `zbssclog` WHERE `login` LIKE '" . $login . "' AND `date`  BETWEEN '" . $lowDay . "' AND '" . $maxDay . "';";
        }
    }

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
 * @global array $us_config
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
    global $us_config;
    $creditLimit = $tariffprice + $sc_price;
    $remoteFlag = false;
    if (isset($us_config['SC_REMOTE'])) {
        if ($us_config['SC_REMOTE']) {
            $remoteFlag = true;
        }
    }

    if (!$remoteFlag) {
        //default sgconf routines
        billing_setcredit($user_login, $creditLimit);
        billing_setcreditexpire($user_login, $scend);
        zbs_PaymentLog($user_login, '-' . $sc_price, $sc_cashtypeid, "SCFEE");
        billing_addcash($user_login, '-' . $sc_price);
    } else {
        //remote API callback
        $remoteApiRequest = '&action=sc&login=' . $user_login . '&cr=' . $creditLimit . '&end=' . $scend . '&fee=' . $sc_price . '&ct=' . $sc_cashtypeid;
        $remoteResult = zbs_remoteApiRequest($remoteApiRequest);
    }

    zbs_CreditLogPush($user_login);
    log_register('CHANGE Credit (' . $user_login . ') ON ' . $creditLimit);
    show_window('', __('Now you have a credit'));

    if (isset($us_config['SC_MTAPI_FIX'])) {
        if ($us_config['SC_MTAPI_FIX']) {
            //Reset via Down flag
            if ($us_config['SC_MTAPI_FIX'] == 1) {
                executor('-u ' . $user_login . ' -d 1');
                executor('-u ' . $user_login . ' -d 0');
            }
            //Reset via AO flag
            if ($us_config['SC_MTAPI_FIX'] == 2) {
                executor('-u ' . $user_login . ' --always-online 0');
                executor('-u ' . $user_login . ' --always-online 1');
            }
        }
    }
}

//Agent actions begins here
if (ubRouting::checkGet('agentcredit')) {
    $scAgentResult = array();
    $agentWasHere = true;
    $agentOutputFormat = 'xml';
    if (ubRouting::checkGet('json')) {
        $agentOutputFormat = 'json';
    }
} else {
    $agentWasHere = false;
}

// if SC enabled
if ($us_config['SC_ENABLED']) {

// let needed params
    $userData = zbs_UserGetStargazerData($user_login);
    $current_cash = $userData['Cash'];
    $current_credit = $userData['Credit'];
    $current_credit_expire = $userData['CreditExpire'];
    $tariff = $userData['Tariff'];
    $frozenFlag = $userData['Passive'];
    $downFlag = $userData['Down'];

    $us_currency = $us_config['currency'];
    $sc_minday = $us_config['SC_MINDAY'];
    $sc_maxday = $us_config['SC_MAXDAY'];
    $sc_term = $us_config['SC_TERM'];
    $sc_price = $us_config['SC_PRICE'];
    $sc_cashtypeid = $us_config['SC_CASHTYPEID'];
    $sc_monthcontrol = $us_config['SC_MONTHCONTROL'];
    $sc_hackhcontrol = (isset($us_config['SC_HACKCONTROL']) AND !empty($us_config['SC_HACKCONTROL'])) ? true : false;
    $sc_allowed = array();
    $creditResultLabel = '';

    //getting some tariff data
    $tariffData = zbs_UserGetTariffData($tariff);
    if (empty($tariffData)) {
        //user have no tariff
        $tariffData['name'] = '*_NO_TARIFF_*';
        $tariffData['Fee'] = 0;
        $tariffData['period'] = 'month';
    }

    //power tariffs basic support
    if ($tariffData['Fee'] == 0) {
        if ($us_config['POWERTARIFFS_ENABLED']) {
            $tariffData['Fee'] = zbs_GetPowerTariffPrice($tariff);
        }
    }

    $vs_price = zbs_VServicesGetPrice($user_login, $tariffData);

//allowed tariffs option
    if (isset($us_config['SC_TARIFFSALLOWED'])) {
        if (!empty($us_config['SC_TARIFFSALLOWED'])) {
            $sc_allowed = explode(',', $us_config['SC_TARIFFSALLOWED']);
            $sc_allowed = array_flip($sc_allowed);
        }
    }

    $tariffFee = $tariffData['Fee'];

    if (isset($tariffData['period'])) {
        $tariffPeriod = $tariffData['period'];
    } else {
        $tariffPeriod = 'month'; //older stargazer releases
    }

    $tariffprice = $tariffFee; //default for month/spread tariffs

    if (!@$us_config['SC_DAILY_FIX']) {
        if ($tariffPeriod == 'day') {
            $tariffprice = $tariffFee * date("t"); // now this is price for whole month
        }
    }

    $tariffprice += $vs_price; //appending virtual services price

    if (isset($us_config['SC_DAILY_FIX'])) {
        if ($us_config['SC_DAILY_FIX']) {
            $tariffprice = abs($current_cash) + ($tariffprice * $us_config['SC_TERM']);
        }
    }


    $cday = date("d");

//welcome message
    $wmess = __('If you wait too long to pay for the service, here you can get credit for') . ' ' . $sc_term . ' ' . __('days. The price of this service is') . ': ' . $sc_price . ' ' . $us_currency . '. ';
    if (isset($us_config['SC_VSCREDIT'])) {
        if ($us_config['SC_VSCREDIT']) {
            $wmess .= __('Also you promise to pay for the current month, in accordance with your service plan') . ".";
        } else {
            $wmess .= __('Also you promise to pay for the current month, in accordance with your service plan') . ". " . __('Additional services are not subject to credit') . ".";
        }
    }
    show_window(__('Credits'), $wmess);

//if day is something like that needed
    if (($cday <= $sc_maxday) AND ( $cday >= $sc_minday)) {
        if (($current_credit <= 0) AND ( $current_credit_expire == 0)) {
            //ok, no credit now
            $performCredit = false; //just form
            $agreementCheck = false; //agreement accept
            $justCheckPossibility = false; //possibility check for agentcredit
            //check web forms
            if (ubRouting::checkPost('setcredit')) {
                $performCredit = true;
                if (ubRouting::checkPost('agree')) {
                    $agreementCheck = true;
                }
            }

            //XML agent callback?
            if (ubRouting::checkGet('agentcredit')) {
                $performCredit = true;
                $agreementCheck = true;
                $justCheckPossibility = (ubRouting::checkGet('justcheck')) ? true : false;
            }

            // allow user to set it if its required
            if (!$performCredit) {
                show_window('', zbs_ShowCreditForm());
            } else {
                // set credit routine
                if ($agreementCheck) {
                    //calculate credit expire date
                    $nowTimestamp = time();
                    $creditSeconds = ($sc_term * 86400); //days*secs
                    $creditOffset = $nowTimestamp + $creditSeconds;
                    $scend = date("Y-m-d", $creditOffset);
                    if ((!$frozenFlag) AND (!$downFlag)) {
                        if (abs($current_cash) <= $tariffprice) {
                            if ($current_cash < 0) {
                                if (zbs_CreditCheckAllowed($sc_allowed, $tariff)) {
                                    //additional hack contol enabled
                                    if ($sc_hackhcontrol AND !zbs_CreditLogCheckHack($user_login)) {
                                        $creditResultLabel = __('You can not take out a credit because you have not paid since the previous time');
                                        show_window(__('Sorry'), $creditResultLabel);
                                        $scAgentResult = array();
                                        $scAgentResult[] = array('status' => 10);
                                        $scAgentResult[] = array('message' => 'not paid previous');
                                    } else {
                                        //additional month contol enabled
                                        if ($sc_monthcontrol) {
                                            if (zbs_CreditLogCheckMonth($user_login)) {
                                                //check for allow option
                                                if (!$justCheckPossibility) {
                                                    zbs_CreditDoTheCredit($user_login, $tariffprice, $sc_price, $scend, $sc_cashtypeid);
                                                }
                                                $scAgentResult = array();
                                                $scAgentResult[] = array('status' => 0);
                                                $scAgentResult[] = array('message' => 'success');

                                                $scAgentResult[] = array('fullmessage' => $creditResultLabel);
                                                $scAgentResult[] = array('minday' => $sc_minday);
                                                $scAgentResult[] = array('maxday' => $sc_maxday);
                                                $scAgentResult[] = array('creditterm' => $sc_maxday);
                                                $scAgentResult[] = array('creditprice' => $sc_price);
                                                $scAgentResult[] = array('currency' => $us_currency);
                                                $scAgentResult[] = array('creditintro' => $wmess);
                                                //XMLAgent callback after success
                                                if (ubRouting::checkGet('agentcredit')) {
                                                    zbs_XMLAgentRender($scAgentResult, 'data', '', $agentOutputFormat, false);
                                                }
                                                rcms_redirect("index.php");
                                            } else {
                                                $creditResultLabel = __('You already used credit feature in current month. Only one usage per month is allowed.');
                                                show_window(__('Sorry'), $creditResultLabel);
                                                $scAgentResult = array();
                                                $scAgentResult[] = array('status' => 9);
                                                $scAgentResult[] = array('message' => 'already used in this month');
                                            }
                                        } else {
                                            if (!$justCheckPossibility) {
                                                zbs_CreditDoTheCredit($user_login, $tariffprice, $sc_price, $scend, $sc_cashtypeid);
                                            }
                                            $scAgentResult = array();
                                            $scAgentResult[] = array('status' => 0);
                                            $scAgentResult[] = array('message' => 'success');

                                            $scAgentResult[] = array('fullmessage' => $creditResultLabel);
                                            $scAgentResult[] = array('minday' => $sc_minday);
                                            $scAgentResult[] = array('maxday' => $sc_maxday);
                                            $scAgentResult[] = array('creditterm' => $sc_maxday);
                                            $scAgentResult[] = array('creditprice' => $sc_price);
                                            $scAgentResult[] = array('currency' => $us_currency);
                                            $scAgentResult[] = array('creditintro' => $wmess);
                                            //XMLAgent callback after success
                                            if (ubRouting::checkGet('agentcredit')) {
                                                zbs_XMLAgentRender($scAgentResult, 'data', '', $agentOutputFormat, false);
                                            }
                                            rcms_redirect("index.php");
                                        }
                                        //end of self credit main code
                                    }
                                } else {
                                    $creditResultLabel = __('This feature is not allowed on your tariff');
                                    show_window(__('Sorry'), $creditResultLabel);
                                    $scAgentResult = array();
                                    $scAgentResult[] = array('status' => 8);
                                    $scAgentResult[] = array('message' => 'not allowed on this tariff');
                                }
                            } else {
                                //to many money
                                $creditResultLabel = __('Sorry, sum of money in the account is enought to use service without credit');
                                show_window(__('Sorry'), $creditResultLabel);
                                $scAgentResult = array();
                                $scAgentResult[] = array('status' => 5);
                                $scAgentResult[] = array('message' => 'too much money');
                            }
                        } else {
                            //not allowed to use self credit
                            if ($current_cash < 0) {
                                $creditResultLabel = __('Sorry, your debt does not allow to continue working in the credit');
                                show_window(__('Sorry'), $creditResultLabel);
                                $scAgentResult = array();
                                $scAgentResult[] = array('status' => 6);
                                $scAgentResult[] = array('message' => 'not enough money');
                            } else {
                                $creditResultLabel = __('Sorry, sum of money in the account is enought to use service without credit');
                                show_window(__('Sorry'), $creditResultLabel);
                                $scAgentResult = array();
                                $scAgentResult[] = array('status' => 5);
                                $scAgentResult[] = array('message' => 'too much money');
                            }
                        }
                    } else {
                        $creditResultLabel = __('Your account has been frozen');
                        show_window(__('Sorry'), $creditResultLabel);
                        $scAgentResult = array();
                        $scAgentResult[] = array('status' => 4);
                        $scAgentResult[] = array('message' => 'account frozen');
                    }
                } else {
                    // agreement check
                    $creditResultLabel = __('You must accept our policy');
                    show_window(__('Sorry'), $creditResultLabel);
                    $scAgentResult = array();
                    $scAgentResult[] = array('status' => 7);
                    $scAgentResult[] = array('message' => 'unexpected error');
                }
            }
        } else {
            //you alredy have it 
            $creditResultLabel = __('You already have a credit');
            show_window(__('Sorry'), $creditResultLabel);
            $scAgentResult = array();
            $scAgentResult[] = array('status' => 3);
            $scAgentResult[] = array('message' => 'already have a credit');
        }
    } else {
        $creditResultLabel = __('You can take a credit only between') . ' ' . $sc_minday . __(' and ') . $sc_maxday . ' ' . __('days of the month');
        show_window(__('Sorry'), $creditResultLabel);
        $scAgentResult = array();
        $scAgentResult[] = array('status' => 2);
        $scAgentResult[] = array('message' => 'wrong day');
    }

//append some required data on cases when something not good
    $scAgentResult[] = array('fullmessage' => $creditResultLabel);
    $scAgentResult[] = array('minday' => $sc_minday);
    $scAgentResult[] = array('maxday' => $sc_maxday);
    $scAgentResult[] = array('creditterm' => $sc_term);
    $scAgentResult[] = array('creditprice' => $sc_price);
    $scAgentResult[] = array('currency' => $us_currency);
    $scAgentResult[] = array('creditintro' => $wmess);

//and if disabled :(
} else {
    $scAgentResult = array();
    $scAgentResult[] = array('status' => 1);
    $scAgentResult[] = array('message' => 'disabled');
    show_window(__('Sorry'), __('Unfortunately self credits is disabled'));
}

//XMLAgent callback
if (ubRouting::checkGet('agentcredit')) {
    zbs_XMLAgentRender($scAgentResult, 'data', '', $agentOutputFormat, false);
}
?>
