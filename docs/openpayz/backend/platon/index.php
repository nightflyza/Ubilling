<?php

require_once('../../libs/api.compat.php');
require_once('../../libs/api.astral.php');
require_once('../../libs/api.ubrouting.php');

$cfgPltn = parse_ini_file('config/platon.ini');

$merchant_name = $cfgPltn['MERCHANT_NAME'];
$merchant_url = $cfgPltn['MERCHANT_URL'];
$merchant_service = $cfgPltn['MERCHANT_SERVICE'];
$merchant_logo = $cfgPltn['MERCHANT_LOGO'];
$merchant_currency = $cfgPltn['MERCHANT_CURRENCY'];
$avail_prices = $cfgPltn['AVAIL_PRICES'];

function platonSumm($customer_id, $avail_prices, $merchant_currency) {
    global $cfgPltn;
    $inputs = '';
    $result = '';

    if (!empty($avail_prices)) {
        $avail_prices = explode(',', $avail_prices);
        $i = 0;
        foreach ($avail_prices as $eachprice) {
            $selected = false;
            if ($i == 0) {
                $selected = true;
            }
            $inputs .= wf_RadioInput('amount', $eachprice . ' ' . $merchant_currency, $eachprice, true, $selected);
            $i++;
        }

        if (isset($cfgPltn['CUSTOM_PRICE']) and ! empty($cfgPltn['CUSTOM_PRICE'])) {
            $jsCode = 'function change_custom_amount(){
                            var custom_amount = document.getElementById("radio_custom_amount");
                            custom_amount.value = document.getElementById("input_custom_amount").value;
                            custom_amount.value = (custom_amount.value).toFixed(2);
                        }
                        
                         document.addEventListener(\'DOMContentLoaded\', function() {
                            change_custom_amount();
                         }, false);';

            $inputs .= wf_tag('script') . $jsCode . wf_tag('script', true);
            $inputs .= wf_tag('input', false, '', 'type="radio" name="amount" value="' . $cfgPltn['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()"');
            $inputs .= wf_tag('input', false, '', 'onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $cfgPltn['CUSTOM_PRICE'] . '" min="' . $cfgPltn['CUSTOM_PRICE'] . '" step="any"') . ' ';
            $inputs .= wf_tag('label', false, '', 'for="radio_custom_amount"') . $cfgPltn['MERCHANT_CURRENCY'] . wf_tag('label', true) . wf_delimiter(0);
        }
    } else {
        $inputs .= wf_TextInput('amount', $merchant_currency, '', true, 5, 'finance');
    }

    $inputs .= wf_HiddenInput('paymentid', $customer_id);
    $inputs .= wf_delimiter(0);
    $inputs .= wf_Submit('Оплатити');
    $result .= wf_Form('', 'POST', $inputs, '');
    return ($result);
}

$payment_form = '';
if (!ubRouting::checkPost('amount') and ! ubRouting::checkPost('paymentid')) {
    if (ubRouting::checkGet('customer_id')) {
        $customer_id = ubRouting::get('customer_id', 'vf');
        $payment_form = platonSumm($customer_id, $avail_prices, $merchant_currency);
    } else {
        $payment_form = 'FAIL: no customer ID set';
    }
} else {
    //push form
    $customerId = ubRouting::post('paymentid', 'vf');
    $amountRaw = ubRouting::post('amount', 'float');
    //optional external service payment
    if (isset($cfgPltn['SERVICE_PAYMENT_PERCENT'])) {
        if ($cfgPltn['SERVICE_PAYMENT_PERCENT']) {
            $externalPercent = ubRouting::filters($cfgPltn['SERVICE_PAYMENT_PERCENT'], 'float');
            $amountRaw = $amountRaw + ($amountRaw * ($externalPercent / 100));
        }
    }

    if (!empty($customerId) and ! empty($amountRaw)) {
        $amount = number_format($amountRaw, 2, '.', ''); //required with two finishing zeroes
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

                foreach ($splitProp as $io => $eachSplit) {
                    if (!empty($eachSplit)) {
                        $cleanSplit = trim($eachSplit);
                        $splitRulesArr[$cleanSplit] = 0;
                    }
                }
                $splitAmountRatio = sizeof($splitRulesArr);
                $splittedAmount = round(($amountRaw / $splitAmountRatio), 2);
                $splittedAmount = number_format($splittedAmount, 2, '.', '');

                foreach ($splitRulesArr as $eachSplit => $eachAmount) {
                    if (!empty($eachSplit)) {
                        $splitRulesArr[$eachSplit] = $splittedAmount;
                    }
                }
            }
        }

        //goose resistance is here
        if (isset($cfgPltn['GOOSE_RESISTANCE']) and $cfgPltn['GOOSE_RESISTANCE']) {
            $baseUrl = $cfgPltn['BILLING_URL'] . '/?module=remoteapi&key=' . $cfgPltn['BILLING_KEY'] . '&action=goose';
            $callbackUrl = $baseUrl . '&amount=' . $amountRaw . '&paymentid=' . $customerId;
            $gooseResult = @file_get_contents($callbackUrl);
            if (!empty($gooseResult)) {
                $gooseResult = @json_decode($gooseResult, true);
                if (!empty($gooseResult)) {
                    if ($gooseResult['agents']) {
                        foreach ($gooseResult['agents'] as $io => $each) {
                            $splittedAmount = round(($each['splitamount']), 2);
                            $splittedAmount = number_format($splittedAmount, 2, '.', '');
                            $splitRulesArr[$each['edrpo']] = $splittedAmount;
                        }
                    } else {
                        die('Empty agents received');
                    }
                } else {
                    die('Something went wrong - decode error');
                }
            } else {
                die('Something went wrong - empty reply');
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
            $splitRules .= wf_HiddenInput('split_rules', htmlspecialchars(json_encode($splitRulesArr)));
        }

        $inputs = '';
        $inputs .= wf_HiddenInput('payment', $payment);
        $inputs .= wf_HiddenInput('key', $key);
        $inputs .= wf_HiddenInput('url', $url);
        $inputs .= wf_HiddenInput('error_url', $cfgPltn['URL_FAIL']);
        $inputs .= wf_HiddenInput('data', $data);
        $inputs .= wf_HiddenInput('req_token', $req_token);
        $inputs .= wf_HiddenInput('sign', $sign);
        $inputs .= wf_HiddenInput('lang', 'UK');
        $inputs .= $splitRules;
        $form = wf_Form($apiUrl, 'POST', $inputs);

        //auto form submit
        $form .= wf_tag('script', false, '', 'type="text/javascript"');
        $form .= '  document.forms[0].submit();';
        $form .= wf_tag('script', true);
        print($form);
    }
}

include('template.html');
