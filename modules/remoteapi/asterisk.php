<?php

/**
 * Ubilling remote API for Asterisk and other CRM
 * -----------------------------
 * 
 * Format: /?module=remoteapi&key=[ubserial]&action=[action]&number=[+380XXXXXXXXX]&param=[parameter]
 * 
 * Available parameter: login, swstatus, userstatus, setcredit, paycardpay,
 *                      getuserdatabylogin, getuserdatabymobile, getcontractsbymobile, addusermobile
 *
 * With "userstatus" param you may use pretty self explanationary "ignorecache" and "getmoney" params as well
 * With "setcredit" param you'll need to pass "login", "money" and "expiredays" params as well
 * With "paycardpay" param you'll need to pass "login", "paycardnum", "paycardcashtype" param as well
 * With "getuserdatabylogin" param you may pass "userpass" param as well to enable user + password verification
 * With "addusermobile" param you'll need to pass "login" param also. Optional "maxmobilesamnt" param can be passed to determine
 *      the max mobiles count threshold per user.
 * "getuserdatabymobile" and "getcontractsbymobile" need no additional parameters except the mobile passed in "number" param
 * With "getvservicescount" param you may use "number" or "login" params to search user with one of those.
 *      Returns number of virtual services assigned to a certain user.
 * With "getonlinedaysleft" param you may use "number" or "login" params to search user with one of those.
 *      You may use "includevsrvs" param as well to involve virtual services cost into "online days left" calculations.
 *      Returns number of "online days left" for a certain user.
 * With "getuserspends" param you may use "number" or "login" params to search user with one of those.
 *      You may use "includevsrvs" param as well to get all virtual services in addition to main tariff.
 *      Returns info about user's tariff and it's cost and, optionally, similar info about user's virtual services.
 *          Format: array(TarrifName => array('price' => TariffCost, 'daysperiod' => TariffChargePeriod),
 *                        Vservice1 => array('price' => Vservice1Cost, 'daysperiod' => Vservice1ChargePeriod),
 *                        Vservice2 => array('price' => Vservice2Cost, 'daysperiod' => Vservice2ChargePeriod),
 *                        VserviceN => array('price' => VserviceNCost, 'daysperiod' => VserviceChargePeriod)
 *                       )
 *          Tip: "TarrifName => TariffCost" - is always the first element
 */

