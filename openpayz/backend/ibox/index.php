<?php

if (isset($_GET['customer_id']) AND ! empty($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
} else {
    die('customer_id fail');
}

require_once ('../../libs/api.openpayz.php');
$conf = parse_ini_file('config/ibox.ini');
$refUrl = $conf['REF_URL'];
$recSumm = $conf['REC_SUMM'];

if (!empty($refUrl)) {

    $redirectUrl = $refUrl . '?account=' . $customer_id;
    if ($recSumm) {
        $redirectUrl .= '&payment_sum=' . $recSumm;
    }
    rcms_redirect($redirectUrl);
} else {
    die('REF_URL is empty');
}