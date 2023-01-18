<?php

include ("../../libs/api.openpayz.php");
include ("../../libs/api.ipay.php");

if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
} else {
    die('customer_id fail');
}

$config = parse_ini_file('config/ipay.ini');

// выбираем нужные нам переменные о мерчанте
$merchant_name = $config['MERCHANT_NAME'];
$merchant_url = $config['MERCHANT_URL'];
$merchant_service = $config['MERCHANT_SERVICE'];
$merchant_logo = $config['MERCHANT_LOGO'];
$merchant_currency = $config['MERCHANT_CURRENCY'];
$template_file = $config['TEMPLATE'];

$ipay = new IpayMasterPass($config['MERCHANT_ID'], $config['SIGN_KEY'], $config['LANG'], $config['LOGIN']);
$sessionId = '';
$sessionResponseRaw = $ipay->InitWidgetSession($customer_id);
if (isset($sessionResponseRaw['response'])) {
    if (isset($sessionResponseRaw['response']['session'])) {
        $sessionId = $sessionResponseRaw['response']['session'];
    }
}
//rendering widget
if (!empty($sessionId)) {
    $payment_form = $ipay->getWidgetCode($sessionId);
} else {
    $payment_form = 'Error: empty session, may be empty mobile phone';
}

include($template_file);
