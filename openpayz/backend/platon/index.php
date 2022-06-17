<?php

$conf_platon = parse_ini_file('config/platon.ini');

$merchant_name = $conf_platon['MERCHANT_NAME'];
$merchant_url = $conf_platon['MERCHANT_URL'];
$merchant_service = $conf_platon['MERCHANT_SERVICE'];
$merchant_logo = $conf_platon['MERCHANT_LOGO'];
$merchant_currency = $conf_platon['MERCHANT_CURRENCY'];
$avail_prices = $conf_platon['AVAIL_PRICES'];

function platonSumm($customer_id, $avail_prices, $merchant_currency) {
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
        $payment_form = platonSumm($customer_id, $avail_prices, $merchant_currency);
    } else {
        $payment_form = 'FAIL: no customer ID set';
    }
} else {

    $customerId = $_POST['paymentid'];
    $amount = $_POST['amount'];
    if (!empty($customerId) AND ! empty($amount)) {
        $key = '***';
        $pass = '*******';

        $payment = 'CC';

        $data = base64_encode(
                json_encode(
                        array(
                            'amount' => '100.00',
                            'description' => 'Test',
                            'currency' => 'UAH',
                            'recurring' => 'Y'
                        )
                )
        );

        $req_token = 'Y';
        $url = 'http://google.com';

        $sign = md5(
                strtoupper(
                        strrev($key) .
                        strrev($payment) .
                        strrev($data) .
                        strrev($url) .
                        strrev($pass)
                )
        );

        //form here
    }
}

include('template.html');

