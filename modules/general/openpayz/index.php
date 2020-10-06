<?php

if (cfr('OPENPAYZ')) {
    $alter_conf = $ubillingConfig->getAlter();
//check is openpayz enabled?
    if ($alter_conf['OPENPAYZ_SUPPORT']) {

        $opayz = new OpenPayz();

        //if manual processing transaction
        if ($alter_conf['OPENPAYZ_MANUAL']) {
            if (isset($_GET['process'])) {
                $transaction_data = $opayz->transactionGetData($_GET['process']);
                $customerid = $transaction_data['customerid'];
                $transaction_summ = $transaction_data['summ'];
                $transaction_paysys = $transaction_data['paysys'];
                $allcustomers = $opayz->getCustomers();
                if (isset($allcustomers[$customerid])) {
                    if ($transaction_data['processed'] != 1) {
                        $opayz->cashAdd($allcustomers[$customerid], $transaction_summ, $transaction_paysys);
                        $opayz->transactionSetProcessed($transaction_data['id']);
                        rcms_redirect("?module=openpayz");
                    } else {
                        show_error(__('Already processed'));
                    }
                } else {
                    show_error(__('Selected user is absent in database!'));
                }
            }
        }

        if (wf_CheckGet(array('ajax'))) {
            $opayz->transactionAjaxSource();
        }


        if (!wf_CheckGet(array('graphs'))) {
            //download exported search
            if (wf_CheckGet(array('dload'))) {
                zb_DownloadFile(base64_decode($_GET['dload']), 'docx');
            }



            if (wf_CheckPost(array('searchyear', 'searchmonth', 'searchpaysys'))) {
                show_window(__('Search'), $opayz->renderSearchForm());
                show_window('', wf_BackLink('?module=openpayz', '', true));
                $opayz->doSearch($_POST['searchyear'], $_POST['searchmonth'], $_POST['searchpaysys']);
            } else {
                if (!wf_CheckGet(array('showtransaction'))) {
                    show_window(__('Search'), $opayz->renderSearchForm());
                    //show transactions list
                    $opayz->renderTransactionList();
                } else {
                    $opayz->renderTransactionDetails($_GET['showtransaction']);
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
