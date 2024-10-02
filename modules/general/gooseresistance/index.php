<?php

if (cfr('GOOSE')) {
    if ($ubillingConfig->getAlterParam('GOOSE_RESISTANCE')) {
        $gr = new GRes();
        //new strat creation
        if (ubRouting::checkPost(array($gr::PROUTE_ST_CREATE, $gr::PROUTE_ST_NAME))) {
            $gr->createStrategy(
                ubRouting::post($gr::PROUTE_ST_NAME),
                ubRouting::post($gr::PROUTE_ST_ASSIGNS),
                ubRouting::post($gr::PROUTE_ST_AGENTID),
                ubRouting::post($gr::PROUTE_ST_TARIFF)
            );
            ubRouting::nav($gr::URL_ME);
        }

        //editing existing strategy
        if (ubRouting::checkPost(array($gr::PROUTE_ST_EDIT, $gr::PROUTE_ST_NAME))) {
            $gr->saveStrategy(
                ubRouting::post($gr::PROUTE_ST_EDIT),
                ubRouting::post($gr::PROUTE_ST_NAME),
                ubRouting::post($gr::PROUTE_ST_ASSIGNS),
                ubRouting::post($gr::PROUTE_ST_AGENTID),
                ubRouting::post($gr::PROUTE_ST_TARIFF)
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
                ubRouting::post($gr::PROUTE_SP_VALUE)
            );
            ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_EDIT . '=' . ubRouting::post($gr::PROUTE_SP_STRAT));
        }

        //existing spec editing
        if (ubRouting::checkPost(array($gr::PROUTE_SP_EDIT, $gr::PROUTE_SP_STRAT, $gr::PROUTE_SP_AGENT))) {
            $gr->saveSpec(
                ubRouting::post($gr::PROUTE_SP_EDIT),
                ubRouting::post($gr::PROUTE_SP_AGENT),
                ubRouting::post($gr::PROUTE_SP_TYPE),
                ubRouting::post($gr::PROUTE_SP_VALUE)
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

        //spec custom data field creation or replace
        if (ubRouting::checkPost(array($gr::PROUTE_CD_SPEC, $gr::PROUTE_CD_KEY))) {
            $gr->setCustDataField(
                ubRouting::post($gr::PROUTE_CD_SPEC),
                ubRouting::post($gr::PROUTE_CD_KEY),
                ubRouting::post($gr::PROUTE_CD_VAL)
            );
            ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_CUSTDATA . '=' . ubRouting::post($gr::PROUTE_CD_SPEC));
        }

        //spec custom data field deletion
        if (ubRouting::checkGet(array($gr::ROUTE_CD_DELKEY, $gr::ROUTE_SP_CUSTDATA))) {
            $gr->deleteCustDataField(
                ubRouting::get($gr::ROUTE_SP_CUSTDATA),
                ubRouting::get($gr::ROUTE_CD_DELKEY),
            );
            ubRouting::nav($gr::URL_ME . '&' . $gr::ROUTE_SP_CUSTDATA . '=' . ubRouting::get($gr::ROUTE_SP_CUSTDATA));
        }

        if (ubRouting::checkGet($gr::ROUTE_SP_EDIT)) {
            $stratId = ubRouting::get($gr::ROUTE_SP_EDIT, 'int');
            show_window(__('Strategy configuration') . ': ' . $gr->getStrategyName($stratId), $gr->renderStratSpecsList($stratId));
        } else {
            if (ubRouting::checkGet($gr::ROUTE_SP_CUSTDATA)) {
                show_window(__('Custom data'), $gr->renderCustomDataEditor(ubRouting::get($gr::ROUTE_SP_CUSTDATA, 'int')));
            } else {
                show_window(__('Available strategies'), $gr->renderStrategiesList());
                //strategies testing here
                if (ubRouting::checkPost($gr::PROUTE_CH_USER)) {
                    $checkUserLogin = ubRouting::post($gr::PROUTE_CH_USER, 'login');
                    $checkIncomeAmount = (ubRouting::checkPost($gr::PROUTE_CH_AMOUNT)) ? ubRouting::post($gr::PROUTE_CH_AMOUNT) : 0;
                    $checkExplictStratId = (ubRouting::checkPost($gr::PROUTE_CH_STRAT)) ? ubRouting::post($gr::PROUTE_CH_STRAT) : 0;
                    $gr->setUserLogin($checkUserLogin);
                    $gr->setAmount($checkIncomeAmount);
                    $checkStrategyData = $gr->getStrategyData($checkExplictStratId);
                    show_window(__('Result'), $gr->renderStratTestingResults($checkStrategyData));
                }
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
