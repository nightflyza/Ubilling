<?php

require_once('../../libs/api.compat.php');
require_once('../../libs/api.astral.php');
require_once('../../libs/api.ubrouting.php');
require_once('../../libs/api.mysql.php');
require_once('../../libs/api.omaeurl.php');
require_once('../../libs/api.paysysproto.php');

$cfgPrvdx = parse_ini_file('config/providex.ini');

$merchant_name = $cfgPrvdx['MERCHANT_NAME'];
$merchant_url = $cfgPrvdx['MERCHANT_URL'];
$merchant_service = $cfgPrvdx['MERCHANT_SERVICE'];
$merchant_logo = $cfgPrvdx['MERCHANT_LOGO'];
$merchant_currency = $cfgPrvdx['MERCHANT_CURRENCY'];
$avail_prices = $cfgPrvdx['AVAIL_PRICES'];

function providexSumm($customerID, $avail_prices, $merchant_currency) {
    global $cfgPrvdx;
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

        if (isset($cfgPrvdx['CUSTOM_PRICE']) and ! empty($cfgPrvdx['CUSTOM_PRICE'])) {
            $jsCode = 'function change_custom_amount(){
                            var custom_amount = document.getElementById("radio_custom_amount");
                            custom_amount.value = document.getElementById("input_custom_amount").value;
                            custom_amount.value = (custom_amount.value).toFixed(2);
                        }
                        
                         document.addEventListener(\'DOMContentLoaded\', function() {
                            change_custom_amount();
                         }, false);';

            $inputs .= wf_tag('script') . $jsCode . wf_tag('script', true);
            $inputs .= wf_delimiter(0);
            $inputs .= wf_tag('input', false, '', 'type="radio" name="amount" value="' . $cfgPrvdx['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()"');
            $inputs .= wf_tag('input', false, '', 'onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $cfgPrvdx['CUSTOM_PRICE'] . '" min="' . $cfgPrvdx['CUSTOM_PRICE'] . '" step="any"') . ' ';
            $inputs .= wf_tag('label', false, '', 'for="radio_custom_amount"') . $cfgPrvdx['MERCHANT_CURRENCY'] . wf_tag('label', true) . wf_delimiter(0);
        }
    } else {
        $inputs .= wf_TextInput('amount', $merchant_currency, '', true, 5, 'finance');
    }

    $inputs .= wf_HiddenInput('paymentid', $customerID);
    $inputs .= wf_delimiter(0);
    $inputs .= wf_Submit('Оплатити');
    $result .= wf_Form('', 'POST', $inputs, '');
    return ($result);
}

$payment_form = '';
$jsCode       = '';

