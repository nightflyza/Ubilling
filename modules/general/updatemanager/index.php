<?php

if (cfr('ROOT')) {
    $updateManager = new UbillingUpdateManager();

    show_window(__('Update manager'), $updateManager->renderSqlDumpsList());
    
} else {
    show_error(__('Access denied'));
}
?>


