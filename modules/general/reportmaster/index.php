<?php

if (cfr('REPORTMASTER')) {

    /**
     * Renders custom report
     * 
     * @param string $reportfile
     * @param string $report_name
     * @param array $titles
     * @param array $keys
     * @param array $alldata
     * @param bool $address
     * @param bool $realnames
     * @param bool $rowcount
     * 
     * @return void
     */
    function web_ReportMasterShow($reportfile, $report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $report_name = __($report_name) . ' ' . wf_tag('a', false, '', 'href="?module=reportmaster&view=' . $reportfile . '&printable=true" target="_BLANK"');
        $report_name .= wf_img('skins/printer_small.gif', __('Print'));
        $report_name .= wf_tag('a', true);

        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $i = 0;



        $result = wf_tag('table', false, '', 'width="100%" class="sortable" border="0"');

        $result .= wf_tag('tr', false, 'row1');

        foreach ($titles as $eachtitle) {
            $result .= wf_tag('td') . __($eachtitle) . wf_tag('td', true);
        }

        if ($address) {
            $result .= wf_tag('td') . __('Full address') . wf_tag('td', true);
        }

        if ($realnames) {
            $result .= wf_tag('td') . __('Real Name') . wf_tag('td', true);
        }

        $result .= wf_tag('tr', true);

        if (!empty($alldata)) {
            foreach ($alldata as $io => $eachdata) {
                $i++;
                $result .= wf_tag('tr', false, 'row3');

                foreach ($keys as $eachkey) {
                    if (array_key_exists($eachkey, $eachdata)) {
                        $result .= wf_tag('td') . $eachdata[$eachkey] . wf_tag('td', true);
                    }
                }
                if ($address) {
                    $result .= wf_tag('td') . @$alladdress[$eachdata['login']] . wf_tag('td', true);
                }
                if ($realnames) {
                    $result .= wf_tag('td') . wf_Link('?module=userprofile&username=' . $eachdata['login'], web_profile_icon() . ' ' . @$allrealnames[$eachdata['login']]) . wf_tag('td', true);
                    ;
                }
                $result .= wf_tag('tr', true);
            }
        }
        $result .= wf_tag('table', true);
        if ($rowcount) {
            $result .= wf_tag('strong') . __('Total') . ': ' . $i . wf_tag('strong', true);
            ;
        }
        show_window($report_name, $result);
    }

    /**
     * Renders printable report
     * 
     * @param string $report_name
     * @param array $titles
     * @param array $keys
     * @param array $alldata
     * @param bool $address
     * @param bool $realnames
     * @param bool $rowcount
     * 
     * @return void
     */
    function web_ReportMasterShowPrintable($report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $report_name = wf_tag('h2') . __($report_name) . wf_tag('h2', true);
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $i = 0;
        $result = wf_tag('style', false, '', ' type="text/css"');
        $result .= '
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
        ';
        $result .= wf_tag('style', true);
        $result .= wf_tag('table', false, 'printrm', 'width="100%"');
        $result .= wf_tag('tr');

        foreach ($titles as $eachtitle) {
            $result .= wf_tag('td') . __($eachtitle) . wf_tag('td', true);
        }
        if ($address) {
            $result .= wf_tag('td') . __('Full address') . wf_tag('td', true);
        }
        if ($realnames) {
            $result .= wf_tag('td') . __('Real Name') . wf_tag('td', true);
        }
        $result .= wf_tag('tr', true);

        if (!empty($alldata)) {
            foreach ($alldata as $io => $eachdata) {
                $i++;
                $result .= wf_tag('tr');
                foreach ($keys as $eachkey) {
                    if (array_key_exists($eachkey, $eachdata)) {
                        $result .= wf_tag('td') . $eachdata[$eachkey] . wf_tag('td', true);
                    }
                }
                if ($address) {
                    $result .= wf_tag('td') . @$alladdress[$eachdata['login']] . wf_tag('td', true);
                }
                if ($realnames) {
                    $result .= wf_tag('td') . @$allrealnames[$eachdata['login']] . wf_tag('td', true);
                }
                $result .= wf_tag('tr', true);
            }
        }
        $result .= wf_tag('table', true);
        if ($rowcount) {
            $result .= wf_tag('strong') . __('Total') . ': ' . $i . wf_tag('strong', true);
        }
        print($report_name . $result);
        die();
    }

    /**
     * Returns default trigger selector for reports options
     * 
     * @param string $name
     * @param int $state
     * @return string
     */
    function web_RMTriggerSelector($name, $state = '') {
        $result = web_TriggerSelector($name, $state);
        return ($result);
    }

    /**
     * Renders available reports list
     * 
     * @return string
     */
    function web_ReportMasterShowReportsList() {
        $messages = new UbillingMessageHelper();
        $reports_path = DATA_PATH . "reports/";
        $allreports = rcms_scandir($reports_path);


        $cells = wf_TableCell(__('Report name'));
        if (cfr('REPORTMASTERADM')) {
            $cells .= wf_TableCell(__('Actions'));
        }
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allreports)) {
            foreach ($allreports as $eachreport) {
                $report_template = rcms_parse_ini_file($reports_path . $eachreport);
                $cells = wf_TableCell(wf_Link('?module=reportmaster&view=' . $eachreport, __($report_template['REPORT_NAME'])));
                if (cfr('REPORTMASTERADM')) {
                    $actControls = wf_JSAlert('?module=reportmaster&delete=' . $eachreport, web_delete_icon(), $messages->getDeleteAlert());
                    $actControls .= wf_JSAlert('?module=reportmaster&edit=' . $eachreport, web_edit_icon(), $messages->getEditAlert());
                    $cells .= wf_TableCell($actControls);
                }
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Deletes existing report by its name
     * 
     * @param string $reportname
     * 
     * @return void
     */
    function zb_RMDeleteReport($reportname) {
        $reportname = vf($reportname);
        $reports_path = DATA_PATH . "reports/";
        unlink($reports_path . $reportname);
        log_register("ReportMaster DELETE `" . $reportname . "`");
    }

    /**
     * Views report by its name
     * 
     * @param string $reportname
     * 
     * @return void
     */
    function web_ReportMasterViewReport($reportname) {
        $reportname = vf($reportname);
        $reports_path = DATA_PATH . "reports/";
        //if valid report
        if (file_exists($reports_path . $reportname)) {
            $report_template = rcms_parse_ini_file($reports_path . $reportname);
            $data_query = simple_queryall($report_template['REPORT_QUERY']);
            $keys = explode(',', $report_template['REPORT_KEYS']);
            $titles = explode(',', $report_template['REPORT_FIELD_NAMES']);
            web_ReportMasterShow($reportname, $report_template['REPORT_NAME'], $titles, $keys, $data_query, $report_template['REPORT_ADDR'], $report_template['REPORT_RNAMES'], $report_template['REPORT_ROW_COUNT']);
        } else {
            show_error(__('Unknown report'));
        }
    }

    /**
     * Views report as printable result
     * 
     * @param string $reportname
     * 
     * @return void
     */
    function web_ReportMasterViewReportPrintable($reportname) {
        $reportname = vf($reportname);
        $reports_path = DATA_PATH . "reports/";
        //if valid report
        if (file_exists($reports_path . $reportname)) {
            $report_template = rcms_parse_ini_file($reports_path . $reportname);
            $data_query = simple_queryall($report_template['REPORT_QUERY']);
            $keys = explode(',', $report_template['REPORT_KEYS']);
            $titles = explode(',', $report_template['REPORT_FIELD_NAMES']);
            web_ReportMasterShowPrintable($report_template['REPORT_NAME'], $titles, $keys, $data_query, $report_template['REPORT_ADDR'], $report_template['REPORT_RNAMES'], $report_template['REPORT_ROW_COUNT']);
        } else {
            show_error(__('Unknown report'));
        }
    }

    /**
     * Shows report creation form
     * 
     * @return void
     */
    function web_ReportMasterShowAddForm() {
        $inputs = wf_TextInput('newreportname', __('Report name'), '', true, 40);
        $inputs .= wf_TextInput('newsqlquery', __('SQL Query'), '', true, 40);
        $inputs .= wf_TextInput('newdatakeys', __('Data keys, separated by comma'), '', true, 40);
        $inputs .= wf_TextInput('newfieldnames', __('Field names, separated by comma'), '', true, 40);
        $inputs .= web_RMTriggerSelector('newaddr') . ' ' . __('Show full address by login key') . wf_tag('br');
        $inputs .= web_RMTriggerSelector('newrnames') . ' ' . __('Show Real Names by login key') . wf_tag('br');
        $inputs .= web_RMTriggerSelector('newrowcount') . ' ' . __('Show data query row count') . wf_tag('br');
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');

        show_window(__('Create new report'), $form);
    }

    /**
     * Shows report editing form
     * 
     * @param string $reportfile
     */
    function web_ReportMasterShowEditForm($reportfile) {
        $reports_path = DATA_PATH . "reports/";
        $report_template = rcms_parse_ini_file($reports_path . $reportfile);

        $inputs = wf_TextInput('editreportname', __('Report name'), $report_template['REPORT_NAME'], true, 40);
        $inputs .= wf_TextInput('editsqlquery', __('SQL Query'), $report_template['REPORT_QUERY'], true, 40);
        $inputs .= wf_TextInput('editdatakeys', __('Data keys, separated by comma'), $report_template['REPORT_KEYS'], true, 40);
        $inputs .= wf_TextInput('editfieldnames', __('Field names, separated by comma'), $report_template['REPORT_FIELD_NAMES'], true, 40);
        $inputs .= web_RMTriggerSelector('editaddr', $report_template['REPORT_ADDR']) . ' ' . __('Show full address by login key') . wf_tag('br');
        $inputs .= web_RMTriggerSelector('editrnames', $report_template['REPORT_RNAMES']) . ' ' . __('Show Real Names by login key') . wf_tag('br');
        $inputs .= web_RMTriggerSelector('editrowcount', $report_template['REPORT_ROW_COUNT']) . ' ' . __('Show data query row count') . wf_tag('br');
        $inputs .= wf_Submit(__('Save'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        show_window(__('Edit report'), $form);
    }

    /**
     * Creates new report template file
     * 
     * @param string $newreportname
     * @param string $newsqlquery
     * @param string $newdatakeys
     * @param string $newfieldnames
     * @param int $newaddr
     * @param int $newrn
     * @param int $newrowcount
     * 
     * @return void
     */
    function zb_RMCreateReport($newreportname, $newsqlquery, $newdatakeys, $newfieldnames, $newaddr = 0, $newrn = 0, $newrowcount = 0) {
        $reports_path = DATA_PATH . "reports/";
        $newreportsavefile = time();
        $report_body = '
        REPORT_NAME =' . $newreportname . '
        REPORT_QUERY=' . $newsqlquery . '
        REPORT_KEYS=' . $newdatakeys . '
        REPORT_FIELD_NAMES=' . $newfieldnames . '
        REPORT_ADDR=' . $newaddr . '
        REPORT_RNAMES=' . $newrn . '
        REPORT_ROW_COUNT=' . $newrowcount . '
        ';
        file_put_contents($reports_path . $newreportsavefile, $report_body);
        log_register("ReportMaster CREATE `" . $newreportsavefile . "`");
    }

    /**
     * Changes report template file
     * 
     * @param string $editreportsavefile
     * @param string $editreportname
     * @param string $editsqlquery
     * @param string $editdatakeys
     * @param string $editfieldnames
     * @param int $editaddr
     * @param int $editrn
     * @param int $editrowcount
     * 
     * @return void
     */
    function zb_RMEditReport($editreportsavefile, $editreportname, $editsqlquery, $editdatakeys, $editfieldnames, $editaddr = 0, $editrn = 0, $editrowcount = 0) {
        $reports_path = DATA_PATH . "reports/";
        $report_body = '
        REPORT_NAME =' . $editreportname . '
        REPORT_QUERY=' . $editsqlquery . '
        REPORT_KEYS=' . $editdatakeys . '
        REPORT_FIELD_NAMES=' . $editfieldnames . '
        REPORT_ADDR=' . $editaddr . '
        REPORT_RNAMES=' . $editrn . '
        REPORT_ROW_COUNT=' . $editrowcount . '
        ';
        file_put_contents($reports_path . $editreportsavefile, $report_body);
        log_register("ReportMaster CHANGE `" . $editreportsavefile . "`");
    }

    /**
     * Exports existing userbase as CSV format 
     * 
     * @return void
     */
    function zb_RMExportUserbaseCsv() {
        $allusers = zb_UserGetAllStargazerData();
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $allcontracts = zb_UserGetAllContracts();
        $allmac = array();
        $mac_q = "SELECT * from `nethosts`";
        $allnh = simple_queryall($mac_q);

        if (!empty($allnh)) {
            foreach ($allnh as $nh => $eachnh) {
                $allmac[$eachnh['ip']] = $eachnh['mac'];
            }
        }

        $result = '';
        //options
        $delimiter = ";";
        $in_charset = 'utf-8';
        $out_charset = 'windows-1251';
        /////////////////////
        if (!empty($allusers)) {
            $result .= __('Login') . $delimiter . __('Password') . $delimiter . __('IP') . $delimiter . __('MAC') . $delimiter . __('Tariff') . $delimiter . __('Cash') . $delimiter . __('Credit') . $delimiter . __('Credit expire') . $delimiter . __('Address') . $delimiter . __('Real Name') . $delimiter . __('Contract') . $delimiter . __('AlwaysOnline') . $delimiter . __('Disabled') . $delimiter . __('User passive') . "\n";
            foreach ($allusers as $io => $eachuser) {
                //credit expirity
                if ($eachuser['CreditExpire'] != 0) {
                    $creditexpire = date("Y-m-d", $eachuser['CreditExpire']);
                } else {
                    $creditexpire = '';
                }
                //user mac
                if (isset($allmac[$eachuser['IP']])) {
                    $usermac = $allmac[$eachuser['IP']];
                } else {
                    $usermac = '';
                }

                $result .= $eachuser['login'] . $delimiter . $eachuser['Password'] . $delimiter . $eachuser['IP'] . $delimiter . $usermac . $delimiter . $eachuser['Tariff'] . $delimiter . $eachuser['Cash'] . $delimiter . $eachuser['Credit'] . $delimiter . $creditexpire . $delimiter . @$alladdress[$eachuser['login']] . $delimiter . @$allrealnames[$eachuser['login']] . $delimiter . @$allcontracts[$eachuser['login']] . $delimiter . $eachuser['AlwaysOnline'] . $delimiter . $eachuser['Down'] . $delimiter . $eachuser['Passive'] . "\n";
            }
            if ($in_charset != $out_charset) {
                //not contains unicode symbols
                if (strlen($result) == strlen(utf8_decode($result))) {
                    @$encoded = iconv($in_charset, $out_charset, $result);
                    if ($encoded != false) {
                        $result = $encoded;
                    }
                }
            }
            log_register('DOWNLOAD FILE `userbase.csv`');
            // push data for csv handler
            header('Content-type: application/ms-excel');
            header('Content-Disposition: attachment; filename=userbase.csv');
            echo $result;
            die();
        }
    }

// show reports list
    if (cfr('REPORTMASTERADM')) {
        $export_link = wf_Link('?module=reportmaster&exportuserbase=excel', wf_img("skins/excel.gif", __('Export userbase')), false);
    } else {
        $export_link = '';
    }

    $newreport_link = (cfr('REPORTMASTERADM')) ? wf_Link('?module=reportmaster&add=true', web_add_icon(), false) : '';
    $action_links = ' ' . $export_link . ' ' . $newreport_link;
    show_window(__('Available reports') . $action_links, web_ReportMasterShowReportsList());

//userbase exporting
    if (wf_CheckGet(array('exportuserbase'))) {
        zb_RMExportUserbaseCsv();
    }


//create new report
    if ((isset($_POST['newreportname'])) AND ( isset($_POST['newsqlquery'])) AND ( isset($_POST['newdatakeys'])) AND ( isset($_POST['newfieldnames']))) {
        if (cfr('REPORTMASTERADM')) {
            zb_RMCreateReport($_POST['newreportname'], $_POST['newsqlquery'], $_POST['newdatakeys'], $_POST['newfieldnames'], $_POST['newaddr'], $_POST['newrnames'], $_POST['newrowcount']);
            rcms_redirect("?module=reportmaster");
        } else {
            show_error(__('You cant control this module'));
        }
    }

//delete existing report
    if (isset($_GET['delete'])) {
        if (cfr('REPORTMASTERADM')) {
            zb_RMDeleteReport($_GET['delete']);
            rcms_redirect("?module=reportmaster");
        } else {
            show_error(__('You cant control this module'));
        }
    }

//if adding new report
    if (isset($_GET['add'])) {
        if (cfr('REPORTMASTERADM')) {
            web_ReportMasterShowAddForm();
        } else {
            show_error(__('You cant control this module'));
        }
    }

//and if editing
    if (isset($_GET['edit'])) {
        if (cfr('REPORTMASTERADM')) {
            web_ReportMasterShowEditForm($_GET['edit']);
            if ((isset($_POST['editreportname'])) AND ( isset($_POST['editsqlquery'])) AND ( isset($_POST['editdatakeys'])) AND ( isset($_POST['editfieldnames']))) {
                zb_RMEditReport($_GET['edit'], $_POST['editreportname'], $_POST['editsqlquery'], $_POST['editdatakeys'], $_POST['editfieldnames'], $_POST['editaddr'], $_POST['editrnames'], $_POST['editrowcount']);
                rcms_redirect("?module=reportmaster");
            }
        } else {
            show_error(__('You cant control this module'));
        }
    }


// view reports
    if (isset($_GET['view'])) {
        if (!isset($_GET['printable'])) {
            // natural view    
            web_ReportMasterViewReport($_GET['view']);
        } else {
            //or printable
            web_ReportMasterViewReportPrintable($_GET['view']);
        }
    }
} else {
    show_error(__('You cant control this module'));
}
?>