if (!ubRouting::checkPost('amount') and !ubRouting::checkPost('paymentid')) {
    if (ubRouting::checkGet('customer_id')) {
        $customerID = ubRouting::get('customer_id', 'vf');
        $payment_form = providexSumm($customerID, $avail_prices, $merchant_currency);
    } else {
        $payment_form = 'FAIL: no customer ID set';
    }
} else {
    //push form
    $customerID = ubRouting::post('paymentid', 'vf');
    $amountRaw = ubRouting::post('amount', 'float');
    //optional external service payment
    if (isset($cfgPrvdx['SERVICE_PAYMENT_PERCENT'])) {
        if ($cfgPrvdx['SERVICE_PAYMENT_PERCENT']) {
            $externalPercent = ubRouting::filters($cfgPrvdx['SERVICE_PAYMENT_PERCENT'], 'float');
            $amountRaw = $amountRaw + ($amountRaw * ($externalPercent / 100));
        }
    }

    if (!empty($customerID) and !empty($amountRaw)) {
        $amount = floatval(number_format($amountRaw, 2)); //required with two finishing zeroes
        $userLogin = PaySysProto::getUserLoginByPaymentID($customerID);
        $actionURL = $cfgPrvdx['UBAPI_URL'] . '?module=remoteapi&key=' . $cfgPrvdx['UBAPI_KEY'] . '&action=getagentdata&param=' . $userLogin;
        $agentData = PaySysProto::getUBAgentDataByUBAPIURL($actionURL);

        if (empty($agentData['id'])) {
            die('EMPTY AGENT ID RETURNED');
        }

        $agentID        = $agentData['id'];
        $agentDataExten = PaySysProto::getUBAgentDataExten($agentID, $cfgPrvdx['PAYSYS_EXTINFO_NAME']);

        if (empty($agentDataExten[0])) {
            die('EMPTY AGENT DATA EXTEN RETURNED');
        }

        $agentDataExten     = $agentDataExten[0];
        $prvdxPosID         = $agentDataExten['internal_paysys_id'];
        $prvdxEndpointKey   = $agentDataExten['internal_paysys_srv_id'];
        $prvdxAPIKEy        = $agentDataExten['paysys_token'];
        $prvdxAPISecret     = $agentDataExten['paysys_secret_key'];
        $prvdxCallbackURL   = $agentDataExten['paysys_callback_url'];
        $orderID            = crc32($userLogin . PaySysProto::genRandNumString()) . crc32(microtime(true));
        $jsonArr            = array(
                                   'pos_id'             => $prvdxPosID,
                                   'mode'               => 'hosted',
                                   'method'             => 'purchase',
                                   'amount'             => $amount,
                                   'currency'           => $cfgPrvdx['PAYSYS_API_CURRENCY'],
                                   'order_3ds_bypass'   => 'supported',
                                   'products'           => [],
                                   'customer_email'     => '',
                                   'description'        => $cfgPrvdx['PAYSYS_API_PAYMENT_PURPOSE'],
                                   'order_id'           => $orderID,
                                   'server_url'         => $prvdxCallbackURL,
                                   'payload'            => 'login=' . $userLogin . '__customerid=' . $customerID
                                   );

        //$jsonData = wf_HiddenInput('data', htmlspecialchars(json_encode($jsonArr)), 'providex_json_data');
        $jsonData = json_encode($jsonArr);
file_put_contents('qxcv', print_r($jsonArr, true) . "\n\n" . $jsonData);
        $omaeURL = new OmaeUrl($cfgPrvdx['API_URL']);
        $omaeURL->setVerboseLog(true, 'curl_debug');
        $omaeURL->setOpt(CURLOPT_POST, true);
        $omaeURL->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $omaeURL->setOpt(CURLOPT_MAXREDIRS, 0);
        $omaeURL->dataHeader('Content-type', 'application/json;charset=utf-8');
        $omaeURL->dataHeader('X-API-AUTH', 'CPAY ' . $prvdxAPIKEy . ':' . $prvdxAPISecret);
        $omaeURL->dataHeader('X-API-KEY', $prvdxEndpointKey);
        $omaeURL->dataHeader('Cache-control', 'no-cache');
        $omaeURL->dataPostRaw($jsonData);
        $sendResult = $omaeURL->response();
        $lastResult = $omaeURL->lastRequestInfo();
        $redirectURL = empty($lastResult['redirect_url']) ? 'empty_redir_url' : $lastResult['redirect_url'];
file_put_contents('zxcv', print_r($sendResult, true));
file_put_contents('vxcv', print_r($lastResult, true));

        if (empty($redirectURL)) {
            $jsCode = '';
        } else {
            $jsCode = wf_tag('script', false, '', 'type="text/javascript"');
            //$jsCode .= 'this.parent.location.href = "' . $redirectURL . '";';
            $jsCode .= 'window.location.replace("'. $redirectURL . '");';
            /*$jsCode.= '
    function submit() {
        var settings = {
            "url": "' . $cfgPrvdx['API_URL'] . '",
            "method": "POST",
            "headers": {
                "Content-type": "application/json",
                "Cache-control": "no-cache",
                "X-API-AUTH": "CPAY ' . $prvdxAPIKEy . ':' . $prvdxAPISecret . '",
                "X-API-KEY": "' . $prvdxEndpointKey . '"
            },
            "processData": false,
            "data": ' . $jsonData . '
        }

        $.ajax(settings)
            .done(function(data, status, req) { console.log("done", data, status, req); })
            .fail(function(req, status, err) { console.log("fail", req, status, err); });
    }

    submit();
    ';*/
            $jsCode .= wf_tag('script', true);
        }

        /*$rawData = array(
            'amount' => $amount,
            'description' => $customerID,
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
        }*/

        /*$inputs = '';
        $inputs .= wf_HiddenInput('payment', $payment);
        $inputs .= wf_HiddenInput('key', $key);
        $inputs .= wf_HiddenInput('url', $url);
        $inputs .= wf_HiddenInput('error_url', $cfgPrvdx['URL_FAIL']);
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
        print($form);*/
    }
}

include('template.html');
print($jsCode);