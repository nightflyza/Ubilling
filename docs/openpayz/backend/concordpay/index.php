<?php

/**
 * The interface part shown to the user before making a payment
 * using the ConcordPay payment system
 */

// Load config.
$conf_concordpay = parse_ini_file("config/concordpay.ini");

// Get Customer ID.
if (isset($_GET['customer_id'])) {
    $customer_id = htmlspecialchars(trim($_GET['customer_id']));
} else {
    die($conf_concordpay['ERROR_UNKNOWN_CUSTOMER_ID']);
}

// Get API OpenPayz
include("../../libs/api.openpayz.php");
// Get ConcordPay API
include("ConcordPay.php");

// Get merchant data.
$merchant_name    = $conf_concordpay['MERCHANT_NAME'];
$merchant_url     = $conf_concordpay['MERCHANT_URL'];
$merchant_service = $conf_concordpay['MERCHANT_SERVICE'];
$merchant_logo    = $conf_concordpay['MERCHANT_LOGO'];
$template_file    = $conf_concordpay['TEMPLATE'];

/**
 * Gets user associated customer data by ID.
 *
 * @param string $customer_id
 * @return array
 */
function cp_GetCustomerDataByID($customer_id)
{
    global $conf_concordpay;
    // Значение virtualid (он же: stg.op_customers, Платежный ID) получается, если взять CRC32 от users.login.
    $customer = mysql_real_escape_string($customer_id);
    $query = "
        SELECT `op_customers`.`realid`,
               `op_customers`.`virtualid`,
               `realname`.`realname`,
               `phones`.`phone`,
               `phones`.`mobile`,
               `emails`.`email`
        FROM `op_customers`
        INNER JOIN `realname` ON `op_customers`.`realid`= `realname`.`login`
        INNER JOIN `phones` ON `op_customers`.`realid` = `phones`.`login`
        INNER JOIN `emails` ON `op_customers`.`realid` = `emails`.`login`
        WHERE `op_customers`.`virtualid` = '" . $customer . "'";
    $data = simple_query($query);
    $realId=trim($data['realid']);
    $virtualId=trim($data['virtualid']);
    if (empty($data) || empty($realId) || empty($virtualId)) {
        die($conf_concordpay['ERROR_UNKNOWN_CUSTOMER']);
    }

    $client = array();

    $client['realid']    = $data['realid'];
    $client['virtualid'] = $data['virtualid'];

    if (trim($data['realname']) !== '') {
        $names = explode(' ', $data['realname']);
        $client['client_last_name']  = isset($names[0]) ? $names[0] : '';
        $client['client_first_name'] = isset($names[1]) ? $names[1] : '';
    } else {
        $client['client_last_name']  = '';
        $client['client_first_name'] = '';
    }

    if (trim($data['mobile']) !== '') {
        $client['phone'] = $data['mobile'];
    } elseif (trim($data['phone']) !== '') {
        $client['phone'] = $data['phone'];
    } else {
        $client['phone'] = '';
    }

    if (trim($data['email']) !== '') {
        $client['email'] = $data['email'];
    } else {
        $client['email'] = '';
    }

    return $client;
}

/**
 * Renders amount select form.
 *
 * @return string
 * @global array $conf_concordpay
 */
function cp_AmountForm()
{
    global $conf_concordpay;

    $result  = '<h3>' . $conf_concordpay['LOCALE_AMOUNT'] . '</h3><br>';
    $result .= '<form method="POST" action="">';
    $result .= '<input class="cp-amount" type="text" name="amount" value="0">';
    $result .= '<span class="cp-currency">' . $conf_concordpay['MERCHANT_CURRENCY'] . '</span>';
    $result .= '<input type="submit" class="cp-submit" value="' . $conf_concordpay['GO_TO_PAYMENT'] . '">';
    $result .= '</form>';

    return $result;
}

/**
 * Renders payment form.
 *
 * @param int $amount
 *
 * @return string
 */
