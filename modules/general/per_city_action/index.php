<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['PER_CITY_ACTION']) {
    if (cfr('CITYACTION')) {
        $admin = whoami();
        $form = wf_Link(PerCityAction::MODULE_NAME, wf_img('skins/icon_cleanup.png') .' '. __('Clear'), true, 'ubButton');
        $form.= wf_tag('br');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=debtors", wf_img('skins/icon_debtor.png') .' '.__('Debtors'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=city_payments", wf_img('skins/icon_dollar_16.gif') .' '.__('Payments per city'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=usersearch",  wf_img('skins/icon_search.png') .' '.__('User search'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=permission", web_icon_extended().' '.__('Permission'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=analytics", web_icon_charts().' '.__('Analytics'), true, 'ubButton');
        show_window(__('Actions'), $form);

        $perCityAction = new PerCityAction();
        if (ubRouting::checkGet('action')) {
            $action = ubRouting::get('action');
            if ($action == 'debtors') {
                if (cfr('REPORTCITYDEBTORS')) {
                    show_window(__('Payments'), $perCityAction->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            $perCityAction->LoadAllData('', $cityId, 'debtors');
                            $report_name = __('Debtors by city');
                            $report_name.= wf_Link(PerCityAction::MODULE_NAME . "&action=debtors&citysearch=$cityId&printable=true", wf_img("skins/printer_small.gif",__('Print')));
                            $report_name.= wf_Link(PerCityAction::MODULE_NAME . "&action=debtors&citysearch=$cityId&" . PerCityAction::ROUTE_SHOW_LP . "=true", wf_img_sized("skins/icon_dollar_16.gif", __('Last payment'), 10));
                            $reportData=$perCityAction->PerCityDataShow();
                            show_window(__($report_name), $reportData);
                            if (ubRouting::checkGet('printable')) {
                                $reportData = zb_ReportPrintable(__('Debtors by city'), $reportData);
                                die($reportData);
                            }
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                }
            }

            if ($action == 'usersearch') {
                if (cfr('CITYUSERSEARCH')) {
                    show_window(__('User search'), $perCityAction->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            $perCityAction->LoadAllData('', $cityId, 'usersearch');
                            $report_name = __('Search results') . wf_link(PerCityAction::MODULE_NAME . "&action=usersearch&printable=true&citysearch=$cityId", wf_img("skins/printer_small.gif"));
                            $reportData=$perCityAction->PerCityDataShow();
                            show_window(__($report_name), $reportData);
                            if (ubRouting::checkGet('printable')) {
                                $reportData = zb_ReportPrintable(__('Search results'), $reportData);
                                die($reportData);
                            }
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                
                }
            }

            if ($action == 'city_payments') {
                if (cfr('CITYPAYMENTS')) {
                    show_window(__('Change month'), web_MonthSelector());
                    show_window(__('Change year'), web_YearSelector());
                    show_window(__('Payments'), $perCityAction->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            if (!ubRouting::checkGet('monthsel')) {
                                $cur_month = $perCityAction->GetCurrentDate(true);
                                ubRouting::nav(PerCityAction::MODULE_NAME . "&action=city_payments&citysearch=" . ubRouting::get('citysearch', 'int') . "&monthsel=" . $cur_month);
                            } else {
                                $cur_month = ubRouting::get('monthsel');
                            }
                            if (ubRouting::checkGet('year')) {
                                $year = ubRouting::get('year', 'int');
                            } else {
                                $year = $perCityAction->GetCurrentDate(false, true);
                            }
                            $currentDate = $year . '-' . $cur_month;
                            $perCityAction->LoadAllCredited($currentDate);
                            $perCityAction->LoadAllData($currentDate, $cityId, 'payments');
                            show_window(__('Payments by city'), $perCityAction->PaymentsShow());
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                }
            }
            if ($action == 'permission') {
                if (cfr('CITYPERMISSION')) {
                    if (ubRouting::checkGet('edit')) {
                        $editParam = ubRouting::get('edit');
                        show_window(__('Cities'), $perCityAction->CityChecker($editParam));
                        if (ubRouting::checkPost('city')) {
                            $adminsPermission = '';
                            $cityArr = ubRouting::post('city');
                            foreach ($cityArr as $eachCity) {
                                if ($eachCity == end($cityArr)) {
                                    $adminsPermission.= $eachCity;
                                } else {
                                    $adminsPermission.= $eachCity . ",";
                                }
                            }
                            file_put_contents(PerCityAction::PERMISSION_PATH . $editParam, $adminsPermission);
                            ubRouting::nav(PerCityAction::MODULE_NAME . "&action=permission&edit=" . $editParam);
                        }
                    } else {
                        show_window(__('Admins'), $perCityAction->ListAdmins());
                    }

                    if (ubRouting::checkGet('delete')) {
                        $deleteParam = ubRouting::get('delete');
                        if (!empty($deleteParam)) {

                            if (file_exists(PerCityAction::PERMISSION_PATH . $deleteParam)) {
                                unlink(PerCityAction::PERMISSION_PATH . $deleteParam);
                                ubRouting::nav(PerCityAction::MODULE_NAME . "&action=permission");
                            } else {
                                ubRouting::nav(PerCityAction::MODULE_NAME . "&action=permission");
                            }
                        } else {
                            ubRouting::nav(PerCityAction::MODULE_NAME . "&action=permission");
                        }
                    }
                } else {
                    show_error(__('You cant control this module'));
                }
            }
            if ($action == 'analytics') {
                show_window(__('By date'), $perCityAction->CitySelector($admin, $action));
                show_window('', $perCityAction->ChooseDateForm($action));
                if (ubRouting::checkGet(array('from_date', 'to_date', 'citysearch'))) {
                    $perCityAction->LoadAllData('', ubRouting::get('citysearch', 'int'), 'analytics', ubRouting::get('from_date'), ubRouting::get('to_date'));
                    show_window(__('Analytics'), $perCityAction->AnalyticsShow());
                } elseif (ubRouting::checkGet(array('by_day', 'citysearch'))) {
                    $perCityAction->LoadAllData('', ubRouting::get('citysearch', 'int'), 'analytics', '', '', ubRouting::get('by_day'));
                    show_window(__('Analytics'), $perCityAction->AnalyticsShow());
                }
            }
        }
        zb_BillingStats(true);
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module is disabled'));
}        
