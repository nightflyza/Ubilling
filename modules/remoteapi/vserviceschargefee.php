<?php

/**
 * Virtualservices charge fee
 */
if (ubRouting::get('action') == 'vserviceschargefee') {
    $vservicesChargeFrozen = (ubRouting::get('param') == 'nofrozen') ? false : true;
    $vservicesChargePeriod = '';

    if (ubRouting::checkGet('period')) {
        $periodFilter = str_ireplace('_', ',', ubRouting::get('period'));
        $vservicesChargePeriod = " `charge_period_days` IN (" . $periodFilter . ")";
    }
    $regularPayments = true;
    if (isset($alterconf['VSERVICES_AS_PAYMENTS'])) {
        if ($alterconf['VSERVICES_AS_PAYMENTS']) {
            $regularPayments = true;
        } else {
            $regularPayments = false;
        }
    }
    log_register('REMOTEAPI VSERVICE_CHARGE_FEE STARTED');
    zb_VservicesProcessAll($regularPayments, $vservicesChargeFrozen, $vservicesChargePeriod);
    log_register('REMOTEAPI VSERVICE_CHARGE_FEE FINISHED');
    die('OK:SERVICE_CHARGE_FEE');
}