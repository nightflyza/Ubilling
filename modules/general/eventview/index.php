<?php

if (cfr('EVENTVIEW')) {
    $eventView = new EventView();

    //primary module controls here
    show_window('', $eventView->renderControls());
        if (ubRouting::checkGet($eventView::ROUTE_ZEN)) {
            $eventZen = new ZenFlow('ajeventlog', $eventView->renderEventsReport());
            $zenProfilesSwitch = '';
            if (ubRouting::checkGet($eventView::ROUTE_ZENPROFILES)) {
                $zenFullLink = $eventView::URL_ME . '&' . $eventView::ROUTE_ZEN . '=true';
                $zenProfilesSwitch = wf_Link($zenFullLink,  wf_img_sized('skins/zenprofile_off.png',__('Disable')));
            } else {
                $zenFullLink = $eventView::URL_ME . '&' . $eventView::ROUTE_ZEN . '=true&' . $eventView::ROUTE_ZENPROFILES . '=true';
                $zenProfilesSwitch = wf_Link($zenFullLink, wf_img_sized('skins/zenprofile_on.png',__('Highlight profiles')));
            }

            show_window(__('Zen') . ' ' . $zenProfilesSwitch, $eventZen->render());
        } else {
            show_window(__('Last events'), $eventView->renderEventsReport());
        }
    
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}

