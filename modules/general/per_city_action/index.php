<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['PER_CITY_ACTION']) {
    if (cfr('CITYACTION')) {
        $admin = whoami();
        $form = wf_Link(PerCityAction::MODULE_NAME, __('Clear'), true, 'ubButton');
        $form.= wf_tag('br');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=debtors", __('Debtors'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=city_payments", __('Payments per city'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=usersearch", __('User search'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=permission", __('Permission'), false, 'ubButton');
        $form.= wf_Link(PerCityAction::MODULE_NAME . "&action=analytics", __('Analytics'), true, 'ubButton');
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
                            $report_name = __('Debtors by city') . wf_Link(PerCityAction::MODULE_NAME . "&action=debtors&citysel=$cityId&printable=true", wf_img("skins/printer_small.gif"));
                            show_window(__($report_name), $perCityAction->PerCityDataShow());
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                    if (ubRouting::checkGet('printable')) {
                        if (ubRouting::get('printable')) {
                            $citysel = ubRouting::get('citysel', 'int');
                            $query = "SELECT `address`.`login`,`users`.`cash` FROM `address` INNER JOIN users USING (login) WHERE `address`.`aptid` IN ( SELECT `id` FROM `apt` WHERE `buildid` IN ( SELECT `id` FROM `build` WHERE `streetid` IN ( SELECT `id` FROM `street` WHERE `cityid`='" . $citysel . "'))) and `users`.`cash`<0";
                            $keys = array('cash', 'login');
                            $titles = array('tariff', "Comment", 'Mac ONU', "Credited", "Cash", 'Login');
                            $alldata = simple_queryall($query);
                            web_ReportDebtorsShowPrintable($titles, $keys, $alldata, '1', '1', '1');
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
                            $report_name = __('Search results') . wf_link(PerCityAction::MODULE_NAME . "&action=usersearch&printable=true&citysel=$cityId", wf_img("skins/printer_small.gif"));
                            show_window(__($report_name), $perCityAction->PerCityDataShow());
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                    if (ubRouting::checkGet('printable')) {
                        $City = ubRouting::get('citysel', 'int');
                        $query = "SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $City . "')))";
                        $keys = array('login');
                        $titles = array('tariff', 'Comment', 'MAC ONU', 'Credited', 'Login');
                        $alldata = simple_queryall($query);
                        web_ReportCityShowPrintable($titles, $keys, $alldata, '1', '1', '1');
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
