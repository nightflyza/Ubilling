<?php

if (cfr('BANKSTAMD')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['BANKSTAMD_ENABLED']) {

        $banksta = new BankstaMd();
        $banksta->catchUploadRequest();
        if (wf_CheckGet(array('ajbslist'))) {
            $banksta->bankstaRenderAjaxList();
        }
        if (!wf_CheckGet(array('showhash'))) {
        show_window(__('Upload'), $banksta->renderBankstaLoadForm());
        show_window(__('Previously loaded bank statements'), $banksta->renderBankstaList());
        } else {
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