<?php

/*
 * reset user action
 */
if ($_GET['action'] == 'reset') {
    if (isset($_GET['param'])) {
        $billing->resetuser($_GET['param']);
        log_register("REMOTEAPI RESET User (" . $_GET['param'] . ")");
        if ($alterconf['JUNGEN_ENABLED']) {
            $junGen = new JunGen;
            $junGen->totalRegeneration();
            log_register("JUNGEN UHW REGENERATION (" . $_GET['param'] . ")");
            print('OK:JUNGEN' . "\n");
        }
        //may be user ressurection required?
        if (@$alterconf['RESETHARD']) {
            zb_UserResurrect($_GET['param']);
        }
        die('OK:RESET');
    } else {
        die('ERROR:GET_NO_PARAM');
    }
}