<?php

function web_PaymentsCityShow($query) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $alladrs = zb_AddressGetFulladdresslist();
    $allrealnames = zb_UserGetAllRealnames();
    $alltypes = zb_CashGetAllCashTypes();
    $allapayments = simple_queryall($query);
    $allservicenames = zb_VservicesGetAllNamesLabeled();
    //getting full contract list
    if ($alter_conf['FINREP_CONTRACT']) {
        $allcontracts = zb_UserGetAllContracts();
        $allcontracts = array_flip($allcontracts);
    }

    //getting all users tariffs
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }

    $total = 0;
    $totalPaycount = 0;

    $cells = wf_TableCell(__('IDENC'));
    $cells.= wf_TableCell(__('Date'));
    $cells.= wf_TableCell(__('Cash'));
    //optional contract display
    if ($alter_conf['FINREP_CONTRACT']) {
        $cells.= wf_TableCell(__('Contract'));
    }
    $cells.= wf_TableCell(__('Login'));
    $cells.= wf_TableCell(__('Full address'));
    $cells.= wf_TableCell(__('Real Name'));
    //optional tariff display
    if ($alter_conf['FINREP_TARIFF']) {
        $cells.=wf_TableCell(__('Tariff'));
    }
    $cells.= wf_TableCell(__('Cash type'));
    $cells.= wf_TableCell(__('Notes'));
    $cells.= wf_TableCell(__('Admin'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allapayments)) {
        foreach ($allapayments as $io => $eachpayment) {

            if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
                $eachpayment['note'] = zb_TranslatePaymentNote($eachpayment['note'], $allservicenames);
            }

            $cells = wf_TableCell(zb_NumEncode($eachpayment['id']));
            $cells.= wf_TableCell($eachpayment['date']);
            $cells.= wf_TableCell($eachpayment['summ']);
            //optional contract display
            if ($alter_conf['FINREP_CONTRACT']) {
                $cells.= wf_TableCell(@$allcontracts[$eachpayment['login']]);
            }
            $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachpayment['login'], (web_profile_icon() . ' ' . $eachpayment['login']), false, ''));
            $cells.= wf_TableCell(@$alladrs[$eachpayment['login']]);
            $cells.= wf_TableCell(@$allrealnames[$eachpayment['login']]);
            //optional tariff display
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.= wf_TableCell(@$alltariffs[$eachpayment['login']]);
            }
            $cells.= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
            $cells.= wf_TableCell($eachpayment['note']);
            $cells.= wf_TableCell($eachpayment['admin']);
            $rows.= wf_TableRow($cells, 'row4');

            if ($eachpayment['summ'] > 0) {
                $total = $total + $eachpayment['summ'];
                $totalPaycount++;
            }
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable id');
    $result.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
    $result.=wf_tag('strong') . __('Count') . ': ' . $totalPaycount . wf_tag('strong', true);
    return($result);
}

function web_PerCityShow($query) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $alladrs = zb_AddressGetFulladdresslist();
    $allrealnames = zb_UserGetAllRealnames();
    $alldebtors = simple_queryall($query);
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }
    $allphonedata = zb_UserGetAllPhoneData();
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();
    $total = 0;
    $totalPaycount = 0;

    $cells = wf_TableCell(__('Full address'));
    $cells.= wf_TableCell(__('Real Name'));
    $cells.= wf_TableCell(__('Cash'));
    if ($alter_conf['FINREP_TARIFF']) {
        $cells.=wf_TableCell(__('Tariff'));
    }
    $cells.= wf_TableCell(__('Comment'));
    $cells.= wf_TableCell(__('MAC ONU/ONT'));
    $cells.= wf_TableCell(__('Login'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alldebtors)) {
        foreach ($alldebtors as $eachdebtor) {
            if (!empty($alladrs[$eachdebtor['login']])) {
                $cell = wf_TableCell($alladrs[$eachdebtor['login']]);
            } else {
                $cell = wf_TableCell('');
            }
            if (!empty($allrealnames[$eachdebtor['login']])) {
                if (!empty($allphonedata[$eachdebtor['login']])) {
                    $cell.= wf_TableCell($allrealnames[$eachdebtor['login']] . "&nbsp&nbsp" . $allphonedata[$eachdebtor['login']]['mobile']);
                } else {
                    $cell.= wf_TableCell($allrealnames[$eachdebtor['login']]);
                }
            } else {
                $cell.=wf_TableCell('');
            }
            $cell.= wf_TableCell($eachdebtor['Cash']);
            if ($alter_conf['FINREP_TARIFF']) {
                $cell.= wf_TableCell($alltariffs[$eachdebtor['login']]);
            }
            if (!empty($allnotes[$eachdebtor['login']])) {
                if (!empty($allcomments[$eachdebtor['login']])) {
                    $cell.= wf_TableCell($allnotes[$eachdebtor['login']] . "&nbsp&nbsp" . $allcomments[$eachdebtor['login']]);
                } else {
                    $cell.= wf_TableCell($allnotes[$eachdebtor['login']]);
                }
            } else {
                $cell.= wf_TableCell('');
            }
            if (!empty($allonu[$eachdebtor['login']])) {
                $cell.= wf_TableCell($allonu[$eachdebtor['login']]);
            } else {
                $cell.=wf_TableCell('');
            }
            $cell.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachdebtor['login'], (web_profile_icon() . ' ' . $eachdebtor['login']), false, ''));
            $rows.= wf_TableRow($cell, 'row4');
            $total = $total + $eachdebtor['Cash'];
            $totalPaycount++;
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable id');
    $result.=wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
    $result.=wf_tag('strong') . __('Count') . ': ' . $totalPaycount . wf_tag('strong', true);
    return($result);
}

