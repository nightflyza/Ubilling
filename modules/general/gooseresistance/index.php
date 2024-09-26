<?php

if (cfr('GOOSE')) {
    if ($ubillingConfig->getAlterParam('GOOSE_RESISTANCE')) {
        $gr = new GRes();
        //new strat creation
        if (ubRouting::checkPost(array($gr::PROUTE_ST_CREATE,$gr::PROUTE_ST_NAME))) {
            $gr->createStrategy(
                ubRouting::post($gr::PROUTE_ST_NAME),
                ubRouting::post($gr::PROUTE_ST_ASSIGNS),
                ubRouting::post($gr::PROUTE_ST_AGENTID)
        );
            ubRouting::nav($gr::URL_ME);
        }

        //editing existing strategy
        if (ubRouting::checkPost(array($gr::PROUTE_ST_EDIT,$gr::PROUTE_ST_NAME))) {
            
        }
        show_window(__('Available strategies'), $gr->renderStrategiesList());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
