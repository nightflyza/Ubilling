<?php

//including required libs
include ("../../libs/api.openpayz.php");
include ("../../libs/api.ipay.php");

//debug mode with logging
$debug = false;

/**
 * Reports some error
 * 
 * @param string $data
 * 
 * @return void
 */
function ipay_reportError($data) {
    global $debug;
    header('HTTP/1.1 400 ' . $data . '"', true, 400);
    if ($debug) {
        file_put_contents('./debug.log', date("Y-m-d H:i:s") . ': ' . $data . "\n", FILE_APPEND);
        file_put_contents('./debug.log', print_r($_POST, true) . "\n", FILE_APPEND);
        file_put_contents('./debug.log', '=========================' . "\n", FILE_APPEND);
    }
    die($data);
}

/**
 * Reports some success
 * 
 * @param string $data
 * 
 * @return void
 */
function ipay_reportSuccess($data) {
    global $debug;
    header('HTTP/1.1 200 ' . $data . '"', true, 200);
    if ($debug) {
        file_put_contents('./debug.log', date("Y-m-d H:i:s") . ': ' . $data . "\n", FILE_APPEND);
    }
    die($data);
}

/**
 * Check is transaction unique?
 * 
 * @param $hash - hash string to check
 * @return bool
 */
function ipay_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

//catch some xml notification
$xml = $_POST['xml'];

if (!empty($xml)) {
    $xml = str_replace('\"', '"', $xml);
    $rawXml = xml2array($xml);

    if (!empty($rawXml)) {
        if (isset($rawXml['payment'])) {
            if (isset($rawXml['payment']['status'])) {
                if ($rawXml['payment']['status'] == 5) {
                    $summ = ($rawXml['payment']['amount'] / 100); //в копійках
                    $timestamp = $rawXml['payment']['timestamp'];
                    $rawHash = $rawXml['payment']['ident'];
                    $hash = 'IPAYZ_' . $rawHash;
                    @$transactionInfoRaw = $rawXml['payment']['transactions']['transaction'][0]['info'];
                    if (!empty($transactionInfoRaw)) {
                        $transactionInfo = json_decode($transactionInfoRaw);
                        $customerId = $transactionInfo->acc;
                        //очевидно для платежей прилетающих с черджера другой формат данных о транзакции
                        if (empty($customerId)) {
                            $customerId = $transactionInfo->step_1->acc;
                        }
                        if (!empty($customerId)) {
                            $allCustomers = op_CustomersGetAll();

                            if (isset($allCustomers[$customerId])) {
                                if (ipay_CheckTransaction($hash)) {
                                    op_TransactionAdd($hash, $summ, $customerId, 'IPAY', $transactionInfoRaw);
                                    op_ProcessHandlers();
                                    ipay_reportSuccess('TRANSACTION OK');
                                } else {
                                    ipay_reportSuccess('TRANSACTION OK');
                                }
                            } else {
                                ipay_reportError('UNKNOWN USER ' . $customerId);
                            }
                        } else {
                            ipay_reportError('CANT PARSE USER');
                        }
                    } else {
                        ipay_reportError('EMPTY TRANSACTION INFO');
                    }
                } else {
                    ipay_reportError('UNSUCCEFULL STATUS');
                }
            } else {
                ipay_reportError('STATUS SECTION MISSING');
            }
        } else {
            ipay_reportError('PAYMENT SECTION MISSING');
        }
    } else {
        ipay_reportError('XML REQUEST PARSE FAIL');
    }
} else {
    ipay_reportError('EMPTY REQUEST');
}
?>