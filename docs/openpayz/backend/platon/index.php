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

    return ($form);
}

$payment_form = '';
if (!isset($_POST['amount']) and ! isset($_POST['paymentid'])) {
    if (isset($_GET['customer_id']) and ! empty($_GET['customer_id'])) {
        $customer_id = $_GET['customer_id'];
        $payment_form = platonSumm($customer_id, $avail_prices, $merchant_currency);
    } else {
        $payment_form = 'FAIL: no customer ID set';
    }
} else {
    //push form
    $customerId = $_POST['paymentid'];
    $amountRaw = $_POST['amount'];
    $amount = $amountRaw . '.00'; //required with two finishing zeroes
    if (!empty($customerId) and ! empty($amountRaw)) {
        $key = $cfgPltn['KEY'];
        $pass = $cfgPltn['PASSWORD'];
        $payment = 'CC';
        $req_token = 'Y';
        $url = $cfgPltn['URL_OK'];
        $apiUrl = $cfgPltn['API_URL'];
        $splitProp = array();
        $splitRulesArr = array();
        $splitRules = '';

        //proportional static split
        if (isset($cfgPltn['SPLIT_STATIC'])) {
            if (!empty($cfgPltn['SPLIT_STATIC'])) {
                $splitProp = explode(',', $cfgPltn['SPLIT_STATIC']);
            }


            foreach ($splitProp as $io => $eachSplit) {
                if (!empty($eachSplit)) {
                    $cleanSplit = trim($eachSplit);
                    $splitRulesArr[$cleanSplit] = 0;
                }
            }
            $splitAmountRatio = sizeof($splitRulesArr);
            $splittedAmount = round(($amountRaw / $splitAmountRatio), 2);
            if (strpos($splittedAmount, '.') === false) {
                $splittedAmount = $splittedAmount . '.00';
            }

            foreach ($splitRulesArr as $eachSplit => $eachAmount) {
                if (!empty($eachSplit)) {
                    $splitRulesArr[$eachSplit] = $splittedAmount;
                }
            }
        }

        $rawData = array(
            'amount' => $amount,
            'description' => $customerId,
            'currency' => 'UAH',
            'recurring' => 'Y'
        );
  
        $data = base64_encode(json_encode($rawData));

        $sign = md5(
            strtoupper(
                strrev($key) .
                    strrev($payment) .
                    strrev($data) .
                    strrev($url) .
                    strrev($pass)
            )
        );

        //optional split rules append
        if (!empty($splitRulesArr)) {
            $splitRules .= '<input type="hidden" name="split_rules" value="' . htmlspecialchars(json_encode($splitRulesArr)) . '" />';
        }

        $form = '
            <form action="' . $apiUrl . '" method="POST">
                <input type="hidden" name="payment" value="' . $payment . '" />
                <input type="hidden" name="key" value="' . $key . '" />
                <input type="hidden" name="url" value="' . $url . '" />
                <input type="hidden" name="error_url" value="' . $cfgPltn['URL_FAIL'] . '" />
                <input type="hidden" name="data" value="' . $data . '" />
                <input type="hidden" name="req_token" value="' . $req_token . '" />
                <input type="hidden" name="sign" value="' . $sign . '" />
                <input type="hidden" name="lang" value="UK" />
                ' . $splitRules . '
              </form>   
              ';

        //auto form submit
        $form .= '<script type="text/javascript">
                  document.forms[0].submit();
                </script>';
        print($form);
    }
}

include('template.html');
