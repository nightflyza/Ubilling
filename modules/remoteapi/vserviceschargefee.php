<?php

/*
 * Virtualservices charge fee
 */

if ($_GET['action'] == 'vserviceschargefee') {
    if (wf_CheckGet(array('param'))) {
        if ($_GET['param'] == 'nofrozen') {
            $vservicesChargeFrozen = false;
        } else {
            $vservicesChargeFrozen = true;
        }
    } else {
        $vservicesChargeFrozen = true;
    }

    zb_VservicesProcessAll(true, $vservicesChargeFrozen);
    log_register("REMOTEAPI VSERVICE_CHARGE_FEE");
    die('OK:SERVICE_CHARGE_FEE');
}