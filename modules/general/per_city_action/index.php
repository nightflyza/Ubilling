<?php

if (cfr('CITYACTION')) {
    $form = wf_Link("?module=per_city_action&debtors=true", __('Debtors'), false, 'ubButton');
    $form.= wf_Link("?module=per_city_action&city_payments=true", __('Payments per city'), false, 'ubButton');
    $form.= wf_Link("?module=per_city_action&usersearch=true", __('User search'), true, 'ubButton');
    show_window(__('Actions'), $form);

    if (cfr('REPORTCITYDEBTORS')) {
        if (isset($_GET['debtors'])) {
            if ($_GET['debtors']) {

                function web_ReportDebtorsShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
                    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
                    $report_name = '<h2>' . __("Debtors by city") . '</h2>';
                    $allrealnames = zb_UserGetAllRealnames();
                    $alladdress = zb_AddressGetFulladdresslist();
                    if ($alter_conf['FINREP_TARIFF']) {
                        $alltariffs = zb_TariffsGetAllUsers();
                    }
                    $allphonedata = zb_UserGetAllPhoneData();
                    $allmacs = zb_UserGetAllMACs();
                    $i = 0;
                    $result = '
            <style type="text/css">
        table.printrm {
        border-width: 1px;
        border-spacing: 2px;
        border-style: outset;
        border-color: gray;
        border-collapse: separate;
        background-color: white;
        }
        table.printrm th {
        border-width: 1px;
        padding: 1px;
        border-style: dashed;
        border-color: gray;
        background-color: white;
        -moz-border-radius: ;
        }
        table.printrm td {
        border-width: 1px;
        padding: 1px;
        border-style: dashed;
        border-color: gray;
        background-color: white;
        -moz-border-radius: ;
        }
        </style>


         <table width="100%"  class="printrm">';
                    $result.='<tr>';
                    foreach ($titles as $eachtitle) {
                        $result.='<td>' . __($eachtitle) . '</td>';
                    }
                    if ($address) {
                        $result.='<td>' . __('Full address') . '</td>';
                    }
                    if ($realnames) {
                        $result.='<td>' . __('Real Name') . '</td>';
                    }

                    $result.='</tr>';
                    if (!empty($alldata)) {
                        foreach ($alldata as $io => $eachdata) {
                            $i++;
                            $result.='<tr>';
                            foreach ($keys as $eachkey) {
                                if (array_key_exists($eachkey, $eachdata)) {
                                    $result.='<td>' . $eachdata[$eachkey] . '</td>';
                                }
                            }
                            if ($alter_conf['FINREP_TARIFF']) {
                                $result.= '<td>' . @$alltariffs[$eachdata['login']] . '</td>';
                            }
                            $result.= '<td>' . @$allphonedata[$eachdata['login']]['mobile'] . '</td>';
                            $result.= '<td>' . $allmacs[$eachdata['login']] . '</td>';
                            if ($address) {
                                $result.='<td>' . @$alladdress[$eachdata['login']] . '</td>';
                            }
                            if ($realnames) {
                                $result.='<td>' . @$allrealnames[$eachdata['login']] . '</td>';
                            }
                            $result.='</tr>';
                        }
                    }
                    $result.='</table>';
                    if ($rowcount) {
                        $result.='<strong>' . __('Total') . ': ' . $i . '</strong>';
                    }
                    print($report_name . $result);
                    die();
                }

                function web_DebtorsShow($query) {
                    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
                    $alladrs = zb_AddressGetFulladdresslist();
                    $allrealnames = zb_UserGetAllRealnames();
                    $alldebtors = simple_queryall($query);
                    if ($alter_conf['FINREP_TARIFF']) {
                        $alltariffs = zb_TariffsGetAllUsers();
                    }
                    $allphonedata = zb_UserGetAllPhoneData();
                    $allmacs = zb_UserGetAllMACs();
                    $total = 0;
                    $totalPaycount = 0;

                    $cells = wf_TableCell(__('Cash'));
                    $cells.= wf_TableCell(__('Login'));
                    $cells.= wf_TableCell(__('Full address'));
                    $cells.= wf_TableCell(__('Real Name'));
                    if ($alter_conf['FINREP_TARIFF']) {
                        $cells.=wf_TableCell(__('Tariff'));
                    }
                    $cells.= wf_TableCell(__('mobile'));
                    $cells.= wf_TableCell(__('mac'));
//    $cells.= wf_TableCell(__('mac_onu'));
                    $rows = wf_TableRow($cells, 'row1');

                    if (!empty($alldebtors)) {
                        foreach ($alldebtors as $eachdebtor) {

                            $cell = wf_TableCell($eachdebtor['Cash']);
                            $cell.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachdebtor['login'], (web_profile_icon() . ' ' . $eachdebtor['login']), false, ''));
                            $cell.= wf_TableCell(@$alladrs[$eachdebtor['login']]);
                            $cell.= wf_TableCell(@$allrealnames[$eachdebtor['login']]);
                            //optional tariff display
                            if ($alter_conf['FINREP_TARIFF']) {
                                $cell.= wf_TableCell(@$alltariffs[$eachdebtor['login']]);
                            }
                            $cell.= wf_TableCell(@$allphonedata[$eachdebtor['login']]['mobile']);
                            $cell.= wf_TableCell($allmacs[$eachdebtor['login']]);
                            $rows.= wf_TableRow($cell, 'row3');

                            if ($eachdebtor['Cash'] < 0) {
                                $total = $total + $eachdebtor['Cash'];
                                $totalPaycount++;
                            }
                        }
                    }

                    $result = wf_TableBody($rows, '100%', '0', 'sortable');
                    $result.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
                    $result.=wf_tag('strong') . __('Count') . ': ' . $totalPaycount . wf_tag('strong', true);
                    return($result);
                }

                function web_UserPaymentsCityForm() {
                    $form = wf_tag('form', false, '', 'action="" method="POST"');
                    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
                    if (!isset($_POST['citysel'])) {
                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_CitySelectorAc());
                        $form.= wf_TableRow($cells, 'row3');
                    } else {
                        // if city selected
                        $cityname = zb_AddressGetCityData($_POST['citysel']);
                        $cityname = $cityname['cityname'];

                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_POST['citysel']));
                        $cells.= wf_TableCell(wf_Submit(__('Find')));
                        $form.= wf_TableRow($cells, 'row1');
                    }
                    $form.= wf_tag('table', true);
                    $form.= wf_tag('form', true);

                    return($form);
                }

                show_window(__('Payments'), web_UserPaymentsCityForm());
                $sQuery = "SELECT * FROM `users` WHERE `cash` < 0 AND `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`=";



                if (isset($_POST['citysearch'])) {
                    $cityQuery = $_POST['citysearch'];
                    $report_name = 'Debtors by city';
                    $report_name = __($report_name) . wf_Link("?module=per_city_action&debtors=true&citysel=$cityQuery&printable=true", wf_img("skins/printer_small.gif"));
                    $query = $sQuery . $cityQuery . "))))";
                    show_window(__($report_name), web_DebtorsShow($query));
                }

                if (isset($_GET['printable'])) {
                    if ($_GET['printable']) {
                        $query = "SELECT `address`.`login`,`users`.`cash` FROM `address` INNER JOIN users USING (login) WHERE `address`.`aptid` IN ( SELECT `id` FROM `apt` WHERE `buildid` IN ( SELECT `id` FROM `build` WHERE `streetid` IN ( SELECT `id` FROM `street` WHERE `cityid`=" . $_GET['citysel'] . "))) and `users`.`cash`<0";
                        $keys = array('login', 'cash');
                        $titles = array('Login', 'Cash', 'tariff', 'mobile', 'mac');
                        $alldata = simple_queryall($query);
                        web_ReportDebtorsShowPrintable($titles, $keys, $alldata, '1', '1', '1');
                    }
                }
            }
        }
    } else {
        show_error('You dont have enough permission');
    }
    if (cfr('CITYUSERSEARCH')) {
        if (isset($_GET['usersearch'])) {
            if ($_GET['usersearch']) {

                function web_UserSearchCityForm() {

                    $form = wf_tag('form', false, '', 'action="" method="POST"');
                    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
                    if (!isset($_POST['citysel'])) {
                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_CitySelectorAc());
                        $form.= wf_TableRow($cells, 'row3');
                    } else {
                        // if city selected
                        $cityname = zb_AddressGetCityData($_POST['citysel']);
                        $cityname = $cityname['cityname'];

                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_POST['citysel']));
                        $cells.= wf_TableCell(wf_Submit(__('Find')));
                        $form.= wf_TableRow($cells, 'row3');
                    }

                    $form.=wf_tag('table', true);
                    $form.=wf_tag('form', true);

                    return($form);
                }

                function Search_City($query, $searchtype) {
                    global $ubillingConfig;
                    $query = mysql_real_escape_string(trim($query));
                    $searchtype = vf($searchtype);
                    $altercfg = $ubillingConfig->getAlter();

                    //check strict mode for our searchtype
                    $strictsearch = array();
                    if (isset($altercfg['SEARCH_STRICT'])) {
                        if (!empty($altercfg['SEARCH_STRICT'])) {
                            $strictsearch = explode(',', $altercfg['SEARCH_STRICT']);
                            $strictsearch = array_flip($strictsearch);
                        }
                    }


                    //construct query                 
                    if ($searchtype == 'city') {
                        $query = "SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`=$query)))";
                    }                                        
                    //mac-address search
                    if ($searchtype == 'mac') {
                        $allfoundlogins = array();
                        $allMacs = zb_UserGetAllMACs();
                        $searchMacPart = strtolower($query);
                        if (!empty($allMacs)) {
                            $allMacs = array_flip($allMacs);
                            foreach ($allMacs as $eachMac => $macLogin) {
                                if (ispos($eachMac, $searchMacPart)) {
                                    $allfoundlogins[] = $macLogin;
                                }
                            }
                        }
                    }

                    if ($searchtype == 'apt') {
                        $query = "SELECT `login` from `address` WHERE `aptid` = '" . $query . "'";
                    }
                    if ($searchtype == 'payid') {
                        if ($altercfg['OPENPAYZ_REALID']) {
                            $query = "SELECT `realid` AS `login` from `op_customers` WHERE `virtualid`='" . $query . "'";
                        } else {
                            $query = "SELECT `login` from `users` WHERE `IP` = '" . int2ip($query) . "'";
                        }
                    }

                    // пытаемся изобразить результат
                    if ($searchtype != 'mac') {
                        $allresults = simple_queryall($query);
                        $allfoundlogins = array();
                        if (!empty($allresults)) {
                            foreach ($allresults as $io => $eachresult) {
                                $allfoundlogins[] = $eachresult['login'];
                            }
                            //если таки по четкому адресу искали - давайте уж в профиль со старта
                            if ($searchtype == 'apt') {
                                rcms_redirect("?module=userprofile&username=" . $eachresult['login']);
                            }
                        }
                    }

                    $result = web_UserArrayShower($allfoundlogins);
                    return($result);
                }

                function web_ReportCityShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
                    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
                    $report_name = '<h2>' . __("Debtors by city") . '</h2>';
                    $allrealnames = zb_UserGetAllRealnames();
                    $alladdress = zb_AddressGetFulladdresslist();
                    if ($alter_conf['FINREP_TARIFF']) {
                        $alltariffs = zb_TariffsGetAllUsers();
                    }
                    $allphonedata = zb_UserGetAllPhoneData();
                    $allmacs = zb_UserGetAllMACs();
                    $i = 0;
                    $result = '
            <style type="text/css">
        table.printrm {
        border-width: 1px;
        border-spacing: 2px;
        border-style: outset;
        border-color: gray;
        border-collapse: separate;
        background-color: white;
        }
        table.printrm th {
        border-width: 1px;
        padding: 1px;
        border-style: dashed;
        border-color: gray;
        background-color: white;
        -moz-border-radius: ;
        }
        table.printrm td {
        border-width: 1px;
        padding: 1px;
        border-style: dashed;
        border-color: gray;
        background-color: white;
        -moz-border-radius: ;
        }
        </style>


         <table width="100%"  class="printrm">';
                    $result.='<tr>';
                    foreach ($titles as $eachtitle) {
                        $result.='<td>' . __($eachtitle) . '</td>';
                    }
                    if ($address) {
                        $result.='<td>' . __('Full address') . '</td>';
                    }
                    if ($realnames) {
                        $result.='<td>' . __('Real Name') . '</td>';
                    }

                    $result.='</tr>';
                    if (!empty($alldata)) {
                        foreach ($alldata as $io => $eachdata) {
                            $i++;
                            $result.='<tr>';
                            foreach ($keys as $eachkey) {
                                if (array_key_exists($eachkey, $eachdata)) {
                                    $result.='<td>' . $eachdata[$eachkey] . '</td>';
                                }
                            }
                            if ($alter_conf['FINREP_TARIFF']) {
                                $result.= '<td>' . @$alltariffs[$eachdata['login']] . '</td>';
                            }
                            $result.= '<td>' . @$allphonedata[$eachdata['login']]['mobile'] . '</td>';
                            $result.= '<td>' . $allmacs[$eachdata['login']] . '</td>';
                            if ($address) {
                                $result.='<td>' . @$alladdress[$eachdata['login']] . '</td>';
                            }
                            if ($realnames) {
                                $result.='<td>' . @$allrealnames[$eachdata['login']] . '</td>';
                            }
                            $result.='</tr>';
                        }
                    }
                    $result.='</table>';
                    if ($rowcount) {
                        $result.='<strong>' . __('Total') . ': ' . $i . '</strong>';
                    }
                    print($report_name . $result);
                    die();
                }

                if (isset($_GET['printable'])) {
                    $City = $_GET['citysel'];
                    $query = "SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`=$City)))";
                    $keys = array('login');
                    $titles = array('Login', 'tariff', 'mobile', 'mac');
                    $alldata = simple_queryall($query);
                    web_ReportCityShowPrintable($titles, $keys, $alldata, '1', '1', '1');
                }

                $gridRows = wf_tag('tr', false, '', 'valign="top"');
                $gridRows.= wf_TableCell(wf_tag('h3', false, 'row3') . __('City') . wf_tag('h3', true) . web_UserSearchCityForm(), '60%', '');
                $gridRows.= wf_tag('tr', true);
                $search_forms_grid = wf_TableBody($gridRows, '100%', 0, '');
                show_window(__('User search'), $search_forms_grid);

                if (isset($_POST['citysearch'])) {
                    $cityQuery = $_POST['citysearch'];
                    $report_name = 'Search results';
                    $report_name = __($report_name) . wf_link("?module=per_city_action&usersearch=true&printable=true&citysel=$cityQuery", wf_img("skins/printer_small.gif"));
                    show_window(__($report_name), Search_City($cityQuery, 'city'));
                }
            }
        }
    } else {
        show_error('You dont have enough permission');
    } 
    if (cfr('CITYPAYMENTS')) {
        if (isset($_GET['city_payments'])) {
            if ($_GET['city_payments']) {

                function web_UserMonthSelector($value = '') {
                    $mcells = '';
                    $allmonth = months_array_localized();
                    foreach ($allmonth as $io => $each) {
                        $mcells.= wf_TableCell(wf_Link("?module=report_city&monthsel=$io", $each, false, 'ubButton'));
                    }
                    return ($mcells);
                }

                function web_UserPaymentsCityForm() {
                    $form = wf_tag('form', false, '', 'action="" method="POST"');
                    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
                    if (!isset($_POST['citysel'])) {
                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_CitySelectorAc());
                        $form.= wf_TableRow($cells, 'row3');
                    } else {
                        // if city selected
                        $cityname = zb_AddressGetCityData($_POST['citysel']);
                        $cityname = $cityname['cityname'];

                        $cells = wf_TableCell(__('City'), '40%');
                        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_POST['citysel']));
                        $cells.= wf_TableCell(wf_Submit(__('Find')));
                        $form.= wf_TableRow($cells, 'row1');
                    }
                    $form.= wf_tag('table', true);
                    $form.= wf_tag('form', true);

                    return($form);
                }

                $month_name = date("n") - 1;
                show_window(__('Change month'), web_UserMonthSelector());
                show_window(__('Payments'), web_UserPaymentsCityForm());
                if (isset($_POST['citysearch'])) {
                    if (!isset($_GET['monthsel'])) {
                        $cur_month = date("m");
                    } else {
                        $cur_month = $_GET['monthsel'];
                    }
                    $year = date("o");
                    $cur_date = $year . '-' . $cur_month;
                    $cityQuery = $_POST['citysearch'];
                    show_window(__('Payments by city'), web_PaymentsShow("SELECT * FROM `payments` WHERE date like '" . $cur_date . "%' and `login` IN (SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`=$cityQuery))))"));
                }
            }
        }
    }
} else {
    show_error('You dont have permission to use this module');
}        