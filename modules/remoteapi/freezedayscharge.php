<?php

/*
 * running freeze days charge if FREEZE_DAYS_CHARGE_ENABLED
 */

if (ubRouting::checkGet('action') and ubRouting::get('action') == 'freezedayscharge') {
    if (isset($alterconf['FREEZE_DAYS_CHARGE_ENABLED']) && $alterconf['FREEZE_DAYS_CHARGE_ENABLED']) {
        $processingDebug = (ubRouting::checkGet('param') and ubRouting::get('param') == 'debug2ublog');
        $verboseDebug = ubRouting::checkGet('verbosedebug');

        $freezeDaysInitAmnt = $alterconf['FREEZE_DAYS_INITIAL_AMOUNT'];
        $wrkDaysToRestoreFrzDaysInitAmnt = $alterconf['FREEZE_DAYS_WORK_TO_RESTORE'];

        class users extends NyanORM { }
        class frozen_charge_days extends NyanORM { }

        $tabUsers = new users();
        $tabFrozenChargeDays = new frozen_charge_days();

        $tabUsers->setDebug($processingDebug, $verboseDebug);
        $tabFrozenChargeDays->setDebug($processingDebug, $verboseDebug);

        $tabUsers->join('LEFT', 'frozen_charge_days', 'login');
        $tabUsers->where('users.Passive', '=', '1');
        $tabUsers->where('frozen_charge_days.login', 'IS', 'NULL');
        $tabUsers->selectable('users.login');
        $tabUsers->orderBy('id');
        $allFrozenNotInCountTab = $tabUsers->getAll();

        if (!empty($allFrozenNotInCountTab)) {
            foreach ($allFrozenNotInCountTab as $usr => $eachlogin) {
                $exceptionRaised = false;
                $field_value = array('login' => $eachlogin['login'],
                                     'freeze_days_amount' => $freezeDaysInitAmnt,
                                     'work_days_restore' => $wrkDaysToRestoreFrzDaysInitAmnt
                                    );
                $tabFrozenChargeDays->dataArr($field_value);

                try {
                    $tabFrozenChargeDays->create();
                } catch (Exception $e) {
                    $exceptionRaised = true;
                    log_register('FREEZE DAYS CHARGE: error - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
                }

                if ($processingDebug and !$exceptionRaised) {
                    log_register('FREEZE DAYS CHARGE: login `' . $eachlogin['login'] . '` added to freeze days charge processing table');
                }
            }
        }

        $tabFrozenChargeDays->join('LEFT', 'users', 'login');
        $tabFrozenChargeDays->selectable(array('`frozen_charge_days`.*', '`users`.`Passive`', '`users`.`Down`', '`users`.`Credit`', '`users`.`Cash`'));
        $tabFrozenChargeDays->orderBy('id');
        $frozenAll = $tabFrozenChargeDays->getAll();

        if (!empty($frozenAll)) {
            $usrPassive = 0;
            $frozenProcessed = 0;

            log_register('FREEZE DAYS CHARGE: frozen users to process charge total amount [' . count($frozenAll) . ']');

            foreach ($frozenAll as $usr => $eachlogin) {
                $exceptionRaised = false;
                $usrLogin = $eachlogin['login'];
                $usrPassive = $eachlogin['Passive'];
                $usrDown = $eachlogin['Down'];
                $usrCredit = $eachlogin['Credit'];
                $usrCash = $eachlogin['Cash'];

                $frzDaysAmount = $eachlogin['freeze_days_amount'];
                $frzDaysUsed = $eachlogin['freeze_days_used'];
                $daysWorked = $eachlogin['days_worked'];
                $wrkDaysToRestoreFrzDays = $eachlogin['work_days_restore'];

                if ($usrPassive) {
                    $frzDaysUsedIncr = $frzDaysUsed + 1;
                    $frozenProcessed++;

                    if ($frzDaysUsed >= $frzDaysAmount) {
                        $billing->setpassive($usrLogin, '0');
                    }

                    $tabFrozenChargeDays->data('freeze_days_used', $frzDaysUsedIncr);
                    $tabFrozenChargeDays->data('last_freeze_charge_dt', curdatetime());
                    $tabFrozenChargeDays->where('login', '=', $usrLogin);

                    try {
                        $tabFrozenChargeDays->save(true, true);
                    } catch (Exception $e) {
                        $exceptionRaised = true;
                        log_register('FREEZE DAYS CHARGE: error - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
                    }

                    if ($processingDebug and !$exceptionRaised) {
                        log_register('FREEZE DAYS CHARGE: used freeze days amount increased from [' . $frzDaysUsed . '] to [' . $frzDaysUsedIncr . '] for login `' . $usrLogin . '`');
                    }
                } elseif ($frzDaysUsed >= $frzDaysAmount && $usrCash > '-' . $usrCredit && !$usrDown) {
                    $daysWorkedIncr = $daysWorked + 1;
                    $frozenProcessed++;

                    if ($daysWorkedIncr >= $wrkDaysToRestoreFrzDays) {
                        $daysWorkedIncr = 0;
                        $frzDaysUsed = 0;

                        $tabFrozenChargeDays->data('freeze_days_used', $frzDaysUsed);

                        if ($processingDebug) {
                            log_register('FREEZE DAYS CHARGE: used freeze days and worked days amount reset to 0, service freezing activated for login `' . $usrLogin . '`');
                        }
                    }

                    $tabFrozenChargeDays->data('days_worked', $daysWorkedIncr);
                    $tabFrozenChargeDays->data('last_workdays_upd_dt', curdatetime());
                    $tabFrozenChargeDays->where('login', '=', $usrLogin);

                    try {
                        $tabFrozenChargeDays->save(true, true);
                    } catch (Exception $e) {
                        $exceptionRaised = true;
                        log_register('FREEZE DAYS CHARGE: error - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
                    }

                    if ($processingDebug and !$exceptionRaised) {
                        log_register('FREEZE DAYS CHARGE: worked days amount increased from [' . $daysWorked . '] to [' . $daysWorkedIncr . '] for login `' . $usrLogin . '`');
                    }
                } elseif ($processingDebug and $verboseDebug) {
                    log_register('FREEZE DAYS CHARGE: skipping processing for login `' . $usrLogin . '`');
                }
            }

            log_register('FREEZE DAYS CHARGE: processed frozen users amount [' . $frozenProcessed . ']');
            die('OK:FREEZE_DAYS_CHARGE');
        } else {
            die('OK:FREEZE_DAYS_CHARGE_NO_USERS');
        }
    }
}