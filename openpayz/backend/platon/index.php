<?php

$cfgPltn = parse_ini_file('config/platon.ini');

$merchant_name = $cfgPltn['MERCHANT_NAME'];
$merchant_url = $cfgPltn['MERCHANT_URL'];
$merchant_service = $cfgPltn['MERCHANT_SERVICE'];
$merchant_logo = $cfgPltn['MERCHANT_LOGO'];
$merchant_currency = $cfgPltn['MERCHANT_CURRENCY'];
$avail_prices = $cfgPltn['AVAIL_PRICES'];

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
            $label = '<label for="rbin' . $i . '">' . $eachprice . ' ' . $merchant_currency . '</label>';
            $form .= '<input id="rbin' . $i . '" type="radio" name="amount" value="' . $eachprice . '" ' . $selected . '> ' . $label . '<br>';
            $i++;
        }
    } else {
        $form .= '<input type="text" name="amount" pattern="^\d+$" placeholder="0" > ' . $merchant_currency . '<br>';
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
    //push form
    $customerId = $_POST['paymentid'];
    $amount = $_POST['amount'];
    $amount = ($amount * 100) . '.00'; //required in cents with two finishing zeroes
    if (!empty($customerId) AND ! empty($amount)) {
        $key = $cfgPltn['KEY'];
        $pass = $cfgPltn['PASSWORD'];
        $payment = 'CC';
        $req_token = 'Y';
        $url = $cfgPltn['URL_OK'];
        $apiUrl = $cfgPltn['API_URL'];
        $data = base64_encode(
                json_encode(
                        array(
                            'amount' => $amount,
                            'description' => $merchant_service,
                            'currency' => 'UAH',
                            'recurring' => 'Y'
                        )
                )
        );

        $sign = md5(
                strtoupper(
                        strrev($key) .
                        strrev($payment) .
                        strrev($data) .
                        strrev($url) .
                        strrev($pass)
                )
        );

        $form = '
            <form action="' . $apiUrl . '" method="POST">
                <input type="hidden" name="payment" value="' . $payment . '" />
                <input type="hidden" name="key" value="' . $key . '" />
                <input type="hidden" name="url" value="' . $url . '" />
                <input type="hidden" name="data" value="' . $data . '" />
                <input type="hidden" name="req_token" value="' . $req_token . '" />
                <input type="hidden" name="sign" value="' . $sign . '" />
              </form>   
              
              <script type="text/javascript">
                document.forms[0].submit();
              </script>
              ';
        print($form);
    }
}

include('template.html');

