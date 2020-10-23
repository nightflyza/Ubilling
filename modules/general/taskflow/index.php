<?php

if (cfr('TASKFLOW')) {
    if ($ubillingConfig->getAlterParam('TASKSTATES_ENABLED')) {
        $taskFlow = new TaskFlow();

        //Search form rendering
        show_window(__('Task flow'), $taskFlow->renderControls());

        //Do some fucking search!
        if (ubRouting::checkPost($taskFlow::PROUTE_STARTSEARCH)) {
            show_window(__('Search results'), $taskFlow->performSearch());
        } else {
            $randomAdvice = $taskFlow->getAwesomeAdvice();
            $randomAdvice= zb_TranslitString($randomAdvice); // AUFFFF
            if (!empty($randomAdvice)) {
                show_info(zb_TranslitString(__('Advice of the day')).': '.$randomAdvice);
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
