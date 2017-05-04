<?php

/**
 * Фронтенд для получения прямых пополнений от Банка Фамильный в виде GET запроса
 */
/*
 * Секция настроек
 */

//секретный ключ
$secret = 'guessmeifyoucan';
//минимальная сумма платежа
$minAmount = '1';
//максимальная сумма платежа
$maxAmount = '9000';
//идентификатор сервиса
$serviceName = 'Internet';
//метод вычисления подписи (md5 или sha1)
$signMethod = 'md5';
//проверять ли вообще подпись?
$checkSign = true;




// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

/**
 * Calculates sign
 *  @param string $action - action number
 *  @param int $customerId - customer id 
 *  @param string $serviceId - service id
 *  @param string $payId - payment unique id
 * 
 *  @return string
 */
function ns_CalculateSign($action, $customerId, $serviceId, $payId) {
    global $signMethod, $secret;
    if ($signMethod == 'md5') {
        $sign = md5($action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $secret);
    }

    if ($signMethod == 'sha1') {
        $sign = sha1($action . "_" . $customerId . "_" . $serviceId . "_" . $payId . "_" . $secret);
    }
    $result = strtoupper($sign);
    return ($result);
}

/**
 * GET params define checker
 * 
 * @param  array $params - array of GET variables to check
 * 
 * @return  bool
 */
function ns_CheckGet($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty($_GET[$eachparam])) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
    }
    return ($result);
}

/**
 * String entity search
 * 
 * @param string $string - string variable to compare
 * @param string $search - searched substring
 * 
 * @return bool
 */
function ns_ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return(false);
    } else {
        return(true);
    }
}

/**
 * Returns all user RealNames
 * 
 * @return array
 */
function ns_UserGetAllRealnames() {
    $query_fio = "SELECT * from `realname`";
    $allfioz = simple_queryall($query_fio);
    $fioz = array();
    if (!empty($allfioz)) {
        foreach ($allfioz as $ia => $eachfio) {
            $fioz[$eachfio['login']] = $eachfio['realname'];
        }
    }
    return($fioz);
}

/**
 * Returns all tariff prices array
 * 
 * @return array
 */
function ns_TariffGetPricesAll() {
    $query = "SELECT `name`,`Fee` from `tariffs`";
    $allprices = simple_queryall($query);
    $result = array();

    if (!empty($allprices)) {
        foreach ($allprices as $io => $eachtariff) {
            $result[$eachtariff['name']] = $eachtariff['Fee'];
        }
    }

    return ($result);
}

/**
 * Check is transaction unique?
 * 
 * @param string $hash - hash string to check
 * 
 * @return bool
 */
function ns_CheckTransaction($hash) {
    $hash = loginDB_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Checks Customer ID availability and form correct reply
 * 
 * @param int $customerID - openpayz customer virtual ID
 * 
 * @return string
 */
function ns_CheckCustomer($customerId) {
    global $minAmount, $maxAmount, $serviceName;
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerId])) {
        $login = $allcustomers[$customerId];
        $allrealnames = ns_UserGetAllRealnames();
        $alltariffprices = ns_TariffGetPricesAll();
        $userData = simple_query("SELECT * from `users` WHERE `login`='" . $login . "'");
        //extracting tariff price
        if (!empty($userData)) {
            $userTariff = $userData['Tariff'];
        } else {
            $userTariff = false;
        }

        if ($userTariff) {
            $tariffPrice = $alltariffprices[$userTariff];
        } else {
            $tariffPrice = 0;
        }


        $result = '<?xml version="1.0" encoding="UTF-8" ?>
        <pay-response>
        <balance>' . @$userData['Cash'] . '</balance>
        <name>' . @$allrealnames[$login] . '</name>
        <account>' . $customerId . '</account>
        <service_id>' . $serviceName . '</service_id>
        <abonplata>' . $tariffPrice . '</abonplata>
        <min_amount>' . $minAmount . '</min_amount>
        <max_amount>' . $maxAmount . '</max_amount>
        <status_code>21</status_code>
        <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
        </pay-response>
        ';
    } else {
        $result = '<?xml version="1.0" encoding="UTF-8" ?>
        <pay-response>
        <status_code>-40</status_code>
        <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
        </pay-response>
        ';
    }
    $result = trim($result);
    return ($result);
}

/**
 * Runs terminal payment processing and returns transaction result
 * 
 * @param string $payId - payment ID without _prefix
 * @param float $summ - payment summ
 * @param int $customerid - customer virtual id
 * @param string $note - transaction notes
 * 
 * @return  string
 */
