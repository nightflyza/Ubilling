<?php

//warehouse daily reserve remains notification
if ($_GET['action'] == 'warehousetelegram') {
    if ($alterconf['WAREHOUSE_ENABLED']) {
        $warehouse = new Warehouse();
        $warehouse->telegramReserveDailyNotify();
        die('OK: WAREHOUSETELEGRAM');
    } else {
        die('ERROR: WAREHOUSE DISABLED');
    }
}
