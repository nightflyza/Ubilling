<?php

if (cfr(PseudoCRM::RIGHT_VIEW)) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PSEUDOCRM_ENABLED']) {
        $crm = new PseudoCRM();
        //some module controls
        show_window('', $crm->renderPanel());

        //rendering existing leads ajax data
        if (ubRouting::checkGet($crm::ROUTE_LEADS_LIST_AJ)) {
            $crm->ajLeadsList();
        }

        //new lead creation
        if (cfr($crm::RIGHT_LEADS)) {
            if (ubRouting::checkPost($crm::PROUTE_LEAD_CREATE)) {
                if (ubRouting::checkPost(array($crm::PROUTE_LEAD_ADDR, $crm::PROUTE_LEAD_NAME, $crm::PROUTE_LEAD_MOBILE, $crm::PROUTE_LEAD_NOTES))) {
                    $address = ubRouting::post($crm::PROUTE_LEAD_ADDR);
                    $realname = ubRouting::post($crm::PROUTE_LEAD_NAME);
                    $phone = ubRouting::post($crm::PROUTE_LEAD_PHONE);
                    $mobile = ubRouting::post($crm::PROUTE_LEAD_MOBILE);
                    $extmobile = ubRouting::post($crm::PROUTE_LEAD_EXTMOBILE);
                    $email = ubRouting::post($crm::PROUTE_LEAD_EMAIL);
                    $branch = ubRouting::post($crm::PROUTE_LEAD_BRANCH);
                    $tariff = ubRouting::post($crm::PROUTE_LEAD_TARIFF);
                    $login = ubRouting::post($crm::PROUTE_LEAD_LOGIN);
                    $employeeid = ubRouting::post($crm::PROUTE_LEAD_EMPLOYEE);
                    $notes = ubRouting::post($crm::PROUTE_LEAD_NOTES);

                    $leadCreationResult = $crm->createLead($address, $realname, $phone, $mobile, $extmobile, $email, $branch, $tariff, $login, $employeeid, $notes);
                    ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_LEAD_PROFILE . '=' . $leadCreationResult);
                } else {
                    show_error(__('All fields marked with an asterisk are mandatory'));
                }
            }
        }

        //existing lead editing
        if (cfr($crm::RIGHT_LEADS)) {
            if (ubRouting::checkPost($crm::PROUTE_LEAD_SAVE)) {
                if (ubRouting::checkPost(array($crm::PROUTE_LEAD_ADDR, $crm::PROUTE_LEAD_NAME, $crm::PROUTE_LEAD_MOBILE, $crm::PROUTE_LEAD_NOTES))) {
                    $leadId = ubRouting::post($crm::PROUTE_LEAD_SAVE);
                    $address = ubRouting::post($crm::PROUTE_LEAD_ADDR);
                    $realname = ubRouting::post($crm::PROUTE_LEAD_NAME);
                    $phone = ubRouting::post($crm::PROUTE_LEAD_PHONE);
                    $mobile = ubRouting::post($crm::PROUTE_LEAD_MOBILE);
                    $extmobile = ubRouting::post($crm::PROUTE_LEAD_EXTMOBILE);
                    $email = ubRouting::post($crm::PROUTE_LEAD_EMAIL);
                    $branch = ubRouting::post($crm::PROUTE_LEAD_BRANCH);
                    $tariff = ubRouting::post($crm::PROUTE_LEAD_TARIFF);
                    $login = ubRouting::post($crm::PROUTE_LEAD_LOGIN);
                    $employeeid = ubRouting::post($crm::PROUTE_LEAD_EMPLOYEE);
                    $notes = ubRouting::post($crm::PROUTE_LEAD_NOTES);

                    $leadSaveResult = $crm->saveLead($leadId, $address, $realname, $phone, $mobile, $extmobile, $email, $branch, $tariff, $login, $employeeid, $notes);
                    ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_LEAD_PROFILE . '=' . $leadSaveResult);
                } else {
                    show_error(__('All fields marked with an asterisk are mandatory'));
                }
            }
        }

        //rendering existing lead profile
        if (ubRouting::checkGet($crm::ROUTE_LEAD_PROFILE)) {
            $leadId = ubRouting::get($crm::ROUTE_LEAD_PROFILE, 'int');
            show_window(__('Lead profile') . ': ' . $crm->getLeadLabel($leadId), $crm->renderLeadProfile($leadId));
            //lead source here
            if ($crm->isLeadExists($leadId)) {
                show_window(__('Lead source'), $crm->renderLeadSource($leadId));
            }
        }

        //rendering existing leads list
        if (ubRouting::checkGet($crm::ROUTE_LEADS_LIST)) {
            show_window(__('Existing leads'), $crm->renderLeadsList());
        }
        zb_BillingStats();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}