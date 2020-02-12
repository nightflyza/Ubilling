<?php
/*
 * Virtualservices charge fee
 */

if (ubRouting::checkGet('action') and ubRouting::get('action') == 'vserviceschargefee') {
    $vservicesChargeFrozen = (ubRouting::get('param') == 'nofrozen') ? false : true;
    $vservicesChargePeriod = '';

    if (ubRouting::checkGet('period')) {
        $periodFilter = str_ireplace('_', ',', ubRouting::get('period'));
        $vservicesChargePeriod = " WHERE `charge_period_days` IN (" . $periodFilter . ")";
    }

    log_register("REMOTEAPI VSERVICE_CHARGE_FEE STARTED");

    zb_VservicesProcessAll(true, $vservicesChargeFrozen, $vservicesChargePeriod);

    log_register("REMOTEAPI VSERVICE_CHARGE_FEE FINISHED");
    die('OK:SERVICE_CHARGE_FEE');
}