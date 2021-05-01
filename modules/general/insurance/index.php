<?php

if (cfr('INSURANCE')) {
    $insuranceEnabled = $ubillingConfig->getAlterParam('INSURANCE_ENABLED');
    if ($insuranceEnabled) {
        $insurance = new Insurance();

        //rendering home insurance requests list
        if (ubRouting::checkGet($insurance::ROUTE_AJHINSLIST)) {
            $insurance->ajHinsList();
        }

        //sets home insurance request as done if required
        if (ubRouting::checkGet($insurance::ROUTE_HINSDONE)) {
            $hinsReqId = ubRouting::get($insurance::ROUTE_HINSDONE);
            $insurance->setHinsDone($hinsReqId);
            ubRouting::nav($insurance::URL_ME . '&' . $insurance::ROUTE_VIEWHINSREQ . '=' . $hinsReqId);
        }

        //sets home insurance request as undone if required
        if (ubRouting::checkGet($insurance::ROUTE_HINSUNDONE)) {
            $hinsReqId = ubRouting::get($insurance::ROUTE_HINSUNDONE);
            $insurance->setHinsUnDone($hinsReqId);
            ubRouting::nav($insurance::URL_ME . '&' . $insurance::ROUTE_VIEWHINSREQ . '=' . $hinsReqId);
        }

        //some viewers here
        if (!ubRouting::checkGet($insurance::ROUTE_VIEWHINSREQ)) {
            show_window(__('Home insurance requests'), $insurance->renderHinsRequestsList());
        } else {
            $viewHinsId = ubRouting::get($insurance::ROUTE_VIEWHINSREQ);
            show_window(__('Request') . ': ' . $viewHinsId, $insurance->renderHinsRequest($viewHinsId));
        }
    } else {
        show_error(__('This module is disabled'));
    }
}
