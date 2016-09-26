<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['EXHORSE_ENABLED']) {
    if (cfr('EXHORSE')) {
        
        $exhorse = new ExistentialHorse();
        if (wf_CheckPost(array('yearsel'))) {
            $exhorse->setYear($_POST['yearsel']);
        } else {
            $exhorse->setYear(date("Y"));
        }
        
        show_window(__('Existential horse'),$exhorse->renderReport());
        
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>