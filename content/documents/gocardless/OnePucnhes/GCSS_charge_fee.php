<?php
require ("content/documents/gocardless/lib/loader.php");
require ("content/documents/gocardless/guzzle/autoloader.php");

$gcssConf               = parse_ini_file('content/documents/gocardless/config/gcss.ini');
$paysys                 = 'GOCARDLESS';
$debugModeON            = true;
$testModeNoCharge       = false;
$noCreditCheckOrSet     = false;
$loginToCharge          = '';
$creditAsFullFee        = true;
$chargePeriodsMapping   = array('day' => 1, 'week' => 7, 'month' => date('t'));

$viaRAPI = ubRouting::checkGet('rapi');
if ($viaRAPI) {
    $debugModeON        = ubRouting::checkGet('debugison');
    $testModeNoCharge   = ubRouting::checkGet('testmodeison');
    $noCreditCheckOrSet = ubRouting::checkGet('nocreditcheckset');
    $loginToCharge      = ubRouting::checkGet('login');
    $creditAsFullFee    = ubRouting::checkGet('creditasfullfee');
}

$testModeSignStr = empty($testModeNoCharge) ? '' : ' RUNS IN TEST MODE: ';

function getAllChargeParams($login = '') {
    $allChargeParams = array();
    $whereLogin = empty($login) ? '' : " `login` = '" . $login . "' AND ";

    $query = "SELECT * FROM `gcss_charges` WHERE " . $whereLogin . " `mandate_canceled` = 0";
    $qResult = simple_queryall($query);

    if (!empty($qResult)) {
        foreach ($qResult as $eachRec) {
            $allChargeParams[$eachRec['login']] = $eachRec;
        }
    }

    return ($allChargeParams);
}

function getUserInvoiceFromDB($login, $monthNum) {
    $invoice = '';

    $query = "SELECT * FROM `invoices` WHERE `login` = '" . $login . "' and MONTH(`invoice_date`) = '" . $monthNum . "'";
    $qResult = simple_query($query);

    if (!empty($qResult)) {
        $invoice = $qResult;
    }

    return ($invoice);
}

function runQuery($query, $testMode = false, $testMsg = '') {
    if ($testMode) {
        log_register('GOCARDLESS RUNS IN TEST MODE: ' . $testMsg);
    } else {
        nr_query($query);
    }
}

