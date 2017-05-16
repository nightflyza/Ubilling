<?php

if (cfr('ROOT')) {
    $updateManager = new UbillingUpdateManager();

    if (!wf_CheckGet(array('applysql'))) {
        show_window(__('Update manager'), $updateManager->renderSqlDumpsList());
    } else {
        $releaseNum = $_GET['applysql'];
        show_window(__('Test'), $updateManager->applyMysqlDump($releaseNum));
    }
} else {
    show_error(__('Access denied'));
}
?>


