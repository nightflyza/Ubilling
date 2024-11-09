<?php
if (cfr('UBIM')) {
    if (cfr('ROOT')) {
        if (ubRouting::checkGet('flushavacache')) {
            zb_avatarFlushCache();
            if (ubRouting::checkGet('back')) {
                ubRouting::nav(UBMessenger::URL_AVATAR_CONTROL . '&back=' . ubRouting::get('back'));
            } else {
                ubRouting::nav(UBMessenger::URL_AVATAR_CONTROL);
            }
        }
    }

    show_window(__('Avatar control'), web_avatarControlForm(ubRouting::get('back')));
    zb_BillingStats();
} else {
    show_error(__('Access denied'));
}
