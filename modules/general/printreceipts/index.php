<?php
if (cfr('PRINTRECEIPTS')) {
    if ($ubillingConfig->getAlterParam('PRINT_RECEIPTS_ENABLED')) {
        $receiptsPrinter = new PrintReceipt();

        if (ubRouting::checkGet('ajax')) {
            $dateFrom = (ubRouting::checkGet('invdatefrom')) ? ubRouting::get('invdatefrom') : '';
            $dateTo = (ubRouting::checkGet('invdateto')) ? ubRouting::get('invdateto') : '';
            $userLogin = (ubRouting::checkGet('usrlogin')) ? ubRouting::get('usrlogin') : '';
            $whereStr = '';

            if (!empty($dateFrom) or !empty($dateTo)) {
                if (!empty($dateFrom) and $dateFrom == $dateTo) {
                    $dateTo = $dateTo . ' 23:59:59';
                }

                if (!empty($dateFrom) and empty($dateTo)) {
                    $whereStr = " `invoice_date` >= '" . $dateFrom . "' ";
                } elseif (empty($dateFrom) and !empty($dateTo)) {
                    $whereStr = " `invoice_date` <= '" . $dateTo . "' ";
                } else {
                    $whereStr = " `invoice_date` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . " 23:59:59' ";
                }
            }

            if (!empty($userLogin)) {
                $whereStr.= (empty($whereStr)) ? '' : ' AND ';
                $whereStr.= " `login` = '" . $userLogin . "' ";
            }

            $data = $receiptsPrinter->getInvoicesData($whereStr);
            $receiptsPrinter->renderJSON($data);
        }

        if (ubRouting::checkGet('printid')) {
            $whereStr = " `id` = " . ubRouting::get('printid');
            $data = $receiptsPrinter->getInvoicesData($whereStr);

            if (!empty($data[0]['invoice_body'])) {
                die(base64_decode($data[0]['invoice_body']));
            } else {
                die('Empty receipt body or ID not found');
            }
        }

        if (ubRouting::checkPost('printthemall')) {
            $receiptServiceType = ubRouting::post('receiptsrv');
            $receiptServiceName = ubRouting::post('receiptsrvtxt');
            $receiptUsersStatus = ubRouting::post('receiptsubscrstatus');
            $receiptFrozenStatus = ubRouting::post('receiptfrozenstatus');
            $receiptPayTillDate = ubRouting::post('receiptpaytill');
            $receiptDebtCash = ubRouting::post('receiptdebtcash');
            $receiptPayForPeriod = ubRouting::post('receiptpayperiod');
            $receiptUserLogin = (ubRouting::checkPost('receiptslogin')) ? ubRouting::post('receiptslogin') : '';
            $receiptCity = (ubRouting::checkPost('receiptscities') and ubRouting::post('receiptscities') != '-') ? ubRouting::post('receiptscities') : '';
            $receiptStreet = (ubRouting::checkPost('receiptstreets') and ubRouting::post('receiptstreets') != '-') ? ubRouting::post('receiptstreets') : '';
            $receiptBuild = (ubRouting::checkPost('receiptbuilds') and ubRouting::post('receiptbuilds') != '-') ? ubRouting::post('receiptbuilds') : '';
            $receiptTariffID = (ubRouting::checkPost('receipttariffs') and ubRouting::post('receipttariffs') != '-') ? ubRouting::post('receipttariffs') : '';
            $receiptTagID = (ubRouting::checkPost('receipttags') and ubRouting::post('receipttags') != '-') ? ubRouting::post('receipttags') : '';
            $receiptMonthsCnt = (ubRouting::checkPost('receiptmonthscnt')) ? ubRouting::post('receiptmonthscnt', 'int') : 1;
            $receiptSaveToDB = wf_getBoolFromVar(ubRouting::post('receiptsaveindb'));
            $receiptTemplate = (ubRouting::checkPost('receipttemplate') and ubRouting::post('receipttemplate') != '-') ? ubRouting::post('receipttemplate') : '';

            $usersPrintData = $receiptsPrinter->getUsersPrintData($receiptServiceType, $receiptUsersStatus, $receiptUserLogin, $receiptDebtCash,
                                                                  $receiptCity, $receiptStreet, $receiptBuild, $receiptTagID,
                                                                  $receiptTariffID, $receiptFrozenStatus);

            if (!empty($usersPrintData)) {
                die($receiptsPrinter->printReceipts($usersPrintData, $receiptServiceName, $receiptPayTillDate, $receiptMonthsCnt,
                                                    $receiptPayForPeriod, $receiptSaveToDB, $receiptTemplate));
            } else{
                show_warning(__('Query returned empty result'));
            }
        } elseif (ubRouting::checkGet('showhistory')) {
            show_window(__('Issued receipts'), wf_BackLink($receiptsPrinter::URL_ME, __('Back'), true)
                                               . wf_delimiter(0) . $receiptsPrinter->renderJQDT());
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