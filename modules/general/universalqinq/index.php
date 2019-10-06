<?php

if (cfr('UNIVERSALQINQCONFIG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['UNIVERSAL_QINQ_ENABLED']) {
        $qinq = new UniversalQINQ();

        if ($qinq->routing->checkGet('ajax')) {
            $qinq->ajaxData();
        }
        switch ($qinq->routing->get('action')) {
            case 'delete':
                $qinq->delete();
                break;
            case 'edit':
                $qinq->edit();
                break;
            case 'add':
                $qinq->add();
                break;
            case'realm_id_select':
                die($qinq->svlanSelector($qinq->routing->get('ajrealmid')));
                break;
        }

        $qinq->addForm();
        $qinq->showAll();
    }
}
