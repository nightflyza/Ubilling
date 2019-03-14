<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $messages = new UbillingMessageHelper();

    if (wf_CheckGet(array('checkupdates'))) {
        $latestRelease = zb_BillingCheckUpdates(true);
        die($messages->getStyledMessage($latestRelease, 'success'));
    }

    $updateManager = new UbillingUpdateManager();

    if ((!wf_CheckGet(array('applysql'))) AND ( !wf_CheckGet(array('showconfigs')))) {
        //updates check
        show_window('', $updateManager->renderVersionInfo());

        //available updates lists render
        show_window(__('MySQL database schema updates'), $updateManager->renderSqlDumpsList());
        show_window(__('Configuration files updates'), $updateManager->renderConfigsList());
    } else {
        //mysql dumps applying interface
        if (wf_CheckGet(array('applysql'))) {
            $releaseNum = $_GET['applysql'];
            show_window(__('MySQL database schema update'), $updateManager->applyMysqlDump($releaseNum));
        }

        if (wf_CheckGet(array('showconfigs'))) {
            $releaseNum = $_GET['showconfigs'];
            show_window(__('Configuration files updates'), $updateManager->applyConfigOptions($releaseNum));
        }
    }
} else {
    show_error(__('Access denied'));
}
?>


