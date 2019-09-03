<?php

if (cfr('UNIVERSALQINQCONFIG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['UNIVERSAL_QINQ_ENABLED']) {
        $qinq = new UniversalQINQ();
        $qinq->showAll();
    }
}
