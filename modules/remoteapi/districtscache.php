<?php

//districts cache update
if ($_GET['action'] == 'districtscache') {
    if ($alterconf['DISTRICTS_ENABLED']) {
        $districts = new Districts(true);
        $districts->fillDistrictsCache();
        die('OK: DISTRICTSCACHE');
    } else {
        die('ERROR: DISTRICTS DISABLED');
    }
}
