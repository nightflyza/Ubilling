<?php

//ClapTrap background automated sending
if (ubRouting::get('action') == 'ctzilla') {
    if ($alterconf['CLAPTRAPBOT_ENABLED']) {
        if (ubRouting::checkGet(array('filterid', 'templateid'))) {
            $clapTrapMgr = new ClapTrapMgr();
            $clapTrapMgr->filtersPreprocessing(ubRouting::get('filterid'), ubRouting::get('templateid'));
            die('OK:CLAPTRAPMGR');
        } else {
            die('ERROR:NO_FILTER_OR_TEMPLATE_ID');
        }   
    } else {
        die('ERROR:CLAPTRAPMGR_DISABLED');
    }
}
