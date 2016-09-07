<?php

if (cfr('CONDET')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['CONDET_ENABLED']) {

        $conDet = new ConnectionDetails();
        //json reply
        if (wf_CheckGet(array('ajax'))) {
            $conDet->ajaxGetData();
        }

        //json reply for ukv report
        if (wf_CheckGet(array('ajaxukv'))) {
            $conDet->ajaxGetDataUkv();
        }

        //is UKV enabled?
        if ($altCfg['UKV_ENABLED']) {
            $reportControls = wf_Link('?module=report_condet', wf_img('skins/ymaps/globe.png') . ' ' . __('Internet'), false, 'ubButton');
            $reportControls.= wf_Link('?module=report_condet&ukv=true', wf_img('skins/ukv/tv.png') . ' ' . __('CaTV'), false, 'ubButton');
            show_window('', $reportControls);
        }


        if (!wf_CheckGet(array('ukv'))) {
            show_window(__('Connection details report'), $conDet->renderReportBody());
        } else {
            if ($altCfg['UKV_ENABLED']) {
                show_window(__('Connection details report') . ': ' . __('CaTV'), $conDet->renderReportBodyUkv());
            } else {
                show_error(__('This module is disabled'));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>

