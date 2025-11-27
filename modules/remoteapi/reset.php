<?php

/**
 * reset user action
 */
if ($_GET['action'] == 'reset') {
    if (isset($_GET['param'])) {
        $billing->resetuser($_GET['param']);
        log_register("REMOTEAPI RESET (" . $_GET['param'] . ")");
        //may be user ressurection required?
        if (@$alterconf['RESETHARD']) {
            zb_UserResurrect($_GET['param']);
        }
        die('OK:RESET');
    } else {
        die('ERROR:GET_NO_PARAM');
    }
}