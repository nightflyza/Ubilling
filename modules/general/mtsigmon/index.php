<?php

if (cfr('MTSIGMON')) {

// Main code part

    $alter_config = $ubillingConfig->getAlter();
    if ($alter_config['MTSIGMON_ENABLED']) {

        $sigmon = new MTsigmon();

        // force MT polling
        if (wf_CheckGet(array('forcepoll'))) {
            $sigmon->MTDevicesPolling(true);
            if (wf_CheckGet(array('username'))) {
                 rcms_redirect($sigmon::URL_ME . '&username='. vf($_GET['username']));
            } else {
                rcms_redirect($sigmon::URL_ME);
            }
        }

        // getting MT json data for list
        if (wf_CheckGet(array('ajaxmt', 'mtid'))) {
            $sigmon->renderMTsigmonList(vf($_GET['mtid'], 3));
        }

        // rendering availavle MT LIST
        show_window(__('Mikrotik signal monitor'), $sigmon->controls());
        $sigmon->renderMTList();


    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>