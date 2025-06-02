<?php

if ($system->checkForRight('ONLINE')) {
    $altCfg = $ubillingConfig->getAlter();
    $onlineHpMode = $altCfg['ONLINE_HP_MODE'];
    if ($onlineHpMode) {
        //turbo mode
        if ($onlineHpMode == 3) {
            $tUsList = new TurboUsersList();
            if (ubRouting::checkGet('ajax')) {
                $tUsList->jsonUserList();
            }
            $usersListContainer = $tUsList->renderUsersListContainer();
        } else {
            $usersListContainer = web_OnlineRenderUserListContainer();
            if (ubRouting::checkGet('ajax')) {

                //default rendering
                if ($onlineHpMode == 1) {
                    die(zb_AjaxOnlineDataSourceSafe());
                }

                //fast with caching, for huge databases.
                if ($onlineHpMode == 2) {
                    $defaultJsonCacheTime = 600;
                    $onlineJsonCache = new UbillingCache();
                    $fastJsonReply = $onlineJsonCache->getCallback('HPONLINEJSON', function () {
                        return (zb_AjaxOnlineDataSourceSafe());
                    }, $defaultJsonCacheTime);
                    die($fastJsonReply);
                }
            }
        }

        show_window(__('Users'), $usersListContainer);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
