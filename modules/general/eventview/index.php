<?php

if (cfr('EVENTVIEW')) {
    $eventView = new EventView();
    
    //primary module controls here
    show_window('', $eventView->renderControls());

    if (!ubRouting::checkGet($eventView::ROUTE_STATS)) {
        if (ubRouting::checkGet($eventView::ROUTE_ZEN)) {
            $eventZen=new ZenFlow('ajeventlog', $eventView->renderEventsReport());
            show_window(__('Zen'), $eventZen->render());
        } else {
            show_window(__('Last events'), $eventView->renderEventsReport());
        }
    } else {
        show_window(__('Month actions stats'), $eventView->renderEventStats());
    }
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
?>
