<?php

class ReportMaster {

    /**
     * Contains message helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all available reports as reportId=>reportData
     *
     * @var array
     */
    protected $allReports = array();

    /**
     * Contains current instance administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Some predefined paths, URLs/routes etc...
     */
    const PATH_REPORTS = 'content/reports/';
    const URL_ME = '?module=reportmaster';
    const URL_TASKBAR = '?module=taskbar';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const ROUTE_ADD = 'add';
    const ROUTE_EDIT = 'edit';
    const ROUTE_VIEW = 'view';
    const ROUTE_DELETE = 'delete';
    const ROUTE_RENDERER = 'renderer';
    const ROUTE_BASEEXPORT = 'exportuserbase';
    const PROUTE_NEWTYPE = 'newreporttype';
    const PROUTE_NEWNAME = 'newreportname';
    const PROUTE_NEWQUERY = 'newquery';
    const PROUTE_NEWKEYS = 'newdatakeys';
    const PROUTE_NEWFIELDS = 'newfieldnames';
    const PROUTE_NEWADDR = 'newaddr';
    const PROUTE_NEWRNAMES = 'newrnames';
    const PROUTE_NEWROWCOUNT = 'newrowcount';
    const PROUTE_EDTYPE = 'editreporttype';
    const PROUTE_EDNAME = 'editreportname';
    const PROUTE_EDQUERY = 'editquery';
    const PROUTE_EDKEYS = 'editdatakeys';
    const PROUTE_EDFIELDS = 'editfieldnames';
    const PROUTE_EDADDR = 'editaddr';
    const PROUTE_EDRNAMES = 'editrnames';
    const PROUTE_EDROWCOUNT = 'editrowcount';
    const PROUTE_EDADMACL = 'editadminsacl';
    const PROUTE_EDONTB = 'editontb';
    const PROUTE_EDICON = 'editicon';
    const MOD_PRINT = 'printable';
    const MOD_CSV = 'csv';

    public function __construct() {
        $this->initMessages();
        $this->setLogin();
        $this->loadReports();
    }

    /**
     * Inits message helper instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets admin login for current instance. Required for per/report rights check
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Returns array of existing reports as reportId=>reportData
     * 
     * @return string
     */
    public function getReports() {
        return($this->allReports);
    }

    /**
     * Checks have current user access for some report
     * 
     * @param string $reportId
     * 
     * @return bool
     */
    public function isMeAllowed($reportId) {
        $result = true;
        //root administrators have access for all reports by default
        if (!cfr('ROOT')) {
            $result = false;
            if (isset($this->allReports[$reportId])) {
                if (!empty($this->allReports[$reportId]['REPORT_ALLOWADMINS'])) {
                    $admAcl = explode(',', $this->allReports[$reportId]['REPORT_ALLOWADMINS']);
                    $admAcl = array_flip($admAcl);
                    if (isset($admAcl[$this->myLogin])) {
                        //i`m listed there?
                        $result = true;
                    }
                } else {
                    //empty admins ACL means access for all
                    $result = true;
                }
            } else {
                //no access for reports which not exists, lol.
                $result = false;
            }
        }
        return($result);
    }

    /**
     * Loads all available reports from filesystem
     * 
     * @return void
     */
    protected function loadReports() {
        $allReports = rcms_scandir(self::PATH_REPORTS);
        if (!empty($allReports)) {
            foreach ($allReports as $eachReport) {
                $reportData = rcms_parse_ini_file(self::PATH_REPORTS . $eachReport);
                //legacy reports is SQL by default
                if (!isset($reportData['REPORT_TYPE'])) {
                    $reportData['REPORT_TYPE'] = 'SQL';
                }

                //legacy reports allowed for all by default
                if (!isset($reportData['REPORT_ALLOWADMINS'])) {
                    $reportData['REPORT_ALLOWADMINS'] = '';
                }

                //on TB and icon options disabled by default
                if (!isset($reportData['REPORT_ONTB'])) {
                    $reportData['REPORT_ONTB'] = 0;
                }

                if (!isset($reportData['REPORT_ICON'])) {
                    $reportData['REPORT_ICON'] = '';
                }

                $this->allReports[$eachReport] = $reportData;
            }
        }
    }

