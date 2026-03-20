<?php

$result = '';

if ($darkVoidContext['altCfg']['TB_UBIM']) {
    if (cfr('UBIM')) {
        $ubIm = new UBMessenger();
        $unreadMessageCount = $ubIm->checkForUnreadMessages();
        if ($unreadMessageCount) {
            $result .= wf_Link($ubIm::URL_ME . '&' . $ubIm::ROUTE_REFRESH . '=true', wf_img("skins/ubim_blink.gif", $unreadMessageCount . ' ' . __('new message received')), false, '');
        }
    }
}

return ($result);
