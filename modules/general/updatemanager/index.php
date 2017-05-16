<?php

if (cfr('ROOT')) {
    $updateManager = new UbillingUpdateManager();

    if (!wf_CheckGet(array('applysql'))) {
        show_window(__('Update manager'), $updateManager->renderSqlDumpsList());
    } else {
        //dump applying here
    }
} else {
    show_error(__('Access denied'));
}
?>