function createInvoice($login) {
    $receiptsPrinter = new PrintReceipt();
    $configFileData = $receiptsPrinter->getSettingsFromConfig(ubRouting::checkGet('rcptconfigpath'));

    // possible values:     'inetsrv' / 'ctvsrv'
    $receiptServiceType = trim($configFileData['SERVICE_TYPE']);
    // possible values:     'advance' / 'postpaid'
    $receiptChargeMode = trim($configFileData['CHARGE_MODE']);
    $receiptChargeStarts1st = wf_getBoolFromVar($configFileData['CHARGE_PERIOD_STARTS_ON_1ST']);
    // values, e.g.:    __('Internet') / __('Cable television')
    $receiptServiceName = trim($configFileData['SERVICE_NAME']);
    // possible values:     'debt' / 'debtasbalance' / 'undebt' / 'all'
    $receiptUsersStatus = trim($configFileData['USERS_STATUS']);
    // money amount under which user's cosidered to be a debtor
    $receiptDebtCash = $configFileData['DEBT_CASH_AMOUNT'];
    $receiptUsrTagID = $configFileData['USER_HAS_TAGID'];
    $receiptFrozenStatus = trim($configFileData['USER_FROZEN_STATUS_IS']);
    $receiptTariffID = trim($configFileData['USER_HAS_TARIFF']);
    $receiptTemplate = $configFileData['CUSTOM_TEMPLATE_FOLDER'];
    $receiptSaveToDB = wf_getBoolFromVar($configFileData['SAVE_TO_DB']);
    //E-mailing settings
    $receiptSendEmail = wf_getBoolFromVar($configFileData['SEND_EMAIL']);
    // possible values:     'built-in' / 'phpmail'
    $receiptEmailEngine = (empty($configFileData['EMAIL_ENGINE'])) ? 'built-in' : trim($configFileData['EMAIL_ENGINE']);
    //Virtual services inclusion settings
    $receiptVsrvsInclude = wf_getBoolFromVar($configFileData['INCLUDE_VSERVICES']);
    //Number of days to be considered as "month" to calculate monthly cost of daily charged virtual services
    $receiptVsrvsDIM = (empty($configFileData['VSERVICES_DAYS_IN_MONTH'])) ? 30 : $configFileData['VSERVICES_DAYS_IN_MONTH'];
    //Default charge period of certain virtual service, if not specified
    $receiptVsrvsDCP = (empty($configFileData['VSERVICES_DEFAULT_CHARGE_PERIOD'])) ? 'month' : trim($configFileData['VSERVICES_DEFAULT_CHARGE_PERIOD']);
    $receiptSaveToFile = (empty($configFileData['SAVE_ALL_TO_FILE_DIR'])) ? '' : trim($configFileData['SAVE_ALL_TO_FILE_DIR']);
    $receiptMonthsCnt = (empty($configFileData['PAY_FOR_MONTHS_COUNT'])) ? 1 : $configFileData['PAY_FOR_MONTHS_COUNT'];
    $receiptPayTillDate = (empty($configFileData['PAY_TILL_DATE'])) ? 'nextmonth' : trim($configFileData['PAY_TILL_DATE']);
    $receiptPayForPeriod = (empty($configFileData['PAY_FOR_PERIOD'])) ? 'curmonth' : trim($configFileData['PAY_FOR_PERIOD']);
    $partialChargeOn = (empty($configFileData['PARTIAL_CHARGE_ON'])) ? 0 : $configFileData['PARTIAL_CHARGE_ON'];
    $partialChargeStartDate = (empty($configFileData['PARTIAL_CHARGE_START_DATE_IS'])) ? 'contractdate' : trim($configFileData['PARTIAL_CHARGE_START_DATE_IS']);
    $partialChargeMode = (empty($configFileData['PARTIAL_CHARGE_MODE'])) ? 'partial_full' : trim($configFileData['PARTIAL_CHARGE_MODE']);
    $partialChargePrintMode = (empty($configFileData['PARTIAL_CHARGE_PRINT_MODE'])) ? 'print_separate' : trim($configFileData['PARTIAL_CHARGE_PRINT_MODE']);

    $usersPrintData = $receiptsPrinter->getUsersPrintData($receiptServiceType, $receiptUsersStatus, $login, $receiptDebtCash,
                                                        '', '', '', $receiptUsrTagID,
                                                          $receiptTariffID, $receiptFrozenStatus);

    if (!empty($usersPrintData)) {
        $receipt = $receiptsPrinter->printReceipts($usersPrintData, $receiptServiceType, $receiptPayTillDate, $receiptMonthsCnt,
                                                       $receiptPayForPeriod, $receiptVsrvsInclude, $receiptVsrvsDIM, $receiptVsrvsDCP,
                                                       $receiptSaveToDB, $receiptSendEmail, $receiptEmailEngine,
                                                       $partialChargeOn, $partialChargeStartDate, $partialChargeMode, $partialChargePrintMode,
                                                       $receiptChargeMode, $receiptChargeStarts1st, $receiptServiceName, $receiptTemplate
                                                      );
    }

    return ($receipt);
}

$client = new \GoCardlessPro\Client(array(
        'access_token' => $gcssConf['GCSS_API_TOKEN'],
        'environment' => \GoCardlessPro\Environment::LIVE
    )
);

if ($debugModeON) {
    log_register($paysys . ' charge process STARTED ' . $testModeSignStr);
}

$allChargeParams = getAllChargeParams($loginToCharge);
$allUsersTM = array();
$whereLogin = empty($loginToCharge) ? '' : " `users`.`login` = '" . $loginToCharge . "' AND ";

