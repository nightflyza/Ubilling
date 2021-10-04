<?php

if (cfr('REPORTSIGNUP') AND cfr('USERSMAP')) {

    $sigmap = new SigMap();

    show_window(__('Filters'), $sigmap->renderDateForm());
    show_window(__('Signups map'), $sigmap->renderMap());
    show_window('', $sigmap->renderStats());
    show_window('', wf_BackLink('?module=report_signup'));
} else {
    show_error(__('Access denied'));
}
?>