function GetAllNotes() {
    $query = "SELECT * FROM `notes`";
    $allnotes = simple_queryall($query);
    $notes = array();
    if (!empty($allnotes)) {
        foreach ($allnotes as $ia => $eachnote) {
            $notes[$eachnote['login']] = $eachnote['note'];
        }
    }
    return ($notes);
}

function GetAllComments() {
    $query = "SELECT * FROM `adcomments`";
    $allcomments = simple_queryall($query);
    $comments = array();
    if (!empty($allcomments)) {
        foreach ($allcomments as $ia => $eachcomment) {
            $comments[$eachcomment['item']] = $eachcomment['text'];
        }
    }
    return ($comments);
}

function GetAllOnu() {
    $query = "SELECT * FROM `pononu`";
    $allonu = simple_queryall($query);
    $onu = array();
    if (!empty($allonu)) {
        foreach ($allonu as $io => $each) {
            $onu[$each['login']] = $each['mac'];
        }
    }
    return ($onu);
}

function DebtorsCitySelector() {
    $form = wf_tag('form', false, '', 'action="" method=GET');
    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
    if (!isset($_GET['citysel'])) {
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("debtors", "true");
        $cells.= wf_TableCell(web_CitySelector());
        $cells.= wf_TableCell(wf_Submit(__("Find")));
        $form.= wf_TableRow($cells, 'row3');
    } else {
        // if city selected
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("debtors", "true");
        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_GET['citysel']));
        $cells.= wf_TableCell(wf_Submit(__('Find')));
        $form.= wf_TableRow($cells, 'row1');
    }
    $form.= wf_tag('table', true);
    $form.= wf_tag('form', true);

    return($form);
}

function web_UserPaymentsCityForm() {
    $form = wf_tag('form', false, '', 'action="" method=GET');
    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
    if (!isset($_GET['citysel'])) {
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("city_payments", "true");
        if (isset($_GET['monthsel'])) {
            $cells.= wf_HiddenInput('monthsel', $_GET['monthsel']);
        }
        $cells.= wf_TableCell(web_CitySelector());
        $cells.= wf_TableCell(wf_Submit(__("Find")));
        $form.= wf_TableRow($cells, 'row3');
    } else {
        // if city selected
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];

        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("city_payments", "true");
        if (isset($_GET['monthsel'])) {
            $cells.= wf_HiddenInput('monthsel', $_GET['monthsel']);
        }
        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_GET['citysel']));
        $cells.= wf_TableCell(wf_Submit(__('Find')));
        $form.= wf_TableRow($cells, 'row1');
    }
    $form.= wf_tag('table', true);
    $form.= wf_tag('form', true);

    return($form);
}

function web_UserSearchCityForm() {
    $form = wf_tag('form', false, '', 'action="" method="GET"');
    $form.= wf_tag('table', false, '', 'width="100%" border="0"');
    if (!isset($_GET['citysel'])) {
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("usersearch", "true");
        $cells.= wf_TableCell(web_CitySelector());
        $cells.= wf_TableCell(wf_Submit(__("Find")));
        $form.= wf_TableRow($cells, 'row3');
    } else {
        // if city selected
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("usersearch", "true");
        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_GET['citysel']));
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
    $cityQuery = $query;
    $searchtype = vf($searchtype);
    $altercfg = $ubillingConfig->getAlter();

    //construct query                 
    if ($searchtype == 'city') {
        $query = "SELECT * FROM `users` WHERE `login` IN(SELECT `login` FROM `address` WHERE `aptid` IN (SELECT `id` FROM `apt` WHERE `buildid` IN (SELECT `id` FROM `build` WHERE `streetid` IN (SELECT `id` FROM `street` WHERE `cityid`=$query))))";
    }

    $report_name = 'usersearch';
    $report_name = __($report_name) . wf_Link("?module=per_city_action&debtors=true&citysel=$cityQuery&printable=true", wf_img("skins/printer_small.gif"));
    show_window(__($report_name), web_PerCityShow($query));
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
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();

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
            $result.='<td>' . @$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']] . '</td>';
            $result.='<td>' . @$allonu[$eachdata['login']] . '</td>';

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

function web_MonthSelector($value = '') {
    $mcells = '';
    $allmonth = months_array_localized();
    foreach ($allmonth as $io => $each) {
        if (isset($_GET['citysel'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&city_payments=true&monthsel=" . $io . "&citysel=" . $_GET['citysel'], $each, false, 'ubButton'));
        } elseif (isset($_GET['citysearch'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&city_payments=true&monthsel=" . $io . "&citysearch=" . $_GET['citysearch'], $each, false, 'ubButton'));
        } else {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&city_payments=true&monthsel=" . $io, $each, false, 'ubButton'));
        }
    }
    return ($mcells);
}

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
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();
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
            $result.= '<td>' . @$allmacs[$eachdata['login']] . '</td>';
            $result.='<td>' . @$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']] . '</td>';
            $result.='<td>' . @$allonu[$eachdata['login']] . '</td>';
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
