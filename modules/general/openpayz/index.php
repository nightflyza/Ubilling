<?php

if (cfr('OPENPAYZ')) {
    $altCfg = $ubillingConfig->getAlter();
//check is openpayz enabled?
    if ($altCfg['OPENPAYZ_SUPPORT']) {

        $paySysLoadFlag = false;
        if (ubRouting::checkGet('transactionsearch') OR ubRouting::checkPost('searchpaysys')) {
            $paySysLoadFlag = true;
        }
        $opayz = new OpenPayz($paySysLoadFlag);

        //if manual processing transaction
        if ($altCfg['OPENPAYZ_MANUAL']) {
            if (ubRouting::checkGet('process', false)) {
                $transaction_data = $opayz->transactionGetData(ubRouting::get('process'));
                $customerid = $transaction_data['customerid'];
                $transaction_summ = $transaction_data['summ'];
                $transaction_paysys = $transaction_data['paysys'];
                $allcustomers = $opayz->getCustomers();
                if (isset($allcustomers[$customerid])) {
                    if ($transaction_data['processed'] != 1) {
                        $opayz->cashAdd($allcustomers[$customerid], $transaction_summ, $transaction_paysys);
                        $opayz->transactionSetProcessed($transaction_data['id']);
                        ubRouting::nav($opayz::URL_ME);
                    } else {
                        show_error(__('Already processed'));
                    }
                } else {
                    show_error(__('Selected user is absent in database!'));
                }
            }
        }

        if (ubRouting::checkGet('ajax')) {
            $opayz->transactionAjaxSource();
        }


        if (!ubRouting::checkGet('graphs')) {
            //download exported search
            if (ubRouting::checkGet('dload')) {
                zb_DownloadFile(base64_decode(ubRouting::get('dload')), 'docx');
            }


            //search some transactions here
            if (ubRouting::checkGet('transactionsearch')) {

                show_window(__('Search'), $opayz->renderSearchForm());
                //perform search
                if (ubRouting::checkPost(array('searchyear', 'searchmonth', 'searchpaysys'))) {
                    $opayz->doSearch(ubRouting::post('searchyear'), ubRouting::post('searchmonth'), ubRouting::post('searchpaysys'));
                }
            } else {
                if (!ubRouting::checkGet('showtransaction')) {
                    //show transactions list
                    $opayz->renderTransactionList();
                } else {
                    $opayz->renderTransactionDetails(ubRouting::get('showtransaction'));
                }
            }
        } else {
            show_window(__('Graphs'), $opayz->renderGraphs());
        }
        zb_BillingStats(true);
    } else {
        show_error(__('OpenPayz support not enabled'));
    }
} else {
    show_error(__('You cant control this module'));
}

