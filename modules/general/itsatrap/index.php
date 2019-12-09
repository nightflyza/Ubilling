<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['ITSATRAP_ENABLED']) {
    if (cfr('ITSATRAP')) {
        $itsatrap = new ItSaTrap();

        //saving new data source
        if (ubRouting::checkPost('newdatasource', false)) {
            $itsatrap->saveBasicConfig();
            ubRouting::nav($itsatrap::URL_ME);
        }

        deb($itsatrap->renderConfigForm());
        
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

