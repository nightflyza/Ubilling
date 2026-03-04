<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['PER_CITY_ACTION']) {
    if (cfr('CITYACTION')) {
        $admin = whoami();
        $pca = new PerCityAction();
        show_window(__('Actions'), $pca->renderControls());

       
        if (ubRouting::checkGet('action')) {
            $action = ubRouting::get('action');
            if ($action == 'debtors') {
                if (cfr('REPORTCITYDEBTORS')) {
                    show_window(__('Payments'), $pca->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($pca->CheckRigts($cityId, $admin)) {
                            $pca->LoadAllData('', $cityId, 'debtors');
                            $report_name = __('Debtors by city');
                            $report_name.= wf_Link($pca::MODULE_NAME . "&action=debtors&citysearch=$cityId&printable=true", wf_img("skins/printer_small.gif",__('Print')));
                            $report_name.= wf_Link($pca::MODULE_NAME . "&action=debtors&citysearch=$cityId&" . $pca::ROUTE_SHOW_LP . "=true", wf_img_sized("skins/icon_dollar_16.gif", __('Last payment'), 10));
                            $reportData=$pca->PerCityDataShow();
                            show_window(__($report_name), $reportData);
                            if (ubRouting::checkGet($pca::ROUTE_PRINTABLE)) {
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
                    show_window(__('User search'), $pca->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($pca->CheckRigts($cityId, $admin)) {
                            $pca->LoadAllData('', $cityId, 'usersearch');
                            $report_name = __('Search results') . wf_link($pca::MODULE_NAME . "&action=usersearch&printable=true&citysearch=$cityId", wf_img("skins/printer_small.gif"));
                            $reportData=$pca->PerCityDataShow();
                            show_window(__($report_name), $reportData);
                            if (ubRouting::checkGet($pca::ROUTE_PRINTABLE)) {
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
                    show_window(__('Payments'), $pca->CitySelector($admin, $action));
                    if (ubRouting::checkGet('citysearch')) {
                        $cityId = ubRouting::get('citysearch', 'int');
                        if ($pca->CheckRigts($cityId, $admin)) {
                            if (!ubRouting::checkGet('monthsel')) {
                                $cur_month = $pca->GetCurrentDate(true);
                                ubRouting::nav($pca::MODULE_NAME . "&action=city_payments&citysearch=" . ubRouting::get('citysearch', 'int') . "&monthsel=" . $cur_month);
                            } else {
                                $cur_month = ubRouting::get('monthsel');
                            }
                            if (ubRouting::checkGet('year')) {
                                $year = ubRouting::get('year', 'int');
                            } else {
                                $year = $pca->GetCurrentDate(false, true);
                            }
                            $currentDate = $year . '-' . $cur_month;
                            $pca->LoadAllCredited($currentDate);
                            $pca->LoadAllData($currentDate, $cityId, 'payments');
                            show_window(__('Payments by city'), $pca->PaymentsShow());
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
                        show_window(__('Cities'), $pca->CityChecker($editParam));
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
                            file_put_contents($pca::PERMISSION_PATH . $editParam, $adminsPermission);
                            ubRouting::nav($pca::MODULE_NAME . "&action=permission&edit=" . $editParam);
                        }
                    } else {
                        show_window(__('Admins'), $pca->ListAdmins());
                    }

                    if (ubRouting::checkGet('delete')) {
                        $deleteParam = ubRouting::get('delete');
                        if (!empty($deleteParam)) {

                            if (file_exists($pca::PERMISSION_PATH . $deleteParam)) {
                                unlink($pca::PERMISSION_PATH . $deleteParam);
                                ubRouting::nav($pca::MODULE_NAME . "&action=permission");
                            } else {
                                ubRouting::nav($pca::MODULE_NAME . "&action=permission");
                            }
                        } else {
                            ubRouting::nav($pca::MODULE_NAME . "&action=permission");
                        }
                    }
                } else {
                    show_error(__('You cant control this module'));
                }
            }
            if ($action == 'analytics') {
                show_window(__('By date'), $pca->CitySelector($admin, $action));
                show_window('', $pca->ChooseDateForm($action));
                if (ubRouting::checkGet(array('from_date', 'to_date', 'citysearch'))) {
                    $pca->LoadAllData('', ubRouting::get('citysearch', 'int'), 'analytics', ubRouting::get('from_date'), ubRouting::get('to_date'));
                    show_window(__('Analytics'), $pca->AnalyticsShow());
                } elseif (ubRouting::checkGet(array('by_day', 'citysearch'))) {
                    $pca->LoadAllData('', ubRouting::get('citysearch', 'int'), 'analytics', '', '', ubRouting::get('by_day'));
                    show_window(__('Analytics'), $pca->AnalyticsShow());
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
