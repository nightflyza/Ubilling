<?php

if (cfr('TASKFLOW')) {
    if ($ubillingConfig->getAlterParam('TASKSTATES_ENABLED')) {
        $taskFlow = new TaskFlow();

        if (ubRouting::checkGet($taskFlow::ROUTE_EMREPORT)) {
            //Employee report rendering
            $windowControls = wf_Link($taskFlow::URL_ME, web_icon_search('Task flow'));
            show_window(__('By date') . ' ' . $windowControls, $taskFlow->renderEmployeeReportForm());
            show_window(__('Report'), $taskFlow->renderEmployeeReport());
        } else {
            //Search form rendering
            $windowControls = wf_Link($taskFlow::URL_ME . '&' . $taskFlow::ROUTE_EMREPORT . '=true', web_icon_charts('Report'));
            show_window(__('Task flow') . ' ' . $windowControls, $taskFlow->renderControls());
        }

        //Do some fucking search!
        if (ubRouting::checkPost($taskFlow::PROUTE_STARTSEARCH)) {
            show_window(__('Search results'), $taskFlow->performSearch());
        } else {
            if (!ubRouting::checkGet($taskFlow::ROUTE_EMREPORT)) {
                $randomAdvice = $taskFlow->getAwesomeAdvice();
                $randomAdvice = zb_TranslitString($randomAdvice); // AUFFFF
                if (!empty($randomAdvice)) {
                    show_info(zb_TranslitString(__('Advice of the day')) . ': ' . $randomAdvice);
                }
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