function cp_PaymentForm($amount)
{
    global $conf_concordpay, $customer_id;

    $concordpay   = new ConcordPay($conf_concordpay['SECRET_KEY']);
    $merchant_id  = $conf_concordpay['MERCHANT_ID'];
    $order_id     = cp_OrderIdGenerate();
    $amount       = vf($amount, 3);
    $currency_iso = $conf_concordpay['CONCORDPAY_CURRENCY'];
    $client       = cp_GetCustomerDataByID($customer_id);

    $description  = $conf_concordpay['PAYMENT_BY_CARD'] . ' ' . htmlspecialchars($_SERVER['HTTP_HOST']) . ', '
        . $client['client_first_name'] . ' ' . $client['client_last_name'] . ', ' . $client['phone'];

    $approve_url   = cp_GetResultUrl('approve');
    $decline_url   = cp_GetResultUrl('decline');
    $cancel_url    = cp_GetResultUrl('cancel');
    $callback_url  = $conf_concordpay['FRONTEND_URL'] . "?customer_id=$customer_id";
    $signatureData = array(
        'merchant_id'  => $merchant_id,
        'order_id'     => $order_id,
        'amount'       => $amount,
        'currency_iso' => $currency_iso,
        'description'  => $description
    );
    $signature = $concordpay->cp_GenerateRequestSignature($signatureData);

    $result = $conf_concordpay['LOCALE_SUM'] . ': ' . $amount . ' ' . $conf_concordpay['MERCHANT_CURRENCY'];
    $result .= '<form method="POST" action="' . $conf_concordpay['CONCORDPAY_API_URL'] . '">';
    $result .= '<input type="hidden" name="operation"    value="Purchase"/>';
    $result .= '<input type="hidden" name="merchant_id"  value="' . $merchant_id . '"/>';
    $result .= '<input type="hidden" name="amount"       value="' . $amount . '"/>';
    $result .= '<input type="hidden" name="order_id"     value="' . $order_id . '"/>';
    $result .= '<input type="hidden" name="currency_iso" value="' . $currency_iso . '"/>';
    $result .= '<input type="hidden" name="description"  value="' . $description . '"/>';
    $result .= '<input type="hidden" name="approve_url"  value="' . $approve_url . '"/>';
    $result .= '<input type="hidden" name="decline_url"  value="' . $decline_url . '"/>';
    $result .= '<input type="hidden" name="cancel_url"   value="' . $cancel_url . '"/>';
    $result .= '<input type="hidden" name="callback_url" value="' . $callback_url . '"/>';
    $result .= '<input type="hidden" name="signature"    value="' . $signature . '"/>';
    // Statistics.
    $result .= '<input type="hidden" name="client_first_name" value="' . $client['client_first_name'] . '"/>';
    $result .= '<input type="hidden" name="client_last_name"  value="' . $client['client_last_name'] . '"/>';
    $result .= '<input type="hidden" name="email"             value="' . $client['email'] . '"/>';
    $result .= '<input type="hidden" name="phone"             value="' . $client['phone'] . '"/>';
    $result .= '<button type="submit" class="cp-submit">' . $conf_concordpay['LOCALE_SUBMIT'] . '</button>';

    return $result;
}

/**
 * The page with the payment results, to which the client will be redirected.
 *
 * @return string
 */
function cp_PageResult()
{
    global $conf_concordpay;
    $result = '';
    $case   = mb_strtolower($_GET['result']);
    switch ($case) {
        case 'approve':
            $result = '<h3 class="cp-success">' . $conf_concordpay["PAYMENT_SUCCESSFUL"] . '</h3>';
            break;
        case 'decline':
            $result = '<h3 class="cp-error">' . $conf_concordpay["PAYMENT_DECLINED"] . '</h3>';
            break;
        case 'cancel':
            $result = '<h3 class="cp-warning">' . $conf_concordpay["PAYMENT_CANCELED"] . '</h3>';
            break;
    }

    $result .= '<a href=' . cp_GetPaymentsListUrl() . ' class="cp-link">
            <img src="/../../../userstats/skins/paper/iconz/payments.gif" class="menuicon" alt="Payments">
            <span class="cp-text">' . $conf_concordpay["GO_TO_PAYMENTS_LIST"] . '</span>
        </a>';

    return $result;
}

/**
 * URL redirection to a page with a list of customer payments.
 *
 * @return string
 */
function cp_GetPaymentsListUrl()
{
    return htmlspecialchars($_SERVER['REQUEST_SCHEME']) . '://' . htmlspecialchars($_SERVER['HTTP_HOST'])
        . '/userstats/index.php?module=payments';
}

/**
 * URL redirection to the page with payment results.
 *
 * @param $result
 * @return string
 */
function cp_GetResultUrl($result)
{
    global $conf_concordpay, $customer_id;
    return $conf_concordpay['RESPONSE_URL'] . "?customer_id=$customer_id&result=$result";
}

/**
 * ID order generation.
 *
 * @return string
 */
function cp_OrderIdGenerate()
{
    return 'CP' . ConcordPay::ORDER_SEPARATOR . time();
}

if (!isset($_GET['customer_id'])) {
    die($conf_concordpay['ERROR_UNKNOWN_CUSTOMER_ID']);
}

if (isset($_GET['result'])) {
    // Payment results page.
    $payment_form = cp_PageResult();
} elseif (isset($_POST['amount'])) {
    // Form to send to the payment page ConcordPay.
    $payment_form = cp_PaymentForm($_POST['amount']);
} else {
    // Payment amount set form.
    $payment_form = cp_AmountForm();
}

// Render data in template.
include($template_file);