$query = "SELECT `users`.`login`, `users`.`Tariff`, `users`.`Cash`, `users`.`Credit`, 
                 IF(`users`.`CreditExpire` = 0, '', FROM_UNIXTIME(`users`.`CreditExpire`, '%Y-%m-%d')) AS `CreditExpireDate`,  
                 `tariffs`.`Fee`, `gcss_mandates`.`mandate_id`, `contracts`.`contract`, 
                 `phones`.`phone`, `phones`.`mobile`, `emails`.`email` 
            FROM `users` 
                INNER JOIN `gcss_mandates` ON `gcss_mandates`.`login` = `users`.`login` AND `gcss_mandates`.`canceled` <= 0
                LEFT JOIN `tariffs` ON `tariffs`.`name` = `users`.`Tariff`
                LEFT JOIN `contracts` ON `contracts`.`login` = `users`.`login`
                LEFT JOIN `phones` ON `phones`.`login` = `users`.`login` 
                LEFT JOIN `emails` ON `emails`.`login` = `users`.`login`                                 
            WHERE " . $whereLogin . " `users`.`Passive` = '0'";         //  and `users`.Cash < 0
$allUsersTM = simple_queryall($query);

if (empty($allUsersTM)) {
    log_register($paysys . ' no fee charge processing - empty mandates list');
} else {
    $stgDayFee              = (empty($gcssConf['GCSS_STG_DAYFEE'])) ? 1 : $gcssConf['GCSS_STG_DAYFEE'];
    $chargePeriodStartDay   = (empty($gcssConf['GCSS_CHARGE_PERIOD_START_DAY'])) ? 1 : $gcssConf['GCSS_CHARGE_PERIOD_START_DAY'];
    $becomesDebtorAfterDay  = (empty($gcssConf['GCSS_USER_BECOMES_DEBTOR_AFTER_DAY'])) ? 20 : $gcssConf['GCSS_USER_BECOMES_DEBTOR_AFTER_DAY'];
    $maxChargeAttemptsCnt   = (empty($gcssConf['GCSS_MAX_CHARGE_ATTEMPTS_COUNT'])) ? 10 : $gcssConf['GCSS_MAX_CHARGE_ATTEMPTS_COUNT'];
    $failChargePeriod       = (empty($gcssConf['GCSS_AFTER_FAIL_CHARGE_PERIOD'])) ? $chargePeriodsMapping['day'] : $chargePeriodsMapping[$gcssConf['GCSS_AFTER_FAIL_CHARGE_PERIOD']];

    switch ($failChargePeriod) {
        case 'week':
            $failChargePeriod = 7;
            break;

        case 'month':
            $failChargePeriod = date('t');
            break;

        default:
            $failChargePeriod = 1;
    }

    $failedReChargeDaysInterval = $gcssConf['GCSS_AFTER_FAIL_CHARGE_INTERVAL'] * $failChargePeriod;

    if (empty($chargePeriodStartDay)) {
        log_register($paysys . ' no fee charge action taken - empty charge start date');
        return (false);
    }

    $curDate                = curdate();
    $curDayOfMonth          = date('j');     // DOM without leading 0
    $curMonthNum            = date('n');     // month number without leading 0
    $curMonthChargeDate     = date('Y-m-') . $chargePeriodStartDay;
    $curMonthDaysCount      = date('t');
    $curMonthLastDay        = date('Y-m-') . $curMonthDaysCount;
    $nextChargeDate         = date('Y-m-d', strtotime($curDate . ' + ' . $failedReChargeDaysInterval . ' days'));   // get next charge day
    $todayIsStartChargeDay  = ($curDayOfMonth == $chargePeriodStartDay);
    $startChargeDayPassed   = ($curDayOfMonth > $chargePeriodStartDay);

    // nullify all succeeded charges from previous billing period if today is a beginning of new billing period
    if ($todayIsStartChargeDay) {
        $query = "UPDATE `gcss_charges` SET
                         `credit_sum_charged` = 0, 
                         `lst_paym_succeeded` = 0,
                         `attempt` = 0,
                         `warn_mail_send` = 0                        
                    WHERE `mandate_canceled` = 0 
                 ";
        runQuery($query, $testModeNoCharge. 'nullify all succeeded charges from previous billing period if today is a beginning of new billing period');
    }

    log_register($paysys . ' users amount to process [' . count($allUsersTM) . ']');

    foreach ($allUsersTM as $io => $eachRec) {
        $usrLogin = $eachRec['login'];
        $usrMandate = $eachRec['mandate_id'];
        $usrContract = $eachRec['contract'];
        $usrCash = $eachRec['Cash'];
        $usrCreditSum = $eachRec['Credit'];
        $usrCreditExp = $eachRec['CreditExpireDate'];
        $usrTariff = $eachRec['Tariff'];
        $usrTariffPrice = $eachRec['Fee'];
        $usrPhone = $eachRec['phone'];
        $usrMobile = $eachRec['mobile'];
        $usrEmail = $eachRec['email'];

        $usrIsActive = ($usrCash > '-' . $usrCreditSum);
        $usrNotChargedYet = false;

        log_register($paysys . ' STARTING processing for login (' . $usrLogin . ') with mandate [' . $usrMandate . ']');

        if (empty($usrTariffPrice) or empty($usrTariff) or $usrTariff == '*_NO_TARIFF_*') {
            log_register($paysys . ' no fee charge for login (' . $usrLogin . ')  - no tariff data or incorrect tariff data found');
            continue;
        }

        $usrChargeParams = (empty($allChargeParams[$usrLogin])) ? array() : $allChargeParams[$usrLogin];

        if (empty($usrChargeParams)) {
            $query = "INSERT INTO `gcss_charges` (`login`, `mandate_id`) 
                                          VALUES ('" . $usrLogin . "', '" . $usrMandate . "')";
            runQuery($query);

            $allChargeParams = getAllChargeParams($loginToCharge);
            $usrChargeParams = $allChargeParams[$usrLogin];
            $usrNotChargedYet = true;

            if ($debugModeON) {
                log_register($paysys . ' no charge record for login (' . $usrLogin . ') found. Added one with mandate [' . $usrMandate . '].');
            }
        }


        // if charge processing day has passed and for some reason user still has succeeded flag ON - remove it
        if (!empty($usrChargeParams['lst_paym_succeeded']) and $startChargeDayPassed) {
            $lastChargeMonth = (empty($usrChargeParams['last_charge_date'])) ? 0 : date('n', strtotime($usrChargeParams['last_charge_date']));
            $lastChargeDiff  = $curMonthNum - $lastChargeMonth;

            if ($lastChargeDiff != $curMonthNum and $lastChargeDiff >= 1
                and $usrChargeParams['debtor'] == 0 and $usrChargeParams['charge_failed'] == 0) {

                $query = "UPDATE `gcss_charges` SET 
                                 `credit_sum_charged` = 0,
                                 `lst_paym_succeeded` = 0,
                                 `attempt` = 0,
                                 `warn_mail_send` = 0
                            WHERE `mandate_canceled` = 0 and `login` = '". $usrLogin ."' 
                         ";
                runQuery($query, $testModeNoCharge, 'INDIVIDUALLY nullify all succeeded charges from previous billing period if today is a beginning of new billing period');
            }
        }

        // user is active now:
        // remove debt and failed flags if user has it but now is active and without debt
        // to be able to charge user again further
        if ($usrCash >= 0) {
            if ($usrChargeParams['debtor'] == 1 or $usrChargeParams['charge_failed'] == 1 or $usrChargeParams['lst_paym_failed'] == 1) {
                $query = "UPDATE `gcss_charges` SET
                             `lst_paym_failed` = 0,
                             `debtor` = 0,
                             `charge_failed` = 0   
                        WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "'
                     ";
                runQuery($query, $testModeNoCharge, 'user is active now - remove debt and failed flags if user has it but now is active and without debt to be able to charge user again further');
            }

            log_register($paysys . ' user has balance >= 0 and is active now - no need to charge');

            continue;
        }

        // skip user charge on some conditions:
        // if debtor
        // if last payment this month succeeded
        // if this month charge totally failed
        // if next charge date is future ahead yet
        if ($usrChargeParams['debtor'] == 1
            or $usrChargeParams['lst_paym_succeeded'] == 1
            or $usrChargeParams['charge_failed'] == 1
            or (!empty($usrChargeParams['next_charge_dt']) and $usrChargeParams['next_charge_dt'] > $curDate and !$usrNotChargedYet)
        ) {
            $reason = '';

            if ($debugModeON) {
                $reason.= ($usrChargeParams['debtor'] == 1) ? ' | user is debtor already ' : '';

                $reason.= ($usrChargeParams['lst_paym_succeeded'] == 1) ? ' | last user payment succeeded ' : '';

                $reason.= ($usrChargeParams['charge_failed'] == 1) ? ' | last user payment charge failed ' : '';

                $reason.= (!empty($usrChargeParams['next_charge_dt']) and $usrChargeParams['next_charge_dt'] > $curDate and !$usrNotChargedYet)
                    ? ' | next charge date try is ' . $usrChargeParams['next_charge_dt'] . ' and not came yet ' : '';

                log_register($paysys . ' skipping charge for login (' . $usrLogin . ') mandate [' . $usrMandate . ']. Reason: ' . $reason);
            }

            continue;
        }

        // if $userChargeParams['attempts'] + 1 > $maxChargeAttemptsCnt
        // or if $nextChargeDate >= $curMonthLastDay ($nextChargeDate is in a next month), so
        // - mark this charge - as failed, user - as debtor and don't touch it anymore
        if (($usrChargeParams['attempt'] + 1) > $maxChargeAttemptsCnt or $nextChargeDate >= $curMonthLastDay) {
            log_register($paysys . ' max charge attempts reached for login (' . $usrLogin . ') - marked as failed');
            $query = "UPDATE `gcss_charges` SET
                             `debtor` = 1,
                             `charge_failed` = 1                        
                        WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "'
                     ";
            runQuery($query, $testModeNoCharge, '$userChargeParams[\'attempts\'] + 1 > $maxChargeAttemptsCnt or $nextChargeDate is in a next month - mark this charge - as failed, user - as debtor and don\'t touch it anymore');

            $tStr = ($nextChargeDate >= $curMonthLastDay) ? 'next charge date is an a next month.' : 'max charge attempts reached.';

            if ($debugModeON) {
                log_register($paysys . ' skipping charge for login (' . $usrLogin . ') mandate [' . $usrMandate . '] because ' . $tStr);
            }

            continue;
        }

        // if today is a next_charge_date
        // or next_charge_date already passed
        // or last_charge_date >= current month charge date start ($curMonthChargeDate already passed and we made some charges already)
        // but the status of current charge is nor succeeded, nor failed
        // just move next_charge_date 1 day ahead and skip this user (e.g. to wait charge finish from GC)
        // moving 1 day ahead needed to be able to charge user again if payment try is unsuccessful or to skip, if payment succeeded
        if (    (($usrChargeParams['next_charge_date'] <= $curDate and $usrChargeParams['next_charge_date'] != '0000-00-00')
                or $usrChargeParams['last_charge_date'] >= $curMonthChargeDate)
            and $usrChargeParams['lst_paym_succeeded'] == 0
            and $usrChargeParams['lst_paym_failed'] == 0
            and !empty($usrChargeParams['last_payment_id'])) {

            $query = "UPDATE `gcss_charges` SET 
                             `next_charge_date` = '" . date('Y-m-d', strtotime($curDate . ' + 1 day')) . "'                        
                        WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "'
                     ";
            runQuery($query, $testModeNoCharge, 'just move next_charge_date 1 day ahead and skip this user (e.g. to wait charge finish from GC)');

            if ($debugModeON) {
                log_register($paysys . ' skipping charge for login (' . $usrLogin . ') mandate [' . $usrMandate . '] because previous charge is not finished yet.');
            }

            continue;
        }


        $usrInvoiceFromDB = getUserInvoiceFromDB($usrLogin, $curMonthNum);

        if (empty($usrInvoiceFromDB)) {
            log_register($paysys . ' no invoices fround for login (' . $usrLogin . ') for month [' . $curMonthNum . '].');

            if ($testModeNoCharge) {
                log_register($paysys . $testModeSignStr . ' new invoice created');
                $tmpInvoice = 'INVOICE';
            } else {
                $tmpInvoice = createInvoice($usrLogin);
            }

            if (empty($tmpInvoice)) {
                log_register($paysys . ' invoice generation error for login (' . $usrLogin . ') for month [' . $curMonthNum . '].');
            }
        }

        // get invoice number. it changes only for the first charge in a billing period
        if ($todayIsStartChargeDay or empty($usrChargeParams['last_payment_invoice'])) {
            if (empty($usrInvoiceFromDB)) {
                $usrInvoice = 'INV-' . $usrContract . '-' . date('Ymd');
            } else {
                $usrInvoice = 'INV-' . $usrContract . '-' . $usrInvoiceFromDB['invoice_num'];
            }
        } else {
            $usrInvoice = $usrChargeParams['last_payment_invoice'];
        }

        // getting fee charge amount
        $usrAllVServicesCost = 0;
        if (empty($usrInvoiceFromDB)) {
            $usrAllVServicesCost = zb_VservicesGetUserPrice($usrLogin);
            $usrFullFee = $usrTariffPrice + $usrAllVServicesCost + abs($usrCash);
        } else {
            $usrFullFee = $usrInvoiceFromDB['invoice_sum'];
        }

        if ($noCreditCheckOrSet) {
            log_register($paysys . ' __NO__ credit check or set parameter active. Login (' . $usrLogin . '). User is active [' . $usrIsActive . ']');
        } else {
            // as we run this routine everyday - we need to check if there is a need to
            // set credit till $becomesDebtorAfterDay if $todayIsStartChargeDay
            // or if $todayIsStartChargeDay passed but last_charge_date is < $todayIsStartChargeDay
            // which means that script didn't start on $todayIsStartChargeDay for some reason
            // but we still need to charge users this month
            $creditExpire = date('Y-m-') . $becomesDebtorAfterDay;

            if ($debugModeON) {
                log_register($paysys . ' before credit params: user credit expire [' . $usrCreditExp . '] current credit expire [' . $creditExpire . '] expire dates equal [' . ($usrCreditExp == $creditExpire) . '] user is active [' . $usrIsActive . ']');
            }

            if ($curDayOfMonth < $becomesDebtorAfterDay and $curDayOfMonth >= $stgDayFee      // $chargePeriodStartDay
                and $usrChargeParams['debtor'] == 0 and $usrChargeParams['charge_failed'] == 0
                and $usrCreditExp != $creditExpire) {        //or !$usrIsActive
                //and ($todayIsStartChargeDay or ($curDate > $curMonthChargeDate and $usrChargeParams['last_charge_date'] < $curMonthChargeDate))) {

                if ($creditAsFullFee) {
                    $creditAmountFull = $usrTariffPrice + $usrAllVServicesCost;
                } else {
                    $creditDaysCnt = $becomesDebtorAfterDay - $curDayOfMonth + 1;   // getting credit days count
                    $servicesDailyCost = empty($usrFullFee)
                                         ? round(($usrTariffPrice + $usrAllVServicesCost) / $curMonthDaysCount, 2)
                                         : round($usrFullFee / $curMonthDaysCount, 2);
                    $creditDaysCost = $servicesDailyCost * $creditDaysCnt;
                    $creditAmountFull = $creditDaysCost + abs($usrCash) + 1;
                }

                if ($debugModeON) {
                    log_register($paysys . ' credit params: user full fee [' . $usrFullFee . '] days count [' . $creditDaysCnt . '] daily cost [' . $servicesDailyCost . '] amount [' . $creditDaysCost . '] full amount [' . $creditAmountFull . ']');
                }

                if ($testModeNoCharge) {
                    log_register($paysys . $testModeSignStr . ' credit check for login (' . $usrLogin . ')');
                } else {
                    $billing->setcredit($usrLogin, $creditAmountFull);
                    $billing->setcreditexpire($usrLogin, $creditExpire);
                }

                if (empty($usrChargeParams['credit_sum_charged'])) {
                    zb_CashAdd($usrLogin, -$creditDaysCost, 'correct', 2, $paysys . ' credit before charge');
                    log_register($paysys . ' charged credit cost from balance for login (' . $usrLogin . '): amount [' . $creditDaysCost . ']');

                    $query = "UPDATE `gcss_charges` SET 
                                 `credit_sum_charged` = 1
                            WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "' 
                         ";
                    runQuery($query, $testModeNoCharge, '`credit_sum_charged` have been set');
                }

                log_register($paysys . ' setting credit for login (' . $usrLogin . '): amount [' . $creditDaysCost . '] expire on [' . $creditExpire . ']');
            }
        }

        // if $becomesDebtorAfterDay has passed and user still has no money and is still active - we need
        // to switch OFF the service for that user, regardless the Credit status
        if ($curDayOfMonth > $becomesDebtorAfterDay and $usrChargeParams['lst_paym_succeeded'] != 1 and $usrIsActive and !$noCreditCheckOrSet) {
            if ($testModeNoCharge) {
                log_register($paysys . $testModeSignStr . ' $becomesDebtorAfterDay has passed and user still has no money and is still active - switched OFF the service for that user, regardless the Credit status');
            } else {
                $billing->setcredit($usrLogin, 0);
                $billing->setcreditexpire($usrLogin, '');
                $billing->setdown($usrLogin, 1);
            }
            log_register($paysys . ' making service OFF for login (' . $usrLogin . ')');

            /*$query = "UPDATE `gcss_charges` SET
                             `debtor` = 1,
                             `next_charge_date` = " . date('Y-m-d', strtotime($curDate . ' + ' . failedReChargeDaysInterval . ' day')) . "
                        WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "'
                     ";
            runQuery($query);

            continue;*/
        }


        $datetime       = curdatetime();
        $idempotencyKey = 'GCSS_' . md5($usrInvoice . $datetime);
        //$chargeFailed   = ($usrChargeParams['attempt'] >= $maxChargeAttemptsCnt);     // not needed here

        if ($testModeNoCharge) {
            log_register($paysys . $testModeSignStr . ' payment charge successfully created');
        } else {
            $payment = $client->payments()->create(array(
                    "params" => array(
                        "amount" => $usrFullFee * 100, // sum in GBP in pence
                        "currency" => "GBP",
                        "description" => 'BeaconsTelecom Internet service access fee charge due to invoice #' . $usrInvoice,  // INV-{CONTRACT}-{INVOICE_NUM}
                        "links" => array(
                            // The mandate ID
                            "mandate" => $usrMandate
                        ),
                        // Almost all resources in the API let you store custom metadata,
                        // which you can retrieve later
                        "metadata" => array(
                            "invoice_number" => $usrInvoice
                        )
                    ),
                    "headers" => array(
                        "Idempotency-Key" => $idempotencyKey
                    )
                )
            );
        }

        $query = "UPDATE `gcss_charges` SET 
                         `attempt` = " . ($usrChargeParams['attempt'] + 1) . ",
                         `last_payment_id` = '" . $payment->id . "',
                         `last_payment_invoice` = '" . $usrInvoice . "',
                         `last_charge_date` = '" . $curDate . "',
                         `next_charge_date` = '" . $nextChargeDate . "',
                         `gcss_charge_date` = '" . $payment->charge_date . "',
                         `gcss_payout_date` = '" . date('Y-m-d', strtotime($payment->charge_date . ' + 2 days')) . "',
                         `lst_paym_succeeded` = 0,
                         `lst_paym_failed` = 0
                    WHERE `mandate_canceled` = 0 and `login` = '" . $usrLogin . "'
                 ";
        runQuery($query, $testModeNoCharge, 'user have been charged');

        $query = "INSERT INTO `op_transactions` (`id`,`hash`, `date` , `summ` , `customerid` ,`paysys` , `processed` ,`invoice`, `payment_id`)
                                         VALUES (NULL ,'" . $idempotencyKey . "' , '" . $datetime . "', '" . $usrFullFee . "', '" . $usrContract . "', '" . $paysys . "', '0', '" . $usrInvoice . "', '" . $payment->id .  "');";
        runQuery($query, $testModeNoCharge, 'OPAYZ transaction have been created');

        log_register($paysys . ' payment request: login (' . $usrLogin . ')  tafiff price [' . $usrTariffPrice . '],  vservices price [' . $usrAllVServicesCost . '],  fee charged sum [' . $usrFullFee . '],  ID [' . $payment->id . '] GC charge date: ' . $payment->charge_date);
    }

    if ($debugModeON) {
        log_register($paysys . ' charge process FINISHED ' . $testModeSignStr);
    }
}


// mandates - balance - invoices SQL query:
// select gcss_mandates.*, realname.realname, contracts.contract, invoices.id as inv_id, invoices.invoice_num, invoices.invoice_date, invoices.invoice_sum, users.cash from gcss_mandates left join realname using(login) left join contracts using(login) left join invoices using(login) left join users using(login) where canceled = 0
