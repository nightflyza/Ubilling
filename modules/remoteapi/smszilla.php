<?php

//SMSZilla background automated sending
if (ubRouting::get('action') == 'smszilla') {
    if ($alterconf['SMSZILLA_ENABLED']) {
        if (ubRouting::checkGet(array('templateid', 'filterid'))) {
            $smszilla = new SMSZilla();
            $smszilla->filtersPreprocessing(ubRouting::get('filterid'), ubRouting::get('templateid'));
            die('OK:SMSZILLA');
        } else {
            die('ERROR:NO_FILTER_OR_TEMPLATE_ID');
        }
    } else {
        die('ERROR:SMSZILLA_DISABLED');
    }
}
