<?php

if (cfr('OPENPAYZ')) {
    $altCfg = $ubillingConfig->getAlter();
//check is openpayz enabled?
    if ($altCfg['OPENPAYZ_SUPPORT']) {

        $opayz = new OpenPayz();

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
                        ubRouting::nav('?module=openpayz');
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



            if (ubRouting::checkPost(array('searchyear', 'searchmonth', 'searchpaysys'))) {
                show_window(__('Search'), $opayz->renderSearchForm());
                show_window('', wf_BackLink('?module=openpayz', '', true));
                $opayz->doSearch(ubRouting::post('searchyear'), ubRouting::post('searchmonth'), ubRouting::post('searchpaysys'));
            } else {
                if (!ubRouting::checkGet('showtransaction')) {
                    show_window(__('Search'), $opayz->renderSearchForm());
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
?>
