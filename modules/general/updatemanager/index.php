<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $messages = new UbillingMessageHelper();

    if (ubRouting::checkGet('checkupdates')) {
        $latestRelease = zb_BillingCheckUpdates(true);
        die($messages->getStyledMessage($latestRelease, 'success'));
    }

    $updateManager = new UbillingUpdateManager();

    if (!ubRouting::checkGet('applysql') AND ! ubRouting::checkGet('showconfigs')) {
        //updates check
        show_window('', $updateManager->renderVersionInfo());

        //available updates lists render
        show_window(__('MySQL database schema updates'), $updateManager->renderSqlDumpsList());
        show_window(__('Configuration files updates'), $updateManager->renderConfigsList());
    } else {
        //mysql dumps applying interface
        if (ubRouting::checkGet('applysql')) {
            show_window(__('MySQL database schema update'), $updateManager->applyMysqlDump(ubRouting::get('applysql')));
        }

        if (ubRouting::checkGet('showconfigs')) {
            show_window(__('Configuration files updates'), $updateManager->applyConfigOptions(ubRouting::get('showconfigs')));
        }
    }
} else {
    show_error(__('Access denied'));
}



