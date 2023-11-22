<?php

if (ubRouting::get('action') == 'pseudocrm') {
    if (@$alterconf['PSEUDOCRM_ENABLED']) {
        $pseudoCrmResult = 'NONE';
        $pseudoCrmActionCall = ubRouting::get('param');
        if ($pseudoCrmActionCall) {
            $crm = new PseudoCRM();
            switch ($pseudoCrmActionCall) {
                //open activities notification
                case 'openactnotify':
                    $crm->notifyOpenActivities();
                    break;
            }
            $pseudoCrmResult = $pseudoCrmActionCall;
        }
        die('OK:' . $pseudoCrmResult);
    } else {
        die('ERROR:PSEUDOCRM_DISABLED');
    }
}