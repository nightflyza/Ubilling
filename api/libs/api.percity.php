<?php

Class ColorTagging {

    protected $allTags = array();
    protected $allTagTypes = array();
    protected $altCfg = array();

    public function __construct() {
        $this->LoadAllTags();
        $this->LoadAllTagTypes();
        $this->loadAlter();
    }

    protected function LoadAllTags() {
        $query = "SELECT * FROM `tags`";
        $allData = simple_queryall($query);
        if (!empty($allData)) {
            foreach ($allData as $eachData) {
                $this->allTags[$eachData['login']] = $eachData['tagid'];
            }
        }
    }

    protected function LoadAllTagTypes() {
        $query = "SELECT * FROM `tagtypes`";
        $allData = simple_queryall($query);
        if (!empty($allData)) {
            foreach ($allData as $eachData) {
                $this->allTagTypes[$eachData['id']] = $eachData;
            }
        }
    }

    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    public function GetUsersColor($login) {
        $color = '';
        $allowed = '';
        $eq = true;
        if (isset($this->allTags[$login])) {
            if (isset($this->altCfg['ALLOWED_COLORS'])) {
                if (is_numeric($this->altCfg['ALLOWED_COLORS'])) {
                    $allowed = $this->altCfg['ALLOWED_COLORS'];
                    if ($this->allTagTypes[$this->allTags[$login]]['id'] == $allowed) {
                        $eq = false;
                    }
                } else {
                    $allowed = explode(",", $this->altCfg['ALLOWED_COLORS']);
                    foreach ($allowed as $each) {
                        if ($this->allTagTypes[$this->allTags[$login]]['id'] == $each) {
                            $eq = false;
                        }
                    }
                }
                if (!$eq) {
                    $color = $this->allTagTypes[$this->allTags[$login]]['tagcolor'];
                }
            } else {
                $color = $this->allTagTypes[$this->allTags[$login]]['tagcolor'];
            }
        }
        return($color);
    }

}

