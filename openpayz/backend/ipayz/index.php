<?php

$conf_ipay = parse_ini_file('config/ipayz.ini');

$merchant_name = $conf_ipay['MERCHANT_NAME'];
$merchant_url = $conf_ipay['MERCHANT_URL'];
$merchant_service = $conf_ipay['MERCHANT_SERVICE'];
$merchant_logo = $conf_ipay['MERCHANT_LOGO'];
$merchant_currency = $conf_ipay['MERCHANT_CURRENCY'];
$avail_prices = $conf_ipay['AVAIL_PRICES'];

function ipayz_form($customer_id, $avail_prices, $merchant_currency) {
    $form = '<p> <form action="" method="POST">';
    if (!empty($avail_prices)) {
        $avail_prices = explode(',', $avail_prices);
        $i = 0;
        foreach ($avail_prices as $eachprice) {
            if ($i == 0) {
                $selected = 'CHECKED';
            } else {
                $selected = '';
            }
            $label = '<label for="rbin' . $i . '">' . ($eachprice / 100) . ' ' . $merchant_currency . '</label>';
            $form .= '<input id="rbin' . $i . '" type="radio" name="amount" value="' . $eachprice . '" ' . $selected . '> ' . $label . '<br>';
            $i++;
        }
    } else {
        $form .= '<input type="text" name="amount"> ' . $merchant_currency;
    }

    $form .= '<input type="hidden" name="paymentid" value="' . $customer_id . '">';
    $form .= '<br> <input type="submit">';
    $form .= '</form> </p>';

    return($form);
}

$payment_form = '';
if (!isset($_POST['amount']) AND ! isset($_POST['paymentid'])) {
    if (isset($_GET['customer_id']) AND ! empty($_GET['customer_id'])) {
        $customer_id = $_GET['customer_id'];
        $payment_form = ipayz_form($customer_id, $avail_prices, $merchant_currency);
    } else {
        $payment_form = 'FAIL: no customer ID set';
    }
} else {

    $customerId = $_POST['paymentid'];
    $amount = $_POST['amount'];
    if (!empty($customerId) AND ! empty($amount)) {
        require_once("../../libs/api.openpayz.php");
        require_once("../../libs/api.ipay.php");
        $ipay = new IpayZ();
        $paymentUrl = $ipay->paymentCreate($customerId, $amount);
        rcms_redirect($paymentUrl);
    }
}

include('template.html');
