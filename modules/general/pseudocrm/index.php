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

        //rendering states ajax log
        if (ubRouting::checkGet($crm::ROUTE_REPORT_STATESLOG_AJ)) {
            $crm->ajStatesLog();
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

        //seting activity as done
        if (cfr($crm::RIGHT_ACTIVITIES)) {
            if (ubRouting::checkGet($crm::ROUTE_ACTIVITY_DONE)) {
                $actitivityId = ubRouting::get($crm::ROUTE_ACTIVITY_DONE);
                $crm->setActivityDone($actitivityId);
                ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_ACTIVITY_PROFILE . '=' . $actitivityId);
            }
        }

        //seting activity as undone
        if (cfr($crm::RIGHT_ACTIVITIES)) {
            if (ubRouting::checkGet($crm::ROUTE_ACTIVITY_UNDONE)) {
                $actitivityId = ubRouting::get($crm::ROUTE_ACTIVITY_UNDONE);
                $crm->setActivityUndone($actitivityId);
                ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_ACTIVITY_PROFILE . '=' . $actitivityId);
            }
        }

        //activity record result editing
        if (cfr($crm::RIGHT_ACTIVITIES)) {
            if (ubRouting::checkPost($crm::PROUTE_ACTIVITY_EDIT)) {
                $actitivityId = ubRouting::post($crm::PROUTE_ACTIVITY_EDIT);
                $crm->setActivityResult($actitivityId, ubRouting::post($crm::PROUTE_ACTIVITY_NOTE));
                ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_ACTIVITY_PROFILE . '=' . $actitivityId);
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

        //lead sources report here
        if (ubRouting::checkGet($crm::ROUTE_REPORT_SOURCES)) {
            show_window(__('Leads sources'), $crm->renderReportLeadSources());
        }

        //lead states report here
        if (ubRouting::checkGet($crm::ROUTE_REPORT_STATESLOG)) {
            show_window(__('States log'), $crm->renderReportStatesLog());
        }

        //login to lead assign
        if (ubRouting::checkPost(array($crm::PROUTE_LEAD_ASSIGN, $crm::PROUTE_LEAD_ASSIGN_ID))) {
            $assignUserLogin = ubRouting::post($crm::PROUTE_LEAD_ASSIGN);
            $assignLeadId = ubRouting::post($crm::PROUTE_LEAD_ASSIGN_ID);
            $leadAssignResult = $crm->setLeadLogin($assignLeadId, $assignUserLogin);
            if (empty($leadAssignResult)) {
                ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_LEAD_PROFILE . '=' . $assignLeadId);
            } else {
                show_error($leadAssignResult);
            }
        }


        //detecting lead by assigned login
        if (ubRouting::checkGet($crm::ROUTE_LEAD_DETECT)) {
            $userLogin = ubRouting::get($crm::ROUTE_LEAD_DETECT);
            $detectedLeadId = $crm->searchLeadByLogin($userLogin);
            //go to the lead profile
            if ($detectedLeadId) {
                ubRouting::nav($crm::URL_ME . '&' . $crm::ROUTE_LEAD_PROFILE . '=' . $detectedLeadId);
            } else {
                //or render assigning form
                show_window(__('Assign lead'), $crm->renderLeadAssignForm($userLogin));
            }
        }

        zb_BillingStats();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}