function web_PaymentsCityShow($query) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $alladrs = zb_AddressGetFulladdresslist();
    $allrealnames = zb_UserGetAllRealnames();
    $alltypes = zb_CashGetAllCashTypes();
    $allapayments = simple_queryall($query);
    $allservicenames = zb_VservicesGetAllNamesLabeled();
    $total = 0;
    $totalPaycount = 0;
    if ($alter_conf['FINREP_CONTRACT']) {
        $allcontracts = zb_UserGetAllContracts();
        $allcontracts = array_flip($allcontracts);
    }
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }

    $cells = wf_TableCell(__('IDENC'));
    $cells.= wf_TableCell(__('Date'));
    $cells.= wf_TableCell(__('Cash'));
    if ($alter_conf['FINREP_CONTRACT']) {
        $cells.= wf_TableCell(__('Contract'));
    }
    $cells.= wf_TableCell(__('Login'));
    $cells.= wf_TableCell(__('Full address'));
    $cells.= wf_TableCell(__('Real Name'));
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
            if ($alter_conf['FINREP_CONTRACT']) {
                $cells.= wf_TableCell(@$allcontracts[$eachpayment['login']]);
            }
            $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachpayment['login'], (web_profile_icon() . ' ' . $eachpayment['login']), false, ''));
            $cells.= wf_TableCell(@$alladrs[$eachpayment['login']]);
            $cells.= wf_TableCell(@$allrealnames[$eachpayment['login']]);
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.= wf_TableCell(@$alltariffs[$eachpayment['login']]);
            }
            $cells.= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
            $cells.= wf_TableCell($eachpayment['note']);
            $cells.= wf_TableCell($eachpayment['admin']);
            $rows.= wf_TableRow($cells, 'row4');
            $total = $total + $eachpayment['summ'];
            $totalPaycount++;
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
    $colors = new ColorTagging();

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
            $userColor = $colors->GetUsersColor($eachdebtor['login']);
            $cell = wf_TableCell(@$alladrs[$eachdebtor['login']]);
            $cell.= wf_TableCell(@$allrealnames[$eachdebtor['login']] . "&nbsp&nbsp" . @$allphonedata[$eachdebtor['login']]['mobile']);
            $cell.= wf_TableCell($eachdebtor['Cash']);
            if ($alter_conf['FINREP_TARIFF']) {
                $cell.= wf_TableCell($alltariffs[$eachdebtor['login']]);
            }
            $cell.= wf_TableCell(@$allnotes[$eachdebtor['login']] . "&nbsp&nbsp" . @$allcomments[$eachdebtor['login']]);
            $cell.= wf_TableCell(@$allonu[$eachdebtor['login']]);
            $cell.= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachdebtor['login'], (web_profile_icon() . ' ' . $eachdebtor['login']), false, ''));
            if (!empty($userColor)) {
                $style = "background-color:$userColor";
                $rows.= wf_TableRowStyled($cell, 'row4', $style);
            } else {
                $rows.= wf_TableRow($cell, 'row4');
            }
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
        $cells.= wf_HiddenInput("action", "debtors");
        $cells.= wf_TableCell(web_CitySelectorAc());
        $form.= wf_TableRow($cells, 'row3');
    } else {
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("action", "debtors");
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
        $cells.= wf_HiddenInput("action", "city_payments");
        if (isset($_GET['monthsel'])) {
            $cells.= wf_HiddenInput('monthsel', $_GET['monthsel']);
        }
        $cells.= wf_TableCell(web_CitySelectorAc());
        $form.= wf_TableRow($cells, 'row3');
    } else {
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("action", "city_payments");
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
        $cells.= wf_HiddenInput("action", "usersearch");
        $cells.= wf_TableCell(web_CitySelectorAc());
        $form.= wf_TableRow($cells, 'row3');
    } else {
        $cityname = zb_AddressGetCityData($_GET['citysel']);
        $cityname = $cityname['cityname'];
        $cells = wf_TableCell(__('City'), '40%');
        $cells.= wf_HiddenInput("module", "per_city_action");
        $cells.= wf_HiddenInput("action", "usersearch");
        $cells.= wf_TableCell(web_ok_icon() . ' ' . $cityname . wf_HiddenInput('citysearch', $_GET['citysel']));
        $cells.= wf_TableCell(wf_Submit(__('Find')));
        $form.= wf_TableRow($cells, 'row3');
    }
    $form.=wf_tag('table', true);
    $form.=wf_tag('form', true);
    return($form);
}

function GetAllCreditedUsers() {
    $date = date("Y-m");
    $query = "SELECT * FROM `zbssclog` WHERE `date` LIKE '" . $date . "%';";
    $allCredited = simple_queryall($query);
    if (!empty($allCredited)) {
        foreach ($allCredited as $eachCredited) {
            $creditedUsers[$eachCredited['login']] = $eachCredited['date'];
        }
        return($creditedUsers);
    } else {
        return(false);
    }
}

function web_ReportCityShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $report_name = wf_tag('h2') . __("Debtors by city") . wf_tag('h2', true);
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }
    $allphonedata = zb_UserGetAllPhoneData();
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();

    $i = 0;
    $style = '
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        <style type="text/css">
            table.printrm tbody {
                counter-reset: sortabletablescope;
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm thead tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: "ID";
                display: table-cell;
            }
            table.printrm tbody tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: counter(sortabletablescope);
                counter-increment: sortabletablescope;
                display: table-cell;
            }
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
        </style>';
    $cells = '';
    if ($address) {
        $cells.= wf_TableCell(__('Full address'));
    }
    if ($realnames) {
        $cells.= wf_TableCell(__('Real Name'));
    }
    foreach ($titles as $eachtitle) {
        $cells.= wf_TableCell(__($eachtitle));
    }

    $rows = wf_TableRow($cells);
    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $i++;
            $cells = '';
            if ($address) {
                $cells.= wf_TableCell(@$alladdress[$eachdata['login']]);
            }
            if ($realnames) {
                $cells.= wf_TableCell(@$allrealnames[$eachdata['login']] . "&nbsp" . @$allphonedata[$eachdata['login']]['mobile']);
            }
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.= wf_TableCell(@$alltariffs[$eachdata['login']]);
            }
            $cells.= wf_TableCell(@$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']]);
            $cells.= wf_TableCell(@$allonu[$eachdata['login']]);
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $cells.= wf_TableCell($eachdata[$eachkey]);
                }
            }
            $rows.=wf_TableRow($cells);
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable printrm');
    if ($rowcount) {
        $result.=wf_tag('strong') . __('Total') . ': ' . $i . wf_tag('strong', true);
    }
    print($style . $report_name . $result);
    die();
}