function ns_PaymentProcessing($payId, $summ, $customerid, $note) {
    global $serviceName;
    $hash = 'FBANK_' . loginDB_real_escape_string($payId);
    $note = loginDB_real_escape_string($note);

    if (ns_CheckTransaction($hash)) {
        op_TransactionAdd($hash, $summ, $customerid, 'FBANK', $note);
        op_ProcessHandlers();
        $result = '<?xml version="1.0" encoding="UTF-8" ?>
            <pay-response>
            <pay_id>' . $payId . '</pay_id >
            <service_id>' . $serviceName . '</service_id>
            <amount>' . $summ . '</amount>
            <status_code>22</status_code>
            <description>' . $serviceName . '</description>
            <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
            </pay-response>';
    } else {
        //if duplicate hash
        $result = '<?xml version="1.0" encoding="UTF-8" ?>
                <pay-response>
                <status_code>-100</status_code>
                <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
                </pay-response>';
    }

    $result = trim($result);
    return ($result);
}

/**
 * Checks transaction status by its payment ID
 * 
 * @param string $payID
 * 
 * @return string
 */
function ns_CheckPaymentStatus($payId) {
    global $serviceName;
    $hash = 'FBANK_' . loginDB_real_escape_string($payId);
    $query = "SELECT * from `op_transactions` WHERE `hash`='" . $hash . "'";
    $transactionData = simple_query($query);
    if (!empty($transactionData)) {
        $transactiondate = $transactionData['date'];
        $timestamp = strtotime($transactiondate);
        $transactiondate = date("d.m.Y H:i:s", $timestamp);
        $result = '<?xml version="1.0" encoding="UTF-8" ?>
            <pay-response>
            <status_code>11</status_code>
            <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
            <transaction>
            <pay_id>' . $payId . '</pay_id >
            <service_id>' . $serviceName . '</service_id>
            <amount>' . $transactionData['summ'] . '</amount>
            <status>111</status>
            <time_stamp>' . $transactiondate . '</time_stamp>
            </transaction>
            </pay-response>';
    } else {
        $result = '<?xml version="1.0" encoding="UTF-8" ?>
                <pay-response>
                <status_code>-10</status_code>
                <time_stamp>' . date('d.m.Y H:i:s') . '</time_stamp>
                </pay-response>
            ';
    }
    $result = trim($result);
    return ($result);
}

/* * **********************************
 * Primary payment routines
 * ********************************** */

if (ns_CheckGet(array('ACT', 'PAY_ID', 'SIGN'))) {
    // Extracting payment id and internal transaction HASH
    $payId = $_GET['PAY_ID'];
    $hash = 'FBANK_' . loginDB_real_escape_string($payId);

    // Extracting service id
    if (ns_CheckGet(array('SERVICE_ID'))) {
        $serviceId = loginDB_real_escape_string($_GET['SERVICE_ID']);
    } else {
        $serviceId = '';
    }
    // Detecting needed action
    $action = $_GET['ACT'];

    //
    // Customer info request
    //
    if ($action == 1) {
        if (ns_CheckGet(array('PAY_ACCOUNT'))) {
            $customerId = loginDB_real_escape_string($_GET['PAY_ACCOUNT']);

            //check user availability and send reply
            die(ns_CheckCustomer($customerId));
        } else {
            die('PAY_ACCOUNT FAILED');
        }
    }
    // 
    // Payment processing request
    //
    if ($action == 4) {
        if (ns_CheckGet(array('PAY_AMOUNT'))) {
            $summ = $_GET['PAY_AMOUNT'];
            if (ns_CheckGet(array('PAY_ACCOUNT'))) {
                $customerId = loginDB_real_escape_string($_GET['PAY_ACCOUNT']);
                if (ns_CheckGet(array('RECEIPT_NUM'))) {
                    $receiptNum = $_GET['RECEIPT_NUM'];
                    //Payment processing
                    if ($checkSign) {
                        $receivedSign = $_GET['SIGN'];
                        $normalSign = ns_CalculateSign($action, $customerId, $serviceId, $payId);
                        if ($receivedSign == $normalSign) {
                            $paymentHandler = ns_PaymentProcessing($payId, $summ, $customerId, 'receipt:' . $receiptNum);
                            die($paymentHandler);
                        } else {
                            die('SIGN ERROR');
                        }
                    } else {
                        $paymentHandler = ns_PaymentProcessing($payId, $summ, $customerId, ' receipt:' . $receiptNum);
                        die($paymentHandler);
                    }
                } else {
                    die('RECEIPT_NUM FAILED');
                }
            } else {
                die('PAY_ACCOUNT FAILED');
            }
        } else {
            die('PAY_AMOUNT FAILED');
        }
    }
    //
    // Transaction status check
    //
    if ($action == 7) {
        $transactionStatus = ns_CheckPaymentStatus($payId);
        die($transactionStatus);
    }
} else {
    die('ACT, PAY_ID OR SIGN PARAMS FAILED');
}
?>
