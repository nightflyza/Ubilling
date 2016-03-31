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
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action == 'debtors') {
                if (cfr('REPORTCITYDEBTORS')) {
                    show_window(__('Payments'), $perCityAction->CitySelector($admin, $action));
                    if (isset($_GET['citysearch'])) {
                        $cityId = $_GET['citysearch'];
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            $perCityAction->LoadAllData('', $cityId, 'debtors');
                            if (isset($_GET['ajax'])) {
                                die($perCityAction->ajaxData());
                            }
                            $report_name = __('Debtors by city') . wf_Link(PerCityAction::MODULE_NAME . "&action=debtors&citysel=$cityId&printable=true", wf_img("skins/printer_small.gif"));
                            show_window(__($report_name), $perCityAction->PerCityDataShow());
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                    if (isset($_GET['printable'])) {
                        if ($_GET['printable']) {
                            $query = "SELECT `address`.`login`,`users`.`cash` FROM `address` INNER JOIN users USING (login) WHERE `address`.`aptid` IN ( SELECT `id` FROM `apt` WHERE `buildid` IN ( SELECT `id` FROM `build` WHERE `streetid` IN ( SELECT `id` FROM `street` WHERE `cityid`='" . $_GET['citysel'] . "'))) and `users`.`cash`<0";
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
                    if (isset($_GET['citysearch'])) {
                        $cityId = $_GET['citysearch'];
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            $perCityAction->LoadAllData('', $cityId, 'usersearch');
                            $report_name = __('Search results') . wf_link(PerCityAction::MODULE_NAME . "&action=usersearch&printable=true&citysel=$cityId", wf_img("skins/printer_small.gif"));
                            show_window(__($report_name), $perCityAction->PerCityDataShow());
                        } else {
                            show_error(__('You cant control this module'));
                        }
                    }
                    if (isset($_GET['printable'])) {
                        $City = $_GET['citysel'];
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
                    if (isset($_GET['citysearch'])) {
                        $cityId = $_GET['citysearch'];
                        if ($perCityAction->CheckRigts($cityId, $admin)) {
                            if (!isset($_GET['monthsel'])) {
                                $cur_month = $perCityAction->GetCurrentDate(true);
                                rcms_redirect(PerCityAction::MODULE_NAME . "&action=city_payments&citysearch=" . $_GET['citysearch'] . "&monthsel=" . $cur_month);
                            } else {
                                $cur_month = $_GET['monthsel'];
                            }
                            if (isset($_GET['year'])) {
                                $year = $_GET['year'];
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
                    if (isset($_GET['edit'])) {
                        show_window(__('Cities'), $perCityAction->CityChecker($_GET['edit']));
                        if (isset($_POST['city'])) {
                            $adminsPermission = '';
                            foreach ($_POST['city'] as $eachCity) {
                                if ($eachCity == end($_POST['city'])) {
                                    $adminsPermission.= $eachCity;
                                } else {
                                    $adminsPermission.= $eachCity . ",";
                                }
                            }
                            file_put_contents(PerCityAction::PERMISSION_PATH . $_GET['edit'], $adminsPermission);
                            rcms_redirect(PerCityAction::MODULE_NAME . "&action=permission&edit=" . $_GET['edit']);
                        }
                    } else {
                        show_window(__('Admins'), $perCityAction->ListAdmins());
                    }

                    if (isset($_GET['delete'])) {
                        if (!empty($_GET['delete'])) {

                            if (file_exists(PerCityAction::PERMISSION_PATH . $_GET['delete'])) {
                                unlink(PerCityAction::PERMISSION_PATH . $_GET['delete']);
                                rcms_redirect(PerCityAction::MODULE_NAME . "&action=permission");
                            } else {
                                rcms_redirect(PerCityAction::MODULE_NAME . "&action=permission");
                            }
                        } else {
                            rcms_redirect(PerCityAction::MODULE_NAME . "&action=permission");
                        }
                    }
                } else {
                    show_error(__('You cant control this module'));
                }
            }
            if ($action == 'analytics') {
                show_window(__('By date'), $perCityAction->CitySelector($admin, $action));
                show_window('', $perCityAction->ChooseDateForm($action));
                if (isset($_GET['from_date']) && isset($_GET['to_date']) && isset($_GET['citysearch'])) {
                    $perCityAction->LoadAllData('', $_GET['citysearch'], 'analytics', $_GET['from_date'], $_GET['to_date']);
                    show_window(__('Analytics'), $perCityAction->AnalyticsShow());
                } elseif (isset($_GET['by_day']) && isset($_GET['citysearch'])) {
                    $perCityAction->LoadAllData('', $_GET['citysearch'], 'analytics', '', '', $_GET['by_day']);
                    show_window(__('Analytics'), $perCityAction->AnalyticsShow());
                }
            }
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module is disabled'));
}        
