<?php

if (cfr('EVENTVIEW')) {
    $eventView = new EventView();
    //primary module controls here
    show_window('', $eventView->renderControls());

    if (!ubRouting::checkGet($eventView::ROUTE_STATS)) {
        show_window(__('Last events'), $eventView->renderEventsReport());
        zb_BillingStats(true);
    } else {
        show_window(__('Month actions stats'), $eventView->renderEventStats());
    }
} else {
    show_error(__('Access denied'));
}
?>
