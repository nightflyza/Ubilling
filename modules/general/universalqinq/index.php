<?php

if (cfr('UNIVERSALQINQCONFIG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['UNIVERSAL_QINQ_ENABLED']) {
        $qinq = new UniversalQINQ();

        if ($qinq->routing->checkGet('ajax')) {
            $qinq->ajaxData();
        }

        $qinq->addForm();
        $qinq->showAll();
    }
}
