<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['UNIVERSAL_QINQ_ENABLED']) {
    if (cfr('UNIVERSALQINQCONFIG')) {
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
            case 'realm_id_select':
                die($qinq->svlanSelector());
                break;
            case 'ajaxedit':
                die($qinq->editFormGenerator($qinq->routing->post('universal_encode')));
                break;
        }

        $qinq->links();
        $qinq->showAll();
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
