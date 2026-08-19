<?php

/**
 * Userstats core integration layer
 *
 * /?module=remoteapi&key=SERIAL&action=uscore&operation=OP&login=LOGIN&value=VALUE
 *
 * Available operations:
 * - addcash - add cash to user
 * - setcash - set cash to user
 * - setcredit - set credit to user
 * - setcreditexpire - set credit expire date
 * - settariff - set tariff to user
 * - settariffnm - set tariff name to user
 * - setpassive - set passive status to user
 * - setpassword - set password to user
 * - setdown - set down status to user
 * - setao - set AO status to user
 * - reset - reset user session
 * - setmac - change user MAC address
 * - userauthcheck - check user login and password
 * - isalivecheck - remoteapi liveness check
 *
 * Reply JSON:
 * {"operation":"...","login":"...","value":"...","error":false,"error_message":""}
 *
 * @return void
 */

if (ubRouting::get('action') == 'uscore') {
    $error = false;
    $errorMessage = '';
    $userLogin = ubRouting::get('login','login');
    $operation = ubRouting::get('operation','gigasafe');
    $opValue = ubRouting::get('value','safe');

    if (ubRouting::checkGet('operation')) {
        $allowedOps = array(
            'addcash' => 1,
            'setcash' => 1,
            'setcredit' => 1,
            'setcreditexpire' => 1,
            'settariff' => 1,
            'settariffnm' => 1,
            'setpassive' => 1,
            'setpassword' => 1,
            'setdown' => 1,
            'setao' => 1,
            'reset' => 1,
            'setmac' => 1,
            'userauthcheck' => 1,
            'isalivecheck' => 1
        );

        if (isset($allowedOps[$operation])) {
            if ($operation == 'isalivecheck') {
                $error = false;
                $errorMessage = '';
            } else {
                if (ubRouting::checkGet('login') and ubRouting::checkGet('value', false)) {
                    $allUsers = zb_UserGetAllStargazerDataAssoc();
                    if (isset($allUsers[$userLogin])) {
                        if ($operation == 'addcash') {
                            if (zb_checkMoney($opValue)) {
                                $billing->addcash($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'DIRTY_MONEY';
                            }
                        }

                        if ($operation == 'setcash') {
                            if (zb_checkMoney($opValue)) {
                                $billing->setcash($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'DIRTY_MONEY';
                            }
                        }

                        if ($operation == 'setcredit') {
                            if (zb_checkMoney($opValue)) {
                                $billing->setcredit($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'DIRTY_MONEY';
                            }
                        }

                        if ($operation == 'setcreditexpire') {
                            if ($opValue != '') {
                                $billing->setcreditexpire($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'PARAMS_MISSED';
                            }
                        }

                        if ($operation == 'settariff') {
                            if ($opValue != '') {
                                $billing->settariff($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'PARAMS_MISSED';
                            }
                        }

                        if ($operation == 'settariffnm') {
                            if ($opValue != '') {
                                $billing->settariffnm($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'PARAMS_MISSED';
                            }
                        }

                        if ($operation == 'setpassive') {
                            $billing->setpassive($userLogin, $opValue);
                        }

                        if ($operation == 'setpassword') {
                            if ($opValue != '') {
                                $billing->setpassword($userLogin, $opValue);
                            } else {
                                $error = true;
                                $errorMessage = 'PARAMS_MISSED';
                            }
                        }

                        if ($operation == 'setdown') {
                            $billing->setdown($userLogin, $opValue);
                        }

                        if ($operation == 'setao') {
                            $billing->setao($userLogin, $opValue);
                        }

                        if ($operation == 'reset') {
                            uscoreDoReset($userLogin);
                        }

                        if ($operation == 'setmac') {
                            $macError = uscoreDoSetMac($userLogin, $opValue);
                            if ($macError != '') {
                                $error = true;
                                $errorMessage = $macError;
                            }
                        }

                        if ($operation == 'userauthcheck') {
                            if ($opValue != '') {
                                if ($allUsers[$userLogin]['Password'] != $opValue) {
                                    $error = true;
                                    $errorMessage = 'WRONG_CREDENTIALS';
                                }
                            } else {
                                $error = true;
                                $errorMessage = 'PARAMS_MISSED';
                            }
                        }

                        if (!$error) {
                            if ($operation == 'setpassword') {
                                log_register('USCORE SETPASSWORD (' . $userLogin . ')');
                            } else {
                                if (($operation != 'reset') and ($operation != 'setmac') and ($operation != 'userauthcheck')) {
                                    log_register('USCORE ' . strtoupper($operation) . ' (' . $userLogin . ') ON `' . $opValue . '`');
                                }
                            }
                        }
                    } else {
                        $error = true;
                        $errorMessage = 'WRONG_LOGIN';
                    }
                } else {
                    $error = true;
                    $errorMessage = 'PARAMS_MISSED';
                }
            }
        } else {
            $error = true;
            $errorMessage = 'UNKNOWN_OPERATION';
        }
    } else {
        $error = true;
        $errorMessage = 'PARAMS_MISSED';
    }

    uscoreReply($operation, $userLogin, $opValue, $error, $errorMessage);
}
