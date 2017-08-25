<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['JUNGEN_ENABLED']) {
    if (cfr('JUNGEN')) {
        $junAcct = new JunAcct();
        if (wf_CheckGet(array('dljungenlog'))) {
            $junAcct->logDownload();
        }
        $dateFormControls = $junAcct->renderDateSerachControls();
        show_window(__('Juniper NAS sessions stats') . ' ' . $junAcct->renderLogControl(), $dateFormControls . $junAcct->renderAcctStats());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>