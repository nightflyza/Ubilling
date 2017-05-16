<?php

if (cfr('ROOT')) {
    set_time_limit(0);
    $updateManager = new UbillingUpdateManager();

    if (!wf_CheckGet(array('applysql'))) {
        show_window(__('Update manager'), $updateManager->renderSqlDumpsList());
    } else {
        $releaseNum = $_GET['applysql'];
        show_window(__('MySQL database schema update'), $updateManager->applyMysqlDump($releaseNum));
    }
} else {
    show_error(__('Access denied'));
}
?>


