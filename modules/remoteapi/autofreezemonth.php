<?php

/*
 * auto freezing call which use AUTOFREEZE_CASH_LIMIT as month count
 */
if (ubRouting::get('action') == 'autofreezemonth') {
    if (isset($alterconf['AUTOFREEZE_CASH_LIMIT'])) {
        $tariffPrices = zb_TariffGetPricesAll();
        $tariffPriceMultiplier = abs($alterconf['AUTOFREEZE_CASH_LIMIT']);
        $autoFreezeQuery = "SELECT * from `users` WHERE `Passive`='0' AND `Credit`='0';";
        $allUsersToFreeze = simple_queryall($autoFreezeQuery);
        $freezeCount = 0;
        //optional zbs SC check
        if (ubRouting::checkGet('param')) {
            if (ubRouting::get('param') == 'nocredit') {
                $creditZbsCheck = true;
                $creditZbsUsers = zb_CreditLogGetAll();
            } else {
                $creditZbsCheck = false;
                $creditZbsUsers = array();
            }
        } else {
            $creditZbsCheck = false;
            $creditZbsUsers = array();
        }
        if (!empty($allUsersToFreeze)) {
            foreach ($allUsersToFreeze as $efuidx => $eachfreezeuser) {
                $freezeLogin = $eachfreezeuser['login'];
                $freezeCash = $eachfreezeuser['Cash'];
                $freezeUserTariff = $eachfreezeuser['Tariff'];
                if (isset($tariffPrices[$freezeUserTariff])) {
                    $freezeUserTariffPrice = $tariffPrices[$freezeUserTariff];
                    $tariffFreezeLimit = '-' . ($freezeUserTariffPrice * $tariffPriceMultiplier);
                    if (($freezeCash <= $tariffFreezeLimit) and ($freezeUserTariffPrice != 0)) {
                        //zbs credit check  
                        if ($creditZbsCheck) {
                            if (!isset($creditZbsUsers[$freezeLogin])) {
                                $billing->setpassive($freezeLogin, '1');
                                log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                                $freezeCount++;
                            } else {
                                log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash . ' SKIP BY ZBSSC');
                            }
                        } else {
                            //normal freezing     
                            $billing->setpassive($freezeLogin, '1');
                            log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                            $freezeCount++;
                        }
                    }
                }
            }
            log_register('AUTOFREEZE DONE COUNT `' . $freezeCount . '`');
            die('OK:AUTOFREEZE');
        } else {
            die('OK:NO_USERS_TO_AUTOFREEZE');
        }
    } else {
        die('ERROR:NO_AUTOFREEZE_CASH_LIMIT');
    }
}
