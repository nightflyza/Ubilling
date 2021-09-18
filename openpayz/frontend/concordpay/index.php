<?php

// Frontend for accepting payments ConcordPay
// https://pay.concord.ua/docs/docs/ru/dispatcher.html

// Connecting OpenPayz API.
include("../../libs/api.openpayz.php");
// Connecting ConcordPay API.
include("../../backend/concordpay/ConcordPay.php");

// Debug mode with logging.
$debug = false;

// Load config.
$conf_concordpay = parse_ini_file("../../backend/concordpay/config/concordpay.ini");

// Get response JSON.
$rawRequest = file_get_contents("php://input");
$response   = json_decode($rawRequest, true);

/**
 * Check for POST have needed variables
 *
 * @param $response
 * @return  bool
 */
function cp_CheckResponse($response)
{
    $requiredParameters = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
        'merchantSignature',
        'transactionStatus',
        'type'
    );

    $result = true;
    if (empty($response)) {
        return false;
    }

    foreach ($requiredParameters as $param) {
        if (!isset($response[$param]) || empty($response[$param])) {
            $result = false;
            break;
        }
    }

    return $result;
}

/**
 * @return ConcordPay
 */
function cp_GetConcordpayInstance()
{
    global $conf_concordpay;
    if (isset($conf_concordpay['cp_instance']) && $conf_concordpay['cp_instance'] instanceof ConcordPay) {
        return $conf_concordpay['cp_instance'];
    }
    $conf_concordpay['cp_instance'] = new ConcordPay($conf_concordpay['SECRET_KEY']);

    return $conf_concordpay['cp_instance'];
}

/**
 * @param array $response
 * @return bool
 */
function cp_CheckSignature($response)
{
    $sign = cp_GetConcordpayInstance()->cp_GenerateResponseSignature($response);

    return ($sign === $response['merchantSignature']);
}

/**
 * @param $response
 * @return bool
 */
function cp_CheckOperationType($response)
{
    if (!isset($response['type']) || !in_array($response['type'], cp_GetConcordpayInstance()->cp_GetOperationTypes())) {
        return false;
    }
    return true;
}

/**
 * @param $response
 * @return bool
 */
function cp_CheckTransactionStatus($response)
{
    if (!isset($response['transactionStatus'])
        || !in_array($response['transactionStatus'], array(ConcordPay::TRANSACTION_APPROVED, ConcordPay::TRANSACTION_DECLINED))
    ) {
        return false;
    }
    return true;
}

/**
 * @return bool
 */
function cp_CheckCustomerid()
{
    $customerId=trim($_GET['customer_id']);
    if (!isset($_GET['customer_id']) || empty($customerId)) {
        return false;
    }

    $allCustomers = op_CustomersGetAll();
    if (!array_key_exists(trim($_GET['customer_id']), $allCustomers)) {
        return false;
    }

    return true;
}

/**
 * Check is transaction unique?
 *
 * @param $hash - transaction hash
 *
 * @return bool
 */
function cp_CheckTransaction($hash)
{
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return false;
    }
    return true;
}

/**
 * Reports some error
 *
 * @param string $data
 *
 * @return void
 */
function cp_reportError($data)
{
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
function cp_reportSuccess($data)
{
    global $debug;
    header('HTTP/1.1 200 ' . $data . '"', true, 200);
    if ($debug) {
        file_put_contents('./debug.log', date("Y-m-d H:i:s") . ': ' . $data . "\n", FILE_APPEND);
    }
    die($data);
}

if (cp_CheckResponse($response) !== true) {
    cp_reportError($conf_concordpay['ERROR_NO_RESPONSE_DATA']);
}

if (cp_CheckSignature($response) !== true) {
    cp_reportError($conf_concordpay['ERROR_WRONG_SIGNATURE']);
}

if (cp_CheckOperationType($response) !== true) {
    cp_reportError($conf_concordpay['ERROR_WRONG_OPERATION_TYPE']);
}

if (cp_CheckTransactionStatus($response) !== true) {
    cp_reportError($conf_concordpay['ERROR_WRONG_TRANSACTION_STATUS']);
}

if (cp_CheckCustomerid() !== true) {
    cp_reportError($conf_concordpay['ERROR_UNKNOWN_CUSTOMER']);
}

$hash       = $response['orderReference'];
$customerid = htmlspecialchars(trim($_GET['customer_id']));
$summ       = $response['amount'];
$paysys     = 'CONCORDPAY';
$note       = 'Transaction ID: ' . $response['transactionId'];

if (cp_CheckTransaction($hash) !== true) {
    cp_reportError($conf_concordpay['ERROR_DOUBLE_PAYMENT']);
}

if ($response['transactionStatus'] === ConcordPay::TRANSACTION_APPROVED) {
    if ($response['type'] === ConcordPay::RESPONSE_TYPE_PAYMENT) {
        // Ordinary payment.
        // Register a new transaction.
        op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
        // Calling the raw transaction handlers.
        op_ProcessHandlers();
        // Finish the work.
        cp_reportSuccess($conf_concordpay['TRANSACTION_SUCCESSFUL']);
    }
}
