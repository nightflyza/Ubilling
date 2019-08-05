<?php

//SMSZilla background automated sending
if ($_GET['action'] == 'smszilla') {
    if ($alterconf['SMSZILLA_ENABLED']) {
        if (wf_CheckGet(array('templateid', 'filterid'))) {
            $smszilla = new SMSZilla();
            $smszilla->filtersPreprocessing($_GET['filterid'], $_GET['templateid']);
            die('OK:SMSZILLA');
        } else {
            die('ERROR:NO_FILTER&TEMPLATE_ID');
        }
    } else {
        die('ERROR:SMSZILLA_DISABLED');
    }
}
