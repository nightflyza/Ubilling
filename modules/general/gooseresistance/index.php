<?php

if (cfr('GOOSE')) {
    if ($ubillingConfig->getAlterParam('GOOSE_RESISTANCE')) {
        $gr = new GRes();
        //new strat creation
        if (ubRouting::checkPost(array($gr::PROUTE_ST_CREATE, $gr::PROUTE_ST_NAME))) {
            $gr->createStrategy(
                ubRouting::post($gr::PROUTE_ST_NAME),
                ubRouting::post($gr::PROUTE_ST_ASSIGNS),
                ubRouting::post($gr::PROUTE_ST_AGENTID)
            );
            ubRouting::nav($gr::URL_ME);
        }

        //editing existing strategy
        if (ubRouting::checkPost(array($gr::PROUTE_ST_EDIT, $gr::PROUTE_ST_NAME))) {
            $gr->saveStrategy(
                ubRouting::post($gr::PROUTE_ST_EDIT),
                ubRouting::post($gr::PROUTE_ST_NAME),
                ubRouting::post($gr::PROUTE_ST_ASSIGNS),
                ubRouting::post($gr::PROUTE_ST_AGENTID)
            );
            ubRouting::nav($gr::URL_ME);
        }

        //deleting existing strategy
        if (ubRouting::checkGet($gr::ROUTE_ST_DELETE)) {
            $gr->deleteStrategy(ubRouting::get($gr::ROUTE_ST_DELETE));
            ubRouting::nav($gr::URL_ME);
        }

        //new spec creation
        if (ubRouting::checkPost(array($gr::PROUTE_SP_CREATE, $gr::PROUTE_SP_STRAT, $gr::PROUTE_SP_AGENT))) {
            $gr->createSpec(
                ubRouting::post($gr::PROUTE_SP_STRAT),
                ubRouting::post($gr::PROUTE_SP_AGENT),
                ubRouting::post($gr::PROUTE_SP_TYPE),
                ubRouting::post($gr::PROUTE_SP_VALUE),
                ubRouting::post($gr::PROUTE_SP_CUSTDATA)
            );
            ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_EDIT . '=' . ubRouting::post($gr::PROUTE_SP_STRAT));
        }

        //existing spec editing
        if (ubRouting::checkPost(array($gr::PROUTE_SP_EDIT, $gr::PROUTE_SP_STRAT, $gr::PROUTE_SP_AGENT))) {
            $gr->saveSpec(
                ubRouting::post($gr::PROUTE_SP_EDIT),
                ubRouting::post($gr::PROUTE_SP_AGENT),
                ubRouting::post($gr::PROUTE_SP_TYPE),
                ubRouting::post($gr::PROUTE_SP_VALUE),
                ubRouting::post($gr::PROUTE_SP_CUSTDATA)
            );
            ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_EDIT . '=' . ubRouting::post($gr::PROUTE_SP_STRAT));
        }

        //spec deletion
        if (ubRouting::checkGet($gr::ROUTE_SP_DELETE)) {
            $gr->deleteSpec(ubRouting::get($gr::ROUTE_SP_DELETE));
            if (ubRouting::checkGet($gr::ROUTE_SP_EDIT)) {
                ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_EDIT . '=' . ubRouting::get($gr::ROUTE_SP_EDIT));
            } else {
                ubRouting::nav($gr::URL_ME);
            }
        }


        if (ubRouting::checkGet($gr::ROUTE_SP_EDIT)) {
            $stratId = ubRouting::get($gr::ROUTE_SP_EDIT, 'int');
            show_window(__('Strategy configuration') . ': ' . $gr->getStrategyName($stratId), $gr->renderStratSpecsList($stratId));
        } else {
            show_window(__('Available strategies'), $gr->renderStrategiesList());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
