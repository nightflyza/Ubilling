<?php
if (cfr('REPORTSWPORT')) {
	
    $switchPortAssignReport = new SwitchPortAssign();

    //getting polls data
    if (ubRouting::checkGet('ajaxswitchassign')) {
       $switchPortAssignReport->loadUsersData();
       $switchPortAssignReport->ajaxAvaibleSwitchPortAssign();
    }

    /*
     * controller and view section
     */

    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['SWITCHPORT_IN_PROFILE']) {
        show_window(__('Switch port assign'), $switchPortAssignReport->renderSwitchPortAssign());
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