    /**
     * Renders available reports list
     * 
     * @return string
     */
    public function renderReportsList() {
        $result = '';
        if (!empty($this->allReports)) {
            $cells = wf_TableCell(__('Report name'));
            if (cfr('REPORTMASTERADM')) {
                $cells .= wf_TableCell(__('Actions'));
            }
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allReports as $eachReport => $reportData) {
                if ($this->isMeAllowed($eachReport)) {
                    $cells = wf_TableCell(wf_Link(self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $eachReport, __($reportData['REPORT_NAME'])));
                    if (cfr('REPORTMASTERADM')) {
                        $actControls = '';
                        if (empty($reportData['REPORT_ALLOWADMINS'])) {
                            $reportAccessLabel = wf_img('skins/icon_unlock.png', __('Access for all')) . ' ';
                        } else {
                            $reportAccessLabel = wf_img('skins/icon_key.gif', __('Access restricted')) . ' ';
                        }

                        $actControls .= $reportAccessLabel;
                        $actControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $eachReport, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                        $actControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $eachReport, web_edit_icon(), $this->messages->getEditAlert()) . ' ';
                        $cells .= wf_TableCell($actControls);
                    }
                    $rows .= wf_TableRow($cells, 'row5');
                }
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     * Exports existing userbase as CSV format 
     * 
     * @return void
     */
    public function exportUserbaseCsv() {
        $result = '';

        $allusers = zb_UserGetAllStargazerData();
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $allcontracts = zb_UserGetAllContracts();
        $allmac = zb_UserGetAllIpMACs();
        $delimiter = ';';
        $curDate = curdatetime();

        $headers = array(
            __('Login'),
            __('Password'),
            __('IP'),
            __('MAC'),
            __('Tariff'),
            __('Cash'),
            __('Credit'),
            __('Credit expire'),
            __('Address'),
            __('Real Name'),
            __('Contract'),
            __('AlwaysOnline'),
            __('Disabled'),
            __('User passive')
        );

        if (!empty($allusers)) {
            $result .= implode($delimiter, $headers) . PHP_EOL;

            foreach ($allusers as $io => $eachuser) {
                $creditexpire = '';
                $usermac = '';
                //credit expirity
                if ($eachuser['CreditExpire'] != 0) {
                    $creditexpire = date("Y-m-d", $eachuser['CreditExpire']);
                }
                //user mac
                if (isset($allmac[$eachuser['IP']])) {
                    $usermac = $allmac[$eachuser['IP']];
                }

                $rowData = array(
                    $eachuser['login'],
                    $eachuser['Password'],
                    $eachuser['IP'],
                    $usermac,
                    $eachuser['Tariff'],
                    $eachuser['Cash'],
                    $eachuser['Credit'],
                    $creditexpire,
                    @$alladdress[$eachuser['login']],
                    @$allrealnames[$eachuser['login']],
                    @$allcontracts[$eachuser['login']],
                    $eachuser['AlwaysOnline'],
                    $eachuser['Down'],
                    $eachuser['Passive']
                );

                $result .= implode($delimiter, $rowData) . PHP_EOL;
            }

            log_register('DOWNLOAD FILE `userbase_' . $curDate . '.csv`');
            // push data for csv handler
            header('Content-type: application/ms-excel');
            header('Content-Disposition: attachment; filename=userbase_' . $curDate . '.csv');
            print($result);
            die();
        }
    }

    /**
     * Renders default back control
     * 
     * @return string
     */
    protected function renderBackControl() {
        $result = '';
        $backUrl = self::URL_ME;
        if (ubRouting::get('back') == 'tb') {
            $backUrl = self::URL_TASKBAR;
        }
        $result .= wf_BackLink($backUrl);
        return($result);
    }

    /**
     * Creates new report template file. 
     * 
     * @param string $type report template type: SQL or ONEPUNCH
     * @param string $name report name
     * @param string $query SQL query for SQL-type reports or existing one-punch script alias for ONEPUNCH
     * @param string $keys field keys to use in SQL report
     * @param string $fields field names to associate with keys in SQL report
     * @param int $addr address rendering by login field flag
     * @param int $rn realname rendering by login field flag
     * @param int $rowcount result rows count rendering flag
     * 
     * @return void
     */
    public function createReport($type, $name, $query, $keys = '', $fields = '', $addr = 0, $rn = 0, $rowcount = 0) {
        $fileName = 'rm' . time();
        $pathToSave = self::PATH_REPORTS . $fileName;
        $isOk = false;

        if (!empty($type) AND ! empty($name) AND ! empty($query)) {
            //base params here?
            $isOk = true;
        }

        $reportBody = '';
        $reportBody .= 'REPORT_NAME="' . $name . '"' . PHP_EOL;
        $reportBody .= 'REPORT_TYPE="' . $type . '"' . PHP_EOL;
        $reportBody .= 'REPORT_ALLOWADMINS=""' . PHP_EOL; //allows all by default
        $reportBody .= 'REPORT_QUERY="' . $query . '"' . PHP_EOL;
        $reportBody .= 'REPORT_ONTB="0"' . PHP_EOL;
        $reportBody .= 'REPORT_ICON=""' . PHP_EOL;

        if ($type == 'SQL') {
            $reportBody .= 'REPORT_KEYS="' . $keys . '"' . PHP_EOL;
            $reportBody .= 'REPORT_FIELD_NAMES="' . $fields . '"' . PHP_EOL;
            $reportBody .= 'REPORT_ADDR="' . $addr . '"' . PHP_EOL;
            $reportBody .= 'REPORT_RNAMES="' . $rn . '"' . PHP_EOL;
            $reportBody .= 'REPORT_ROW_COUNT="' . $rowcount . '"' . PHP_EOL;
        }

        if ($type == 'onepunch') {
            //script exists?
            $onePunch = new OnePunch($query);
            $scriptCode = $onePunch->getScriptContent($query);
            if (!empty($scriptCode)) {
                $isOk = true;
            } else {
                $isOk = false;
            }
        }

        if ($isOk) {
            file_put_contents($pathToSave, $reportBody);
            log_register('REPORTMASTER CREATE ' . $type . ' REPORT `' . $fileName . '`');
        } else {
            log_register('REPORTMASTER CREATE FAIL ' . $type . ' REPORT `' . $fileName . '`');
        }
    }

    /**
     * Renders new report creation form
     * 
     * @return string
     */
    public function renderCreateForm($type = 'sql') {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ADD . '=sql', wf_img('skins/icon_restoredb.png') . ' ' . __('SQL Query'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ADD . '=onepunch', wf_img('skins/icon_php.png') . ' ' . __('One-Punch') . ' ' . __('Script'), false, 'ubButton') . ' ';
        $result .= wf_delimiter(1);
        $inputs = '';
        if ($type == 'sql') {
            $inputs .= wf_HiddenInput(self::PROUTE_NEWTYPE, 'SQL');
            $inputs .= wf_TextInput(self::PROUTE_NEWNAME, __('Report name') . $sup, '', true, 40);
            $inputs .= wf_TextInput(self::PROUTE_NEWQUERY, __('SQL Query') . $sup, '', true, 60);
            $inputs .= wf_TextInput(self::PROUTE_NEWKEYS, __('Data keys, separated by comma') . $sup, '', true, 40);
            $inputs .= wf_TextInput(self::PROUTE_NEWFIELDS, __('Field names, separated by comma') . $sup, '', true, 40);
            $inputs .= web_TriggerSelector(self::PROUTE_NEWADDR) . ' ' . __('Show full address by login key') . wf_tag('br');
            $inputs .= web_TriggerSelector(self::PROUTE_NEWRNAMES) . ' ' . __('Show Real Names by login key') . wf_tag('br');
            $inputs .= web_TriggerSelector(self::PROUTE_NEWROWCOUNT) . ' ' . __('Show data query row count') . wf_tag('br');
        }

        if ($type == 'onepunch') {
            $onePunch = new OnePunch();
            $inputs .= wf_HiddenInput(self::PROUTE_NEWTYPE, 'ONEPUNCH');
            $inputs .= wf_TextInput(self::PROUTE_NEWNAME, __('Report name') . $sup, '', true, 40);
            $inputs .= wf_TextInput(self::PROUTE_NEWQUERY, __('One-Punch') . ' ' . __('Script') . ' ' . __('Alias') . $sup, '', true, 40);
        }
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

    /**
     * Deletes existing report by its name
     * 
     * @param string $reportname
     * 
     * @return void
     */
    public function deleteReport($reportname) {
        unlink(self::PATH_REPORTS . $reportname);
        log_register('REPORTMASTER DELETE REPORT `' . $reportname . '`');
    }

    /**
     * Renders some SQL report into viewport
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
    protected function showSqlReport($reportfile, $report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $report_name = __($report_name) . ' ';
        $urlPrint = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $reportfile . '&' . self::ROUTE_RENDERER . '=' . self::MOD_PRINT;
        $urlCsv = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $reportfile . '&' . self::ROUTE_RENDERER . '=' . self::MOD_CSV;
        $report_name .= wf_Link($urlPrint, web_icon_print(), false, '', 'target="_BLANK"') . ' ';
        $report_name .= wf_Link($urlCsv, wf_img('skins/excel.gif', __('Export') . ' CSV'), false) . ' ';
        $result = $this->getReportData($reportfile, $report_name, $titles, $keys, $alldata, $address, $realnames, $rowcount);
        $result .= wf_delimiter(1);
        $result .= $this->renderBackControl();
        show_window($report_name, $result);
    }

    /**
     * Renders report as CSV file to download
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
    protected function exportSqlToCSV($reportfile, $report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $result = '';
        $delimiter = ';';

        $filename = zb_TranslitString($report_name);
        $filename = str_replace(' ', '_', $filename) . '_' . date("Y-m-d_His") . '.csv';

        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $i = 0;

        //report titles
        foreach ($titles as $eachtitle) {
            $result .= __($eachtitle) . $delimiter;
        }

        if ($address) {
            $result .= __('Full address') . $delimiter;
        }

        if ($realnames) {
            $result .= __('Real Name') . $delimiter;
        }
        $result .= PHP_EOL;

        //report data cells
        if (!empty($alldata)) {
            foreach ($alldata as $io => $eachdata) {
                $i++;
                foreach ($keys as $eachkey) {
                    if (array_key_exists($eachkey, $eachdata)) {
                        $result .= $eachdata[$eachkey] . $delimiter;
                    }
                }

                if ($address) {
                    $result .= @$alladdress[$eachdata['login']] . $delimiter;
                }
                if ($realnames) {
                    $result .= @$allrealnames[$eachdata['login']] . $delimiter;
                }
                $result .= PHP_EOL;
            }
        }

        header('Content-type: application/ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        die($result);
    }

    /**
     * Renders SQL report as printable table
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
    protected function showSqlPrintable($reportfile, $report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $result = $this->getReportData($report_name, $report_name, $titles, $keys, $alldata, $address, $realnames, $rowcount);
        $result = zb_ReportPrintable(__($report_name), $result);
        die($result);
    }

    /**
     * Returns custom-report data
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
     * @return string
     */
    protected function getReportData($reportfile, $report_name, $titles, $keys, $alldata, $address = 0, $realnames = 0, $rowcount = 0) {
        $result = '';
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
                $result .= wf_tag('tr', false, 'row5');

                foreach ($keys as $eachkey) {
                    if (array_key_exists($eachkey, $eachdata)) {
                        $result .= wf_tag('td') . $eachdata[$eachkey] . wf_tag('td', true);
                    }
                }
                if ($address) {
                    $result .= wf_tag('td') . @$alladdress[$eachdata['login']] . wf_tag('td', true);
                }
                if ($realnames) {
                    $result .= wf_tag('td') . wf_Link(self::URL_USERPROFILE . $eachdata['login'], web_profile_icon() . ' ' . @$allrealnames[$eachdata['login']]) . wf_tag('td', true);
                }
                $result .= wf_tag('tr', true);
            }
        }
        $result .= wf_tag('table', true);
        if ($rowcount) {
            $result .= wf_tag('strong') . __('Total') . ': ' . $i . wf_tag('strong', true);
        }
        return($result);
    }

