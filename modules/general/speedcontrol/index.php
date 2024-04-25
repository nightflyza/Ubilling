<?php
if (cfr('SPEEDCONTROL')) {
    
    $speedControlReport = new SpeedControl();

    //dropping speed override if required
    if (ubRouting::checkGet($speedControlReport::ROUTE_FIX)) {
        $userLogin = ubRouting::get($speedControlReport::ROUTE_FIX);
        $speedControlReport->dropOverride($userLogin);
        ubRouting::nav($speedControlReport::URL_ME);
    }

    //rendering report itself
    show_window(__('Users with speed overrides'), $speedControlReport->render());
} else {
    show_error(__('You cant control this module'));
}
