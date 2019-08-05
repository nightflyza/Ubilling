<?php

/*
 * running freeze days charge if FREEZE_DAYS_CHARGE_ENABLED
 */
if ($_GET['action'] == 'freezedayscharge') {
    if (isset($alterconf['FREEZE_DAYS_CHARGE_ENABLED']) && $alterconf['FREEZE_DAYS_CHARGE_ENABLED']) {
        $FreezeDaysInitAmnt = $alterconf['FREEZE_DAYS_INITIAL_AMOUNT'];
        $WrkDaysToRestoreFrzDaysInitAmnt = $alterconf['FREEZE_DAYS_WORK_TO_RESTORE'];

        $TmpQuery = "SELECT `users`.`login` FROM `users` 
                                              LEFT JOIN `frozen_charge_days` ON `frozen_charge_days`.`login` = `users`.`login`
                                            WHERE `users`.`Passive`='1' AND `frozen_charge_days`.`login` IS NULL;";
        $AllFrozenNotInCountTab = simple_queryall($TmpQuery);

        if (!empty($AllFrozenNotInCountTab)) {
            foreach ($AllFrozenNotInCountTab as $usr => $eachlogin) {
                $TmpQuery = "INSERT INTO `frozen_charge_days` (`login`, `freeze_days_amount`, `work_days_restore`)
                                                              VALUES  ('" . $eachlogin['login'] . "', '" . $FreezeDaysInitAmnt . "', '" . $WrkDaysToRestoreFrzDaysInitAmnt . "');";
                nr_query($TmpQuery);
            }
        }

        $FrozenAllQuery = "SELECT `frozen_charge_days`.*, `users`.`Passive`, `users`.`Down`, `users`.`Credit`, `users`.`Cash`
                                                  FROM `frozen_charge_days`
                                                    LEFT JOIN `users` ON `frozen_charge_days`.`login` = `users`.`login`;";
        $FrozenAll = simple_queryall($FrozenAllQuery);

        if (!empty($FrozenAll)) {
            $UsrPassive = 0;
            $FrozenToPocess = count($FrozenAll);

            foreach ($FrozenAll as $usr => $eachlogin) {
                $UsrLogin = $eachlogin['login'];
                $UsrPassive = $eachlogin['Passive'];
                $UsrDown = $eachlogin['Down'];
                $UsrCredit = $eachlogin['Credit'];
                $UsrCash = $eachlogin['Cash'];

                $FrzDaysAmount = $eachlogin['freeze_days_amount'];
                $FrzDaysUsed = $eachlogin['freeze_days_used'];
                $DaysWorked = $eachlogin['days_worked'];
                $WrkDaysToRestoreFrzDays = $eachlogin['work_days_restore'];

                if ($UsrPassive) {
                    $FrzDaysUsed++;

                    if ($FrzDaysUsed >= $FrzDaysAmount) {
                        $billing->setpassive($UsrLogin, '0');
                    }

                    simple_update_field('frozen_charge_days', 'freeze_days_used', $FrzDaysUsed, "WHERE `login`='" . $UsrLogin . "' ");
                } else {
                    if (($FrzDaysUsed >= $FrzDaysAmount) && ($UsrCash < '-' . $UsrCredit) && !$UsrDown) {
                        $DaysWorked++;

                        if ($DaysWorked >= $WrkDaysToRestoreFrzDays) {
                            $DaysWorked = 0;
                            $FrzDaysUsed = 0;

                            simple_update_field('frozen_charge_days', 'freeze_days_used', $FrzDaysUsed, "WHERE `login`='" . $UsrLogin . "' ");
                        }

                        simple_update_field('frozen_charge_days', 'days_worked', $DaysWorked, "WHERE `login`='" . $UsrLogin . "' ");
                    }
                }
            }

            log_register('FREEZE DAYS CHARGE done to `' . $FrozenToPocess . '` users');
            die('OK:FREEZE_DAYS_CHARGE');
        } else {
            die('OK:FREEZE_DAYS_CHARGE_NO_USERS');
        }
    }
}