    /**
     * Renders some report by its reportId aka template name
     * 
     * @param string $reportId
     * 
     * @return void
     */
    public function renderReport($reportId) {
        if (isset($this->allReports[$reportId])) {
            if ($this->isMeAllowed($reportId)) {
                $reportData = $this->allReports[$reportId];

                //normal SQL report
                if ($reportData['REPORT_TYPE'] == 'SQL') {
                    $data_query = simple_queryall($reportData['REPORT_QUERY']);
                    $keys = explode(',', $reportData['REPORT_KEYS']);
                    $titles = explode(',', $reportData['REPORT_FIELD_NAMES']);
                    //detecting renderer type
                    if (ubRouting::checkGet(self::ROUTE_RENDERER)) {
                        //CSV export modifier
                        if (ubRouting::get(self::ROUTE_RENDERER) == self::MOD_CSV) {
                            $this->exportSqlToCSV($reportId, $reportData['REPORT_NAME'], $titles, $keys, $data_query, $reportData['REPORT_ADDR'], $reportData['REPORT_RNAMES'], $reportData['REPORT_ROW_COUNT']);
                        }
                        //Printable view modifier
                        if (ubRouting::get(self::ROUTE_RENDERER) == self::MOD_PRINT) {
                            $this->showSqlPrintable($reportId, $reportData['REPORT_NAME'], $titles, $keys, $data_query, $reportData['REPORT_ADDR'], $reportData['REPORT_RNAMES'], $reportData['REPORT_ROW_COUNT']);
                        }
                    } else {
                        //just render as normal table
                        $this->showSqlReport($reportId, $reportData['REPORT_NAME'], $titles, $keys, $data_query, $reportData['REPORT_ADDR'], $reportData['REPORT_RNAMES'], $reportData['REPORT_ROW_COUNT']);
                    }
                }

                if ($reportData['REPORT_TYPE'] == 'ONEPUNCH') {
                    $onePunch = new OnePunch($reportData['REPORT_QUERY']);
                    $reportCode = $onePunch->getScriptContent($reportData['REPORT_QUERY']);
                    if (!empty($reportCode)) {
                        eval($reportCode);
                    } else {
                        show_error(__('One-Punch') . ' ' . __('script') . ' ' . __('is empty'));
                    }
                    show_window('', $this->renderBackControl());
                }
            } else {
                log_register('REPORTMASTER VIEW FAIL REPORT `' . $reportId . '` ACCESS VIOLATION');
                show_error(__('Access denied'));
                show_window('', $this->renderBackControl());
            }
        } else {
            show_error(__('Unknown report'));
        }
    }

