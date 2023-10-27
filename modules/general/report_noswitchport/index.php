<?php
if (cfr('REPORTNOSWPORT')) {

    /*
     * controller and view section
     */

    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['SWITCHPORT_IN_PROFILE']) {
        $noSwitchPortReport = new SwitchPortReport();
        show_window(__('Users without port assigned'), $noSwitchPortReport->renderNoSwitchPort());
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
