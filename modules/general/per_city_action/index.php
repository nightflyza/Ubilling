<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['PER_CITY_ACTION']) {
    if (cfr('CITYACTION')) {
        $form = wf_Link("?module=per_city_action&action=debtors", __('Debtors'), false, 'ubButton');
        $form.= wf_Link("?module=per_city_action&action=city_payments", __('Payments per city'), false, 'ubButton');
        $form.= wf_Link("?module=per_city_action&action=usersearch", __('User search'), true, 'ubButton');
        show_window(__('Actions'), $form);

        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'debtors') {
                if (cfr('REPORTCITYDEBTORS')) {
                    show_window(__('Payments'), DebtorsCitySelector());
                    if (isset($_GET['citysearch'])) {
                        $cityQuery = $_GET['citysearch'];
                        $report_name = 'Debtors by city';
                        $report_name = __($report_name) . wf_Link("?module=per_city_action&action=debtors&citysel=$cityQuery&printable=true", wf_img("skins/printer_small.gif"));
                        $sQuery = "SELECT * FROM `users` WHERE `cash` < 0 AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityQuery . "'))))";
                        show_window(__($report_name), web_PerCityShow($sQuery));
                    }
                    if (isset($_GET['printable'])) {
                        if ($_GET['printable']) {
                            $query = "SELECT `address`.`login`,`users`.`cash` FROM `address` INNER JOIN users USING (login) WHERE `address`.`aptid` IN ( SELECT `id` FROM `apt` WHERE `buildid` IN ( SELECT `id` FROM `build` WHERE `streetid` IN ( SELECT `id` FROM `street` WHERE `cityid`='" . $_GET['citysel'] . "'))) and `users`.`cash`<0";
                            $keys = array('cash', 'login');
                            $titles = array('tariff', 'comment', 'mac_onu', 'Cash', 'login');
                            $alldata = simple_queryall($query);
                            web_ReportDebtorsShowPrintable($titles, $keys, $alldata, '1', '1', '1');
                        }
                    }
                }
            }


            if ($_GET['action'] == 'usersearch') {
                if (cfr('CITYUSERSEARCH')) {
                    show_window(__('User search'), web_UserSearchCityForm());
                    if (isset($_GET['printable'])) {
                        $City = $_GET['citysel'];
                        $query = "SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $City . "')))";
                        $keys = array('login');
                        $titles = array('tariff', 'comment', 'mac_onu', 'Login');
                        $alldata = simple_queryall($query);
                        web_ReportCityShowPrintable($titles, $keys, $alldata, '1', '1', '1');
                    }

                    if (isset($_GET['citysearch'])) {
                        $cityQuery = $_GET['citysearch'];
                        $sQuery = "SELECT * FROM `users` WHERE `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityQuery . "'))))";
                        $report_name = 'Search results';
                        $report_name = __($report_name) . wf_link("?module=per_city_action&action=usersearch&printable=true&citysel=$cityQuery", wf_img("skins/printer_small.gif"));
                        show_window(__($report_name), web_PerCityShow($sQuery));
                    }
                }
            }
            if ($_GET['action'] == 'city_payments') {
                if (cfr('CITYPAYMENTS')) {
                    $month_name = date("n") - 1;
                    show_window(__('Change month'), web_MonthSelector());
                    show_window(__('Payments'), web_UserPaymentsCityForm());
                    if (isset($_GET['citysearch'])) {
                        if (!isset($_GET['monthsel'])) {
                            $cur_month = date("m");
                            rcms_redirect("?module=per_city_action&action=city_payments&citysearch=" . $_GET['citysearch'] . "&monthsel=" . $cur_month);
                        } else {
                            $cur_month = $_GET['monthsel'];
                        }
                        $year = date("o");
                        $cur_date = $year . '-' . $cur_month;
                        $cityQuery = $_GET['citysearch'];
                        show_window(__('Payments by city'), web_PaymentsCityShow("SELECT * FROM `payments` WHERE `date` LIKE '" . $cur_date . "%'  AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`='" . $cityQuery . "'))))"));
                    }
                }
            }
        }
    } else {
        show_error('You cant control this module');
    }
} else {
    show_error('This module is disabled');
}        
