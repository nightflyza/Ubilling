<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['TELEPONY_ENABLED']) {



    if (cfr('TELEPONY')) {
        $telePony = new TelePony();

        //showing call history form
        if ($altCfg['TELEPONY_CDR']) {
            show_window(__('Calls history'), $telePony->renderCdrDateForm());
        }

        //basic calls stats
        show_window(__('Stats') . ' ' . __('Incoming calls'), $telePony->renderNumLog());



        //rendering calls history here
        if ($altCfg['TELEPONY_CDR']) {
            if (ubRouting::checkPost(array('datefrom', 'dateto'))) {
                show_window(__('TelePony') . ' - ' . __('Calls history'), $telePony->renderCDR());
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

