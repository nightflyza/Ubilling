<?php

if (cfr('ROOT')) {

  

    $updateManager = new UbillingUpdateManager();
} else {
    show_error(__('Access denied'));
}
?>