if ($_GET['action'] == 'asterisk') {
    if ($alterconf['ASTERISK_ENABLED']) {
        if (ubRouting::checkGet('number') or ubRouting::checkGet('login')) {
            if (ubRouting::checkGet('param')) {
                $ignoreCache = ubRouting::checkGet('ignorecache');
                $getMoney = ubRouting::checkGet('getmoney');
                $addMobile = ubRouting::checkGet('addmobile');
                $includeVservices = (ubRouting::checkGet('includevsrvs') ? true : $ubillingConfig->getAlterParam('FUNDSFLOW_CONSIDER_VSERVICES'));
                $maxMobilesAmount = (ubRouting::checkGet('maxmobilesamnt')) ? ubRouting::get('maxmobilesamnt') : 0;
                $userLogin = (ubRouting::checkGet('login')) ? ubRouting::get('login') : '';
                $userPasswd = (ubRouting::checkGet('userpass')) ? ubRouting::get('userpass') : '';
                $creditMoney = (ubRouting::checkGet('money')) ? ubRouting::get('money') : 0.00;
                $creditExpireDays = (ubRouting::checkGet('expiredays')) ? ubRouting::get('expiredays') : 0;
                $payCardNum = (ubRouting::checkGet('paycardnum')) ? ubRouting::get('paycardnum') : '';
                $payCardCashType = (ubRouting::checkGet('paycardcashtype')) ? ubRouting::get('paycardcashtype') : 1;
                $number = trim(ubRouting::get('number'));
                $apiParam = ubRouting::get('param');

                $userdata = (empty($userLogin)) ? array() : zb_ProfileGetStgData($userLogin);

                $asterisk = new Asterisk();
                // We do not need this data in the modules: callshist, ForWhomTheBellTolls
                if ($apiParam == 'swstatus') {
                    $result = $asterisk->AsteriskGetInfoApi($number, 'swstatus');
                    die($result);
                } else {
                    global $billing;
                    $askNum = new AskoziaNum();
                    $askNum->setNumber($number);

                    switch ($apiParam) {
                        case 'setcredit':
                            if (!empty($userdata)) {
                                if (isset($userdata['Cash']) and $userdata['Cash'] < 0) {
                                    $creditCheckEnabled = (isset($alterconf['ASTERISK_SC_CHECK_ENABLED']) and $alterconf['ASTERISK_SC_CHECK_ENABLED']);

                                    if ($creditCheckEnabled) {
                                        $asterSCAllowedTariffs = '';
                                        $userTariff = $userdata['Tariff'];

                                        if (!zb_CreditLogCheckHack($userLogin)) {
                                            log_register('ASTERISK CREDIT GET TRY (' . $userLogin . '): NOT PAYED PREVIOUSLY');
                                            die('ASTERISK CREDIT NOT AVAILABLE: NOT PAYED PREVIOUSLY');
                                        }

                                        if (!zb_CreditLogCheckMonth($userLogin)) {
                                            log_register('ASTERISK CREDIT GET TRY (' . $userLogin . '): ALREADY TOOK');
                                            die('ASTERISK CREDIT NOT AVAILABLE: ALREADY TOOK');
                                        }

                                        if (isset($alterconf['ASTERISK_SC_TARIFFSALLOWED']) and !empty($alterconf['ASTERISK_SC_TARIFFSALLOWED'])) {
                                            $asterSCAllowedTariffs = explode(',', $ubillingConfig->getAlterParam('ASTERISK_SC_TARIFFSALLOWED'));
                                            $asterSCAllowedTariffs = array_flip($asterSCAllowedTariffs);

                                            if (!zb_CreditCheckAllowed($asterSCAllowedTariffs, $userTariff)) {
                                                log_register('ASTERISK CREDIT GET TRY (' . $userLogin . '): NOT ALLOWED FOR TARIFF  ' . $userTariff);
                                                die('ASTERISK CREDIT NOT AVAILABLE: NOT ALLOWED FOR TARIFF');
                                            }
                                        }
                                    }

                                    if ($userdata['Cash'] > -$creditMoney) {
                                        if (curdate() < date("Y-m-d", $userdata['CreditExpire'])) {
                                            log_register('ASTERISK CREDIT GET TRY (' . $userLogin . '): CREDIT IS CURRENTLY ACTIVE');
                                            die('ASTERISK CREDIT NOT AVAILABLE: CREDIT IS CURRENTLY ACTIVE');
                                        } else {
                                            //set credit
                                            $billing->setcredit($userLogin, $creditMoney);
                                            log_register('ASTERISK CHANGE Credit (' . $userLogin . ') ON ' . $creditMoney);
                                            //set credit expire date
                                            $creditExpire = date('Y-m-d', strtotime("+" . $creditExpireDays . " days"));
                                            $billing->setcreditexpire($userLogin, $creditExpire);

                                            if ($creditCheckEnabled) {
                                                zb_CreditLogPush($userLogin);
                                            }

                                            log_register('ASTERISK CHANGE CreditExpire (' . $userLogin . ') ON ' . $creditExpire);
                                            die('ASTERISK CREDIT SET SUCCESSFULY');
                                        }
                                    } else {
                                        log_register('ASTERISK CREDIT TRY (' . $userLogin . '): BALANCE LOWER THAN CREDIT LIMIT');
                                        die('ASTERISK CREDIT NOT AVAILABLE: BALANCE LOWER THAN CREDIT LIMIT');
                                    }
                                } else {
                                    log_register('ASTERISK CREDIT NOT SET (' . $userLogin . '): CASH > 0 OR NOT SET');
                                    die('ASTERISK CREDIT NOT SET: CASH > 0 OR NOT SET');
                                }
                            } else {
                                log_register('ASTERISK CREDIT NOT SET: EMPTY USERDATA');
                                die('ASTERISK CREDIT NOT SET: EMPTY USERDATA');
                            }


                        case 'paycardpay':
                            if (empty($payCardNum)) {
                                log_register('ASTERISK PAYCARD NUMBER IS EMPTY');
                                die('ASTERISK PAYCARD NUMBER IS EMPTY');
                            }

                            if (!empty($userdata)) {
                                $user_ip = $userdata['IP'];
                                $ctime = curdatetime();
                                $payCardNum = vf($payCardNum);
                                $query = "SELECT `id` from `cardbank` WHERE `serial`='" . $payCardNum . "' AND `active`='1' AND `used`='0' AND `usedlogin` = ''";
                                $cardcheck = simple_query($query);

                                if (empty($cardcheck)) {
                                    $query = "INSERT INTO `cardbrute` (`id` , `serial` , `date` , `login` , `ip` )
                                                           VALUES (NULL , '" . $payCardNum . "', '" . $ctime . "', '" . $userLogin . "', '" . $user_ip . "');";
                                    nr_query($query);

                                    log_register('ASTERISK PAYCARD NOT EXISTS');
                                    die('ASTERISK PAYCARD NOT EXISTS');
                                } else {
                                    // mark paycard as used
                                    $query = "SELECT * from `cardbank` WHERE `serial`='" . $payCardNum . "'";
                                    $carddata = simple_query($query);
                                    $cardcash = $carddata['cash'];

                                    $carduse_q = "UPDATE `cardbank` SET
                                                `usedlogin` = '" . $userLogin . "',
                                                `usedip` = '" . $user_ip . "',
                                                `usedate`= '" . $ctime . "',
                                                `used`='1'
                                                 WHERE `serial` ='" . $payCardNum . "';
                                                ";
                                    nr_query($carduse_q);

                                    // add some cash to user balance
                                    billing_addcash($userLogin, $cardcash);

                                    // write card payment to payments log
                                    $cashtypeid = vf($payCardCashType);
                                    $userdata = zb_ProfileGetStgData($userLogin);
                                    $balance = $userdata['Cash'];
                                    $note = mysql_real_escape_string("CARD:" . $payCardNum);
                                    $query = "INSERT INTO `payments` (`id` , `login` , `date` , `admin` , `balance` , `summ` , `cashtypeid` , `note` )
                                                          VALUES (NULL , '" . $userLogin . "', '" . $ctime . "', 'external', '" . $balance . "', '" . $cardcash . "', '" . $cashtypeid . "', '" . $note . "'); ";
                                    nr_query($query);

                                    log_register('ASTERISK PAYCARD PAYMENT SUCCESSFUL');
                                    die('ASTERISK PAYCARD PAYMENT SUCCESSFUL');
                                }
                            } else {
                                log_register('ASTERISK PAYCARD PAYMENT UNSUCCESSFUL: EMPTY USERDATA');
                                die('ASTERISK PAYCARD PAYMENT UNSUCCESSFUL: EMPTY USERDATA');
                            }


                        case 'setpause':
                            if ($userdata['Passive']) {
                                log_register('ASTERISK SET PAUSE UNSUCCESSFUL: PAUSE IS CURRENTLY ACTIVE');
                                die('ASTERISK SET PAUSE UNSUCCESSFUL: PAUSE IS CURRENTLY ACTIVE');
                            } else {
                                if (isset($alterconf['FREEZE_DAYS_CHARGE_ENABLED']) and $alterconf['FREEZE_DAYS_CHARGE_ENABLED']) {
                                    $frozenDataQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $userLogin . "';";
                                    $frozenData = simple_queryall($frozenDataQuery);

                                    if (!empty($frozenData)) {
                                        $frzDaysAmount = $frozenData[0]['freeze_days_amount'];
                                        $frzDaysUsed = $frozenData[0]['freeze_days_used'];

                                        if ($frzDaysUsed >= $frzDaysAmount) {
                                            log_register('ASTERISK SET PAUSE UNSUCCESSFUL: NO AVAILABLE FREEZE DAYS LEFT');
                                            die('ASTERISK SET PAUSE UNSUCCESSFUL: NO AVAILABLE FREEZE DAYS LEFT');
                                        }
                                    }
                                }

                                $billing->setpassive($userLogin, 1);

                                log_register('ASTERISK SET PAUSE SUCCESSFUL FOR  ' . $userLogin);
                                die('ASTERISK SET PAUSE SUCCESSFUL FOR  ' . $userLogin);
                            }


                        case 'setunpause':
                            if ($userdata['Passive']) {
                                $billing->setpassive($userLogin, 0);

                                log_register('ASTERISK UNPAUSE SUCCESSFUL FOR  ' . $userLogin . ' FROM MOBILE: ' . $number);
                                die('ASTERISK UNPAUSE SUCCESSFUL FOR  ' . $userLogin . ' FROM MOBILE: ' . $number);
                            } else {
                                log_register('ASTERISK UNPAUSE UNSUCCESSFUL FOR  ' . $userLogin . ' FROM MOBILE: ' . $number . ': PAUSE IS NOT ACTIVE');
                                die('ASTERISK UNPAUSE UNSUCCESSFUL FOR  ' . $userLogin . ' FROM MOBILE: ' . $number . ': PAUSE IS NOT ACTIVE');
                            }


                        case 'userstatus':
                            $askNum->renderReply(false, $ignoreCache, $getMoney);
                            // no break or die() needed - previous line will call die() itself

                        case 'getuserdatabylogin':
                            $result = $asterisk->getUserData($userLogin, $userPasswd, true);
                            die($result);

                        case 'getuserdatabymobile':
                            $logins = $asterisk->getLoginsByMobile($number, false);
                            $result = $asterisk->getUserData($logins, '', true);
                            die($result);

                        case 'getcontractsbymobile':
                            $result = $asterisk->getContractsByMobile($number);
                            die($result);

                        case 'addusermobile':
                            $result = $asterisk->addUserMobile($userLogin, $number, $maxMobilesAmount);
                            die($result);

                        case 'getonlinedaysleft':
                        case 'getvservicescount':
                        case 'getuserspends':
                        case 'getcontragentdata':
                            if (empty($userLogin) and !empty($number)) {
                                $logins = $asterisk->getLoginsByMobile($number, false);

                                if (!empty($logins)) {
                                    $userLogin = $logins[0];
                                }
                            }

                            if ($apiParam == 'getonlinedaysleft') {
                                $ff = new FundsFlow();
                                $ff->runDataLoders();
                                $onlineDaysLeft = $ff->getOnlineLeftCountFast($userLogin, $includeVservices);
                                die("$onlineDaysLeft");
                            }

                            if ($apiParam == 'getcontragentdata') {
                                $contragent = zb_AgentAssignedGetData($userLogin);
                                die(json_encode($contragent));
                            }

                            $userVsrvs = zb_VservicesGetUsersAll($userLogin, true, true);
                            $userVsrvs = empty($userVsrvs[$userLogin]) ? array() : $userVsrvs[$userLogin];

                            if ($apiParam == 'getvservicescount') {
                                $userVsrvsCnt = (empty($userVsrvs) ? 0 : count($userVsrvs));
                                die("$userVsrvsCnt");
                            }

                            $userSpends = array();
                            $userData   = $asterisk->getUserData($userLogin, '', false, false);
                            $userData   = empty($userData[$userLogin]) ? array() : $userData[$userLogin];

                            if (!empty($userData)) {
                                $userSpends[$userData['Tariff']] = array('price' => $userData['Fee'], 'daysperiod' => $userData['period']);
                            }

                            if (!empty($userVsrvs)) {
                                foreach ($userVsrvs as $eachID => $eachSrv) {
                                    $vsrvName = $eachSrv['vsrvname'];
                                    $vsrvTmpArr = array('price' => $eachSrv['price'], 'daysperiod' => $eachSrv['daysperiod']);
                                    $userSpends[$vsrvName] = $vsrvTmpArr;
                                }
                            }

                            die(json_encode($userSpends));


                        default:
                            $askNum->renderReply(true, $ignoreCache, $getMoney);

                            $result = $asterisk->AsteriskGetInfoApi($number, $_GET['param']);
                            die($result);
                    }
                }
            } else {
                die('ERROR: NOT HAVE PARAMETR');
            }
        } else {
            die('ERROR: NOT HAVE NUMBER');
        }
    } else {
        die('ERROR: ASTERISK DISABLED');
    }
}

            
