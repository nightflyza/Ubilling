<?php

if (cfr('UBIM')) {
    $ubIm = new UBMessenger();

    $threadContent = '';

    //pinning or unpinning contacts
    if (ubRouting::checkGet($ubIm::ROUTE_PIN)) {
        $ubIm->pinContact(ubRouting::get($ubIm::ROUTE_PIN));
        ubRouting::nav($ubIm::URL_ME . '&' . $ubIm::ROUTE_THREAD . '=' . ubRouting::get($ubIm::ROUTE_PIN));
    }
    if (ubRouting::checkGet($ubIm::ROUTE_UNPIN)) {
        $ubIm->unpinContact(ubRouting::get($ubIm::ROUTE_UNPIN));
        ubRouting::nav($ubIm::URL_ME . '&' . $ubIm::ROUTE_THREAD . '=' . ubRouting::get($ubIm::ROUTE_UNPIN));
    }

    //thread data filling
    if (ubRouting::checkGet($ubIm::ROUTE_THREAD)) {
        $threadToRender = ubRouting::get($ubIm::ROUTE_THREAD);
        $threadContent .= $ubIm->renderConversationForm($threadToRender);
        $threadContent .= $ubIm->renderZenThread($threadToRender);
    }

    //creating new message 
    if (ubRouting::checkPost(array($ubIm::PROUTE_MSG_TO, $ubIm::PROUTE_MSG_TEXT))) {
        $ubIm->createMessage(ubRouting::post($ubIm::PROUTE_MSG_TO), ubRouting::post($ubIm::PROUTE_MSG_TEXT));
        ubRouting::nav($ubIm::URL_ME . '&' . $ubIm::ROUTE_GOTHREAD . '=' . ubRouting::post($ubIm::PROUTE_MSG_TO));
    }

    // direct link thread loader
    if (ubRouting::checkGet($ubIm::ROUTE_GOTHREAD)) {
        ubRouting::nav($ubIm::URL_ME . '&' . $ubIm::ROUTE_THREAD . '=' . ubRouting::get($ubIm::ROUTE_GOTHREAD));
    }

    $windowTitle = $ubIm->renderMainWinTitle();
    show_window($windowTitle, $ubIm->renderMainWindow($threadContent));

    if (!ubRouting::checkGet($ubIm::ROUTE_THREAD) and !ubRouting::checkGet($ubIm::ROUTE_GOTHREAD)) {
        //update notification area
        if (ubRouting::checkGet($ubIm::ROUTE_REFRESH) and !ubRouting::checkGet('zenflow')) {
            $darkVoid = new DarkVoid();
            $darkVoid->flushCache();
        }
        zb_BillingStats(true, 'ubimng');
    }
} else {
    show_error(__('You cant control this module'));
}