    /**
     * Renders existing report editing form
     * 
     * @param string $reportId
     * 
     * @return string
     */
    public function renderEditForm($reportId) {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $reportId = ubRouting::filters($reportId, 'mres');
        if (isset($this->allReports[$reportId])) {
            $reportData = $this->allReports[$reportId];
            $reportType = $reportData['REPORT_TYPE'];


            $inputs = '';
            if ($reportType == 'SQL') {
                $inputs .= wf_HiddenInput(self::PROUTE_EDTYPE, 'SQL');
                $inputs .= wf_TextInput(self::PROUTE_EDNAME, __('Report name') . $sup, $reportData['REPORT_NAME'], true, 40);
                $inputs .= wf_TextInput(self::PROUTE_EDQUERY, __('SQL Query') . $sup, $reportData['REPORT_QUERY'], true, 60);
                $inputs .= wf_TextInput(self::PROUTE_EDKEYS, __('Data keys, separated by comma') . $sup, $reportData['REPORT_KEYS'], true, 40);
                $inputs .= wf_TextInput(self::PROUTE_EDFIELDS, __('Field names, separated by comma') . $sup, $reportData['REPORT_FIELD_NAMES'], true, 40);
                $inputs .= web_TriggerSelector(self::PROUTE_EDADDR, $reportData['REPORT_ADDR']) . ' ' . __('Show full address by login key') . wf_tag('br');
                $inputs .= web_TriggerSelector(self::PROUTE_EDRNAMES, $reportData['REPORT_RNAMES']) . ' ' . __('Show Real Names by login key') . wf_tag('br');
                $inputs .= web_TriggerSelector(self::PROUTE_EDROWCOUNT, $reportData['REPORT_ROW_COUNT']) . ' ' . __('Show data query row count') . wf_tag('br');
            }

            if ($reportType == 'ONEPUNCH') {
                $onePunch = new OnePunch();
                $inputs .= wf_HiddenInput(self::PROUTE_EDTYPE, 'ONEPUNCH');
                $inputs .= wf_TextInput(self::PROUTE_EDNAME, __('Report name') . $sup, $reportData['REPORT_NAME'], true, 40);
                $inputs .= wf_TextInput(self::PROUTE_EDQUERY, __('One-Punch') . ' ' . __('script') . ' ' . __('Alias') . $sup, $reportData['REPORT_QUERY'], true, 40);
            }

            $inputs .= __('Access') . ':' . wf_tag('br');
            $inputs .= wf_TextInput(self::PROUTE_EDADMACL, __('Allowed administrators logins') . ' ' . __('(separator - comma)'), $reportData['REPORT_ALLOWADMINS'], true, 40);
            $inputs .= wf_TextInput(self::PROUTE_EDICON, __('Icon'), $reportData['REPORT_ICON'], true, 10);
            $inputs .= web_TriggerSelector(self::PROUTE_EDONTB, $reportData['REPORT_ONTB']) . ' ' . __('Show on taskbar') . wf_tag('br');


            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Unknown report') . ': ' . $reportId, 'error');
        }
        $result .= wf_delimiter(1);
        $result .= $this->renderBackControl();
        return($result);
    }

