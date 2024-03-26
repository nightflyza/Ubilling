<?php

//districts cache update
if (ubRouting::get('action') == 'districtscache') {
    if ($alterconf['DISTRICTS_ENABLED']) {
        $districts = new Districts(true);
        $districts->fillDistrictsCache();
        die('OK: DISTRICTSCACHE');
    } else {
        die('ERROR: DISTRICTS DISABLED');
    }
}
