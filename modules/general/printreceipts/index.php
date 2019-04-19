<?php
if (cfr('PRINTRECEIPTS')) {
    if ($ubillingConfig->getAlterParam('PRINT_RECEIPTS_ENABLED')) {
        $receiptsPrinter = new PrintReceipt();

        if (wf_CheckPost(array('printthemall'))) {
            $receiptServiceType = $_POST['receiptsrv'];
            $receiptServiceName = $_POST['receiptsrvtxt'];
            $receiptUsersStatus = $_POST['receiptsubscrstatus'];
            $receiptPayTillDate = $_POST['receiptpaytill'];
            $receiptDebtCash = $_POST['receiptdebtcash'];
            $receiptPayForPeriod = $_POST['receiptpayperiod'];
            $receiptUserLogin = (wf_CheckPost(array('receiptslogin'))) ? $_POST['receiptslogin'] : '';
            $receiptStreet = (wf_CheckPost(array('receiptstreets')) and $_POST['receiptstreets'] != '-') ? $_POST['receiptstreets'] : '';
            $receiptBuild = (wf_CheckPost(array('receiptbuilds')) and $_POST['receiptbuilds'] != '-') ? $_POST['receiptbuilds'] : '';
            $receiptMonthsCnt = (wf_CheckPost(array('receiptmonthscnt'))) ? (vf($_POST['receiptmonthscnt'], 3)) : 1;

            $usersPrintData = $receiptsPrinter->getUsersPrintData($receiptServiceType, $receiptUsersStatus, $receiptUserLogin, $receiptDebtCash, $receiptStreet, $receiptBuild);

            if (!empty($usersPrintData)) {
                die($receiptsPrinter->printReceipts($usersPrintData, $receiptServiceName, $receiptPayTillDate, $receiptMonthsCnt, $receiptPayForPeriod));
            } else{
                show_warning(__('Query returned empty result'));
            }
        } else {
            show_window(__('Print receipts'), $receiptsPrinter->renderWebForm());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>