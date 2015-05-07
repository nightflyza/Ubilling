<?php

if (cfr('ONLINE')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['CONDET_ENABLED']) {

        $conDet = new ConnectionDetails();
        //json reply
        if (wf_CheckGet(array('ajax'))) {
            die($conDet->ajaxGetData());
        }


        show_window(__('Connection details report'), $conDet->renderReportBody());
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>