function web_MonthSelector() {
    $mcells = '';
    $allmonth = months_array_localized();
    foreach ($allmonth as $io => $each) {
        if (isset($_GET['citysel'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io . "&citysel=" . $_GET['citysel'], $each, false, 'ubButton'));
        } elseif (isset($_GET['citysearch'])) {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io . "&citysearch=" . $_GET['citysearch'], $each, false, 'ubButton'));
        } else {
            $mcells.= wf_TableCell(wf_Link("?module=per_city_action&action=city_payments&monthsel=" . $io, $each, false, 'ubButton'));
        }
    }
    return ($mcells);
}

function web_ReportDebtorsShowPrintable($titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $report_name = wf_tag('h2') . __("Debtors by city") . wf_tag('h2', true);
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }
    $allphonedata = zb_UserGetAllPhoneData();
    $allnotes = GetAllNotes();
    $allcomments = GetAllComments();
    $allonu = GetAllOnu();
    $allCredited = GetAllCreditedUsers();
    $i = 0;
    $style = '
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        <style type="text/css">
            table.printrm tbody {
                counter-reset: sortabletablescope;
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
            }
            table.printrm thead tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: "ID";
                display: table-cell;
            }
            table.printrm tbody tr::before {
                border-width: 1px;
                padding: 1px;
                border-style: dashed;
                border-color: gray;
                background-color: white;
                -moz-border-radius: ;
                text-align: center;
                vertical-align: middle;
                content: counter(sortabletablescope);
                counter-increment: sortabletablescope;
                display: table-cell;
            }
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
        </style>';
    $cells = '';
    if ($address) {
        $cells.=wf_TableCell(__('Full address'));
    }
    if ($realnames) {
        $cells.=wf_TableCell(__('Real Name'));
    }
    foreach ($titles as $eachtitle) {
        $cells.= wf_TableCell(__($eachtitle));
    }
    $rows = wf_TableRow($cells);

    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $i++;
            $cells = '';
            if ($address) {
                $cells.=wf_TableCell(@$alladdress[$eachdata['login']]);
            }
            if ($realnames) {
                $cells.=wf_TableCell(@$allrealnames[$eachdata['login']] . "&nbsp " . @$allphonedata[$eachdata['login']]['mobile']);
            }
            if ($alter_conf['FINREP_TARIFF']) {
                $cells.=wf_TableCell(@$alltariffs[$eachdata['login']]);
            }
            $cells.= wf_TableCell(@$allnotes[$eachdata['login']] . " " . @$allcomments[$eachdata['login']]);
            $cells.= wf_TableCell(@$allonu[$eachdata['login']]);
            $cells.= wf_TableCell(@$allCredited[$eachdata['login']]);
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $cells.=wf_TableCell($eachdata[$eachkey]);
                }
            }
            $rows.=wf_TableRow($cells);
        }
    }
    $result = wf_TableBody($rows, '100%', '0', 'sortable printrm');
    if ($rowcount) {
        $result.='<strong>' . __('Total') . ': ' . $i . '</strong>';
    }
    print($style . $report_name . $result);
    die();
}
