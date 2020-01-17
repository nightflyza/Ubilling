<?php

/**
 * Ubilling remote API for Asterisk and other CRM
 * -----------------------------
 * 
 * Format: /?module=remoteapi&key=[ubserial]&action=[action]&number=[+380XXXXXXXXX]&param=[parameter]
 * 
 * Avaible parameter: login, swstatus, userstatus, setcredit, paycardpay
 *
 * With "userstatus" param you may use pretty self explanationary "ignorecache" and "getmoney" params as well
 * With "setcredit" param you'll need to pass "login", "money" and "expiredays" params as well
 * With "paycardpay" param you'll need to pass "login", "paycardnum", "paycardcashtype" param as well
 * 
 */
if ($_GET['action'] == 'asterisk') {
    if ($alterconf['ASTERISK_ENABLED']) {
        if (wf_CheckGet(array('number'))) {
            if (wf_CheckGet(array('param'))) {
                $ignoreCache = wf_CheckGet(array('ignorecache'));
                $getMoney = wf_CheckGet(array('getmoney'));
                $userLogin = (wf_CheckGet(array('login'))) ? $_GET['login'] : '';
                $creditMoney = (wf_CheckGet(array('money'))) ? $_GET['money'] : 0.00;
                $creditExpireDays = (wf_CheckGet(array('expiredays'))) ? $_GET['expiredays'] : 0;
                $payCardNum = (wf_CheckGet(array('paycardnum'))) ? $_GET['paycardnum'] : '';
                $payCardCashType = (wf_CheckGet(array('paycardcashtype'))) ? $_GET['paycardcashtype'] : 1;
                $number = trim($_GET['number']);

                $userdata = (empty($userLogin)) ? array() : zb_ProfileGetStgData($userLogin);

                $asterisk = new Asterisk();
                $askNum = new AskoziaNum();
                $askNum->setNumber($number);

                switch ($_GET['param']) {
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
                                        global $billing;
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

                            global $billing;
                            $billing->setpassive($userLogin, 1);

                            log_register('ASTERISK SET PAUSE SUCCESSFUL FOR  ' . $userLogin);
                            die('ASTERISK SET PAUSE SUCCESSFUL FOR  ' . $userLogin);
                        }


                    case 'userstatus':
                        $askNum->renderReply(false, $ignoreCache, $getMoney);
                        // no break or die() needed - previous line will call die() itself


                    default:
                        $askNum->renderReply(true, $ignoreCache, $getMoney);

                        $result = $asterisk->AsteriskGetInfoApi($number, $_GET['param']);
                        die($result);
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

            
