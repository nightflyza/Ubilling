<?php

/**
 * Userstats core integration layer
 *
 * /?module=remoteapi&key=SERIAL&action=uscore&param=OP&login=LOGIN&value=VALUE
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
 * 
 * @return string
 */
if (ubRouting::get('action') == 'uscore') {
    $reply = '';
    if (ubRouting::checkGet(array('param', 'login')) and ubRouting::checkGet('value',false)) {
        $userLogin = ubRouting::get('login');
        $operation = ubRouting::get('param');
        $opValue = ubRouting::get('value');
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
            'setao' => 1
        );

        if (isset($allowedOps[$operation])) {
            $allUsers = zb_UserGetAllDataCache();
            if (isset($allUsers[$userLogin])) {
                if ($operation == 'addcash') {
                    if (zb_checkMoney($opValue)) {
                        $billing->addcash($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:DIRTY_MONEY';
                    }
                }

                if ($operation == 'setcash') {
                    if (zb_checkMoney($opValue)) {
                        $billing->setcash($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:DIRTY_MONEY';
                    }
                }

                if ($operation == 'setcredit') {
                    if (zb_checkMoney($opValue)) {
                        $billing->setcredit($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:DIRTY_MONEY';
                    }
                }

                if ($operation == 'setcreditexpire') {
                    if ($opValue != '') {
                        $billing->setcreditexpire($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:PARAMS_MISSED';
                    }
                }

                if ($operation == 'settariff') {
                    if ($opValue != '') {
                        $billing->settariff($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:PARAMS_MISSED';
                    }
                }

                if ($operation == 'settariffnm') {
                    if ($opValue != '') {
                        $billing->settariffnm($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:PARAMS_MISSED';
                    }
                }

                if ($operation == 'setpassive') {
                    $billing->setpassive($userLogin, $opValue);
                }

                if ($operation == 'setpassword') {
                    if ($opValue != '') {
                        $billing->setpassword($userLogin, $opValue);
                    } else {
                        $reply = 'ERROR:PARAMS_MISSED';
                    }
                }

                if ($operation == 'setdown') {
                    $billing->setdown($userLogin, $opValue);
                }

                if ($operation == 'setao') {
                    $billing->setao($userLogin, $opValue);
                }

                if (empty($reply)) {
                    if ($operation == 'setpassword') {
                        log_register('REMOTEAPI USCORE setpassword (' . $userLogin . ')');
                    } else {
                        log_register('REMOTEAPI USCORE ' . $operation . ' (' . $userLogin . ') ON `' . $opValue . '`');
                    }
                    $reply = 'OK:USCORE';
                }
            } else {
                $reply = 'ERROR:WRONG_LOGIN';
            }
        } else {
            $reply = 'ERROR:UNKNOWN_OP';
        }
    } else {
        $reply = 'ERROR:PARAMS_MISSED';
    }
    die($reply);
}
