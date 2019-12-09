<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['ITSATRAP_ENABLED']) {
    if (cfr('ITSATRAP')) {
        $itsatrap = new ItSaTrap();

        //saving new data source and lines limit
        if (ubRouting::checkPost('newdatasource', false)) {
            $itsatrap->saveBasicConfig();
            ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
        }

        //new trap type creation
        if (ubRouting::checkPost(array('newname', 'newmatch', 'newcolor'))) {
            $itsatrap->createTrapType();
            ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
        }

        //existing trap editing
        if (ubRouting::checkPost(array('edittraptypeid', 'editname', 'editmatch', 'editcolor'))) {
            $itsatrap->saveTrapType();
            ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
        }

        //trap type deletion
        if (ubRouting::checkGet('deletetrapid')) {
            $deletionResult = $itsatrap->deleteTrapType(ubRouting::get('deletetrapid', 'int'));
            if (empty($deletionResult)) {
                ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
            } else {
                show_error($deletionResult);
            }
        }

        show_window(__('Configuration'), $itsatrap->renderConfigForm());
        show_window(__('Available SNMP trap types'), $itsatrap->renderTrapTypesList());
        show_window('', $itsatrap->renderTrapCreateForm());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