    /**
     * Saves report on its editing
     * 
     * @param string $reportId
     * 
     * @return void/string on error
     */
    public function saveReport($reportId) {
        $result = '';
        $reportId = ubRouting::filters($reportId, 'mres');
        //report exists?
        if (isset($this->allReports[$reportId])) {
            //basic options received
            if (ubRouting::checkPost(array(self::PROUTE_EDNAME, self::PROUTE_EDQUERY, self::PROUTE_EDTYPE))) {
                $fileName = $reportId;
                $pathToSave = self::PATH_REPORTS . $fileName;
                $isOk = false;

                $newReportType = ubRouting::post(self::PROUTE_EDTYPE);
                $newReportName = ubRouting::post(self::PROUTE_EDNAME);
                $newReportQuery = ubRouting::post(self::PROUTE_EDQUERY);
                $newReportKeys = ubRouting::post(self::PROUTE_EDKEYS);
                $newReportFields = ubRouting::post(self::PROUTE_EDFIELDS);
                $newReportAddr = ubRouting::post(self::PROUTE_EDADDR);
                $newReportRenderNames = ubRouting::post(self::PROUTE_EDRNAMES);
                $newReportRowCount = ubRouting::post(self::PROUTE_EDROWCOUNT);
                $newReportAdmAcl = ubRouting::post(self::PROUTE_EDADMACL);
                $newReportIcon = ubRouting::post(self::PROUTE_EDICON);
                $newReportOnTb = ubRouting::post(self::PROUTE_EDONTB);

                if (!empty($newReportType) AND ! empty($newReportName) AND ! empty($newReportQuery)) {
                    //base params here?
                    $isOk = true;
                } else {
                    $result .= __('All fields marked with an asterisk are mandatory');
                }

                $reportBody = '';
                $reportBody .= 'REPORT_NAME="' . $newReportName . '"' . PHP_EOL;
                $reportBody .= 'REPORT_TYPE="' . $newReportType . '"' . PHP_EOL;
                $reportBody .= 'REPORT_ALLOWADMINS="' . $newReportAdmAcl . '"' . PHP_EOL; //allows all by default
                $reportBody .= 'REPORT_QUERY="' . $newReportQuery . '"' . PHP_EOL;
                $reportBody .= 'REPORT_ONTB="' . $newReportOnTb . '"' . PHP_EOL;
                $reportBody .= 'REPORT_ICON="' . $newReportIcon . '"' . PHP_EOL;

                if ($newReportType == 'SQL') {
                    $reportBody .= 'REPORT_KEYS="' . $newReportKeys . '"' . PHP_EOL;
                    $reportBody .= 'REPORT_FIELD_NAMES="' . $newReportFields . '"' . PHP_EOL;
                    $reportBody .= 'REPORT_ADDR="' . $newReportAddr . '"' . PHP_EOL;
                    $reportBody .= 'REPORT_RNAMES="' . $newReportRenderNames . '"' . PHP_EOL;
                    $reportBody .= 'REPORT_ROW_COUNT="' . $newReportRowCount . '"' . PHP_EOL;
                }

                if ($newReportType == 'ONEPUNCH') {
                    //script exists?
                    $onePunch = new OnePunch($newReportQuery);
                    $scriptCode = $onePunch->getScriptContent($newReportQuery);
                    if (!empty($scriptCode)) {
                        $isOk = true;
                    } else {
                        $isOk = false;
                        $result .= __('One-Punch') . ' ' . __('script') . ' ' . $newReportQuery . ' ' . __('Not exists');
                    }
                }

                if ($isOk) {
                    file_put_contents($pathToSave, $reportBody);
                    log_register('REPORTMASTER SAVE ' . $newReportType . ' REPORT `' . $fileName . '`');
                } else {
                    log_register('REPORTMASTER SAVE FAIL ' . $newReportType . ' REPORT `' . $fileName . '`');
                }
            }
        }
        return($result);
    }

}
