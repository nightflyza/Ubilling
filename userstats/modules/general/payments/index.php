<?php

function zbs_ShowUserPayments($login) {
    $usConfig = zbs_LoadConfig();
    if ($usConfig['PAYMENTS_ENABLED']) {
        $positiveFilter = false;
        $depthLimit = 0;

        if (isset($usConfig['PAYMENTS_ONLYPOSITIVE']) AND $usConfig['PAYMENTS_ONLYPOSITIVE']) {
            $positiveFilter = true;
        }

        if (isset($usConfig['PAYMENTS_DEPTH_LIMIT']) AND $usConfig['PAYMENTS_DEPTH_LIMIT']) {
            $depthLimit = $usConfig['PAYMENTS_DEPTH_LIMIT'];
        }

        $allpayments = zbs_CashGetUserPayments($login, $positiveFilter, $depthLimit);

        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Sum'));
        $cells .= wf_TableCell(__('Balance before'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                if ($usConfig['PAYMENTSTIMEHIDE']) {
                    $timestamp = strtotime($eachpayment['date']);
                    $cleanDate = date("Y-m-d", $timestamp);
                    $dateCells = $cleanDate;
                } else {
                    $dateCells = $eachpayment['date'];
                }

                $cells = wf_TableCell($dateCells, '', '', 'data-label="' . __('Date') . '"');
                if (isset($usConfig['PAYMENTS_ROUND_SUMM']) AND $usConfig['PAYMENTS_ROUND_SUMM']) {
                    $cells .= wf_TableCell(web_roundValue($eachpayment['summ'], 2), '', '', 'data-label="' . __('Sum') . '"');
                    $cells .= wf_TableCell(web_roundValue($eachpayment['balance'], 2), '', '', 'data-label="' . __('Balance before') . '"');
                } else {
                    $cells .= wf_TableCell($eachpayment['summ'], '', '', 'data-label="' . __('Sum') . '"');
                    $cells .= wf_TableCell($eachpayment['balance'], '', '', 'data-label="' . __('Balance before') . '"');
                }
                $rows .= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'mobile-table');
        show_window(__('Last payments'), $result);
    } else {
        $result = __('This module is disabled');
        show_window(__('Sorry'), $result);
    }
}

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
zbs_ShowUserPayments($user_login);

