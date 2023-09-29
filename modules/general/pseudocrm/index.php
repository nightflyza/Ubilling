<?php

if (cfr(PseudoCRM::RIGHT_VIEW)) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PSEUDOCRM_ENABLED']) {
        $crm = new PseudoCRM();
        //some module controls
        $crmMainControls = $crm->renderPanel();
        if (!empty($crmMainControls)) {
            show_window('', $crmMainControls);
        }

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

        //new activity creation
        if (cfr($crm::RIGHT_ACTIVITIES)) {
            if (ubRouting::checkGet($crm::ROUTE_ACTIVITY_CREATE)) {
                $newActivityLeadId = ubRouting::get($crm::ROUTE_ACTIVITY_CREATE);
                $activityCreationResult = $crm->createActivity($newActivityLeadId);
                //redirecting to new activity profile
                if ($activityCreationResult) {
                    ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_ACTIVITY_PROFILE . '=' . $activityCreationResult);
                } else {
                    show_error(__('Something went wrong'));
                }
            }
        }

        //rendering existing lead profile
        if (ubRouting::checkGet($crm::ROUTE_LEAD_PROFILE)) {
            $leadId = ubRouting::get($crm::ROUTE_LEAD_PROFILE, 'int');
            show_window(__('Lead profile') . ': ' . $crm->getLeadLabel($leadId), $crm->renderLeadProfile($leadId));
            //lead source and activities list here
            if ($crm->isLeadExists($leadId)) {
                show_window(__('Lead source'), $crm->renderLeadSource($leadId));
                show_window(__('Previous activity records'), $crm->renderLeadActivitiesList($leadId));
            }
        }

        //rendering existing leads list
        if (ubRouting::checkGet($crm::ROUTE_LEADS_LIST)) {
            show_window(__('Existing leads'), $crm->renderLeadsList());
        }

        //rendering existing lead activity
        if (ubRouting::checkGet($crm::ROUTE_ACTIVITY_PROFILE)) {
            $activityId = ubRouting::get($crm::ROUTE_ACTIVITY_PROFILE);
            show_window(__('Activity record'), $crm->renderActivityProfile($activityId));
        }

        zb_BillingStats();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}