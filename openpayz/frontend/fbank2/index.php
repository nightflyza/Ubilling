<?php

/*
 * Фронтенд для взаимодействия с EasyPay / Fbank
 * на базе протокола: http://store.nightfly.biz/st/1461842084/EasyPay.Provider.3.1.pdf
 * 
 * Проверить можно тут: http://provider.easysoft.com.ua/
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

//debug mode
define('DEBUG_MODE', 0);

//transaction storage path
define('TRANSACTION_PATH', './tmp/');

//Error codes
define('NO_SUCH_USER', '-1');
define('ERROR_PAYMENT', '-2');
define('TRANSACTION_EXISTS', '-3');
define('TRANSACTION_NOT_EXIST', '-4');
define('BAD_CHECKSUM', '-5');

/**
 * Returns all user RealNames
 * 
 * @return array
 */
function ep_UserGetAllRealnames() {
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
 * Check is transaction unique?
 * 
 * @param $hash - hash string to check
 * @return bool/int
 */
function ep_GetTransaction($transactionIdRaw) {
    if (file_exists(TRANSACTION_PATH . $transactionIdRaw)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Gets existing transaction date
 * 
 * @param $id - existing transaction id
 * @return bool/datetime
 */
function ep_GetTransactionTime($id) {
    $id = vf($id, 3);
    $query = "SELECT `date` from `op_transactions` WHERE `id`='" . $id . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return ($data['date']);
    } else {
        return (false);
    }
}

/**
 * Saves unprocessed transaction for further usage & returns its ID
 * 
 * @param array $data Request data
 * 
 * @return string
 */
function ep_SaveTransaction($data) {
    $result = false;
    $fileName = $data['Payment']['OrderId'];

    $toSave = serialize($data);
    if (file_exists(TRANSACTION_PATH)) {
        file_put_contents(TRANSACTION_PATH . $fileName, $toSave);
        if (file_exists(TRANSACTION_PATH . $fileName)) {
            $result = $fileName;
        } else {
            die('No write permissions to ' . TRANSACTION_PATH . ' directory');
        }
    } else {
        mkdir(TRANSACTION_PATH);
        if (!file_exists(TRANSACTION_PATH)) {
            die('No directory write permissions!');
        } else {
            file_put_contents(TRANSACTION_PATH . $fileName, $toSave);
            if (file_exists(TRANSACTION_PATH . $fileName)) {
                $result = $fileName;
            } else {
                die('No write permissions to ' . TRANSACTION_PATH . ' directory');
            }
        }
    }
    return ($result);
}

/**
 * Returns transaction data by its ID
 * 
 * @param string $transactionId
 * 
 * @return array
 */
function ep_GetTransactionData($transactionId) {
    $result = array();
    if (file_exists(TRANSACTION_PATH . $transactionId)) {
        $raw = file_get_contents(TRANSACTION_PATH . $transactionId);
        $result = unserialize($raw);
    }
    return ($result);
}

/**
 * Check is transaction unique?
 * 
 * @param $hash - hash string to check
 * @return bool/int
 */
function ep_CheckTransactionUnique($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns full address list
 *
 * @return array
 */
function ep_AddressGetFulladdresslist() {
    $result = array();
    $apts = array();
    $builds = array();
//наглая заглушка
    $alterconf['ZERO_TOLERANCE'] = 0;
    $alterconf['CITY_DISPLAY'] = 0;
    $city_q = "SELECT * from `city`";
    $adrz_q = "SELECT * from `address`";
    $apt_q = "SELECT * from `apt`";
    $build_q = "SELECT * from build";
    $streets_q = "SELECT * from `street`";
    $alladdrz = simple_queryall($adrz_q);
    $allapt = simple_queryall($apt_q);
    $allbuilds = simple_queryall($build_q);
    $allstreets = simple_queryall($streets_q);
    if (!empty($alladdrz)) {
        foreach ($alladdrz as $io1 => $eachaddress) {
            $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
        }
        foreach ($allapt as $io2 => $eachapt) {
            $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
        }
        foreach ($allbuilds as $io3 => $eachbuild) {
            $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
        }
        foreach ($allstreets as $io4 => $eachstreet) {
            $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
        }
        foreach ($address as $io5 => $eachaddress) {
            $apartment = $apts[$eachaddress['aptid']]['apt'];
            $building = $builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
            $streetname = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
            $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                if ($apartment == 0) {
                    $apartment_filtered = '';
                } else {
                    $apartment_filtered = '/' . $apartment;
                }
            } else {
                $apartment_filtered = '/' . $apartment;
            }
            if (!$alterconf['CITY_DISPLAY']) {
                $result[$eachaddress['login']] = $streetname . ' ' . $building . $apartment_filtered;
            } else {
                $result[$eachaddress['login']] = $cities[$cityid] . ' ' . $streetname . ' ' . $building . $apartment_filtered;
            }
        }
    }
    return($result);
}

//catch POST request
//deprecated in PHP 5.6
//$xml = $HTTP_RAW_POST_DATA;
$xml = file_get_contents('php://input');

$rawXml = xml2array($xml);

//debug mode logging
if (!empty($rawXml)) {
    if (DEBUG_MODE) {
        $debugSave = print_r($rawXml, true);
        file_put_contents('debug.log', $debugSave, FILE_APPEND | LOCK_EX);
    }
}

//check request validity
if (!empty($rawXml)) {
    if (isset($rawXml['Request'])) {
        //определяем тип запроса
        if (isset($rawXml['Request']['Check'])) {
            //
            // проверка пользователя
            //
        $allcustomers = op_CustomersGetAll();
            $checkCustomerId = $rawXml['Request']['Check']['Account'];
            if (isset($allcustomers[$checkCustomerId])) {
                @$userlogin = $allcustomers[$checkCustomerId];
                $allrealnames = ep_UserGetAllRealnames();
                $allAddress=ep_AddressGetFulladdresslist();
                $userData = simple_query("SELECT * from `users` WHERE `login`='" . $userlogin . "'");
                $reply = '
                <Response>
                <StatusCode>0</StatusCode>
                <StatusDetail>Ok</StatusDetail>
                <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                 <AccountInfo>
                    <Name>' . @$allrealnames[$userlogin] . '</Name>
                    <Address>'.@$allAddress[$userlogin].'</Address>
                    <Balance>'.@$userData['Cash'].'</Balance>
                 </AccountInfo>
                </Response>
                ';
                $reply = trim($reply);
                die($reply);
            } else {
                $reply = '
                <Response>
                <StatusCode>' . NO_SUCH_USER . '</StatusCode>
                <StatusDetail>User not found</StatusDetail>
                </Response>
                ';
                $reply = trim($reply);
                die($reply);
            }
        }

        //
        // creating unprocessed transaction in TRANSACTION_PATH
        //
        if (isset($rawXml['Request']['Payment'])) {
            $allcustomers = op_CustomersGetAll();
            $paymentCustomerId = mysql_real_escape_string($rawXml['Request']['Payment']['Account']);
            $transactionIdRaw = $rawXml['Request']['Payment']['OrderId'];

            if (isset($allcustomers[$paymentCustomerId])) {
                if (!ep_GetTransaction($transactionIdRaw)) {

                    $newTransactionId = ep_SaveTransaction($rawXml['Request']);

                    $reply = '
                <Response>
                <StatusCode>0</StatusCode>
                <StatusDetail>Order Created</StatusDetail>
                <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                <PaymentId>' . $newTransactionId . '</PaymentId>
                </Response>
                ';
                    $reply = trim($reply);
                    die($reply);
                } else {
                    //уже есть такая транзакцийка
                    $reply = '
                     <Response>
                        <StatusCode>' . TRANSACTION_EXISTS . '</StatusCode>
                        <StatusDetail>Duplicate transaction</StatusDetail>
                        <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                        <PaymentId>' . ep_GetTransaction($transactionIdRaw) . '</PaymentId>
                     </Response>
                    ';
                    $reply = trim($reply);
                    die($reply);
                }
            } else {
                $reply = '
                     <Response>
                        <StatusCode>' . NO_SUCH_USER . '</StatusCode>
                        <StatusDetail>No such user</StatusDetail>
                        <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                        <PaymentId>' . ep_GetTransaction($transactionIdRaw) . '</PaymentId>
                     </Response>
                    ';
                $reply = trim($reply);
                die($reply);
            }
        }

        //
        // Transaction confirmation
        //
        if (isset($rawXml['Request']['Confirm'])) {
            $checkPaymentID = mysql_real_escape_string($rawXml['Request']['Confirm']['PaymentId']);
            $transactionDataRaw = ep_GetTransactionData($checkPaymentID);

            @$transactionDate = $transactionDataRaw['DateTime'];

            if ($transactionDate) {
                $hashClean = mysql_real_escape_string($transactionDataRaw['Payment']['OrderId']);
                $hash = 'FB_' . $hashClean;
                $summ = mysql_real_escape_string($transactionDataRaw['Payment']['Amount']);
                $paymentCustomerId = mysql_real_escape_string($transactionDataRaw['Payment']['Account']);

                if (ep_CheckTransactionUnique($hash)) {
                    //really processin cash & openpayz transaction
                    op_TransactionAdd($hash, $summ, $paymentCustomerId, 'FBANK', 'no debug info');
                    op_ProcessHandlers();
                }

                $reply = '
                    <Response>
                      <StatusCode>0</StatusCode>
                      <StatusDetail>Transaction Ok</StatusDetail>
                      <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                      <OrderDate>' . $transactionDate . '</OrderDate>
                    </Response>
                    ';
                $reply = trim($reply);
                die($reply);
            } else {
                //нету такой транзакции
                $reply = '
                     <Response>
                        <StatusCode>' . TRANSACTION_NOT_EXIST . '</StatusCode>
                        <StatusDetail>No existing transaction</StatusDetail>
                        <DateTime>' . date("Y-m-d\TH:i:s") . '</DateTime>
                        <PaymentId>' . $checkPaymentID . '</PaymentId>
                     </Response>
                    ';
                $reply = trim($reply);
                die($reply);
            }
        }
    }

    //
    // Transaction cancel dummy reply
    //
    
    if (isset($rawXml['Request']['Cancel'])) {
        $curDate = date("Y-m-d\TH:i:s");
        $reply = '<Response>
                <StatusCode>' . ERROR_PAYMENT . '</StatusCode>
                    <StatusDetail>Transaction destroyed</StatusDetail>
                    <DateTime>' . $curDate . '</DateTime>
                    <CancelDate>' . $curDate . '</CancelDate>
                </Response>';
        $reply = trim($reply);
        die($reply);
    }
} else {
    die('EMPTY_POST_DATA');
}
?>
