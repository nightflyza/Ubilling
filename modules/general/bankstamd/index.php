<?php

if (cfr('BANKSTAMD')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['BANKSTAMD_ENABLED']) {

        $banksta = new BankstaMd();
        $banksta->catchUploadRequest();
        //some list data
        if (wf_CheckGet(array('ajbslist'))) {
            $banksta->bankstaRenderAjaxList();
        }

        if (!wf_CheckGet(array('showhash'))) {
            if (!wf_CheckGet(array('showdetailed'))) {
                //main interface with upload form and loaded banksta list
                show_window(__('Upload'), $banksta->renderBankstaLoadForm());
                show_window(__('Previously loaded bank statements'), $banksta->renderBankstaList());
            } else {
                //some row detailed info
                show_window(__('Bank statement'), $banksta->bankstaGetDetailedRowInfo($_GET['showdetailed']));
            }
        } else {
            //update contract
            if (wf_CheckPost(array('newbankcontr', 'bankstacontractedit'))) {
                $banksta->bankstaSetContract($_POST['bankstacontractedit'], $_POST['newbankcontr']);
                rcms_redirect($banksta::URL_BANKSTA_PROCESSING . $_GET['showhash']);
            }

            //locking some row if needed
            if (isset($_POST['lockbankstarow'])) {
                $banksta->bankstaSetProcessed($_POST['bankstacontractedit']);
                rcms_redirect($banksta::URL_BANKSTA_PROCESSING . $_GET['showhash']);
            }

            //push cash to users if is needed
            if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
                $banksta->bankstaPushPayments();
                rcms_redirect($banksta::URL_BANKSTA_MGMT);
            }
            //big processing form
            show_window(__('Bank statements processing'), $banksta->bankstaProcessingForm($_GET['showhash']));
            show_window('', wf_BackLink($banksta::URL_ME));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>