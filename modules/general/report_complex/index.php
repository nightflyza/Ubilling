<?php

if (cfr('REPORTCOMPLEX')) {

    class ReportComplex {

        protected $data = array();
        protected $masks = array();
        protected $cfields = array();
        protected $contracts = array();
        protected $actives = array();
        protected $altCfg = array();
        protected $ukvCableSeals = array();

        const CPL_SETTINGS_EX = 'NO_SETTINGS_DEFINED';
        const CPL_EMPTY_EX = 'EMPTY_SETTINGS_RECEIVED';
        const CPL_COUNT_EX = 'WRONG_PARAM_COUNT';

        public function __construct() {
            //loads report specific options 
            $this->loadConfig();
            //load actual data by users with complex services
            $this->loadData();
        }

        /*
         * loads report specific options from alter config to private props masks & cfields
         * 
         * @return void 
         */

        private function loadConfig() {
            global $ubillingConfig;
            $altercfg = $ubillingConfig->getAlter();
            $this->altCfg = $altercfg;
            if ((isset($altercfg['COMPLEX_MASKS'])) AND ( isset($altercfg['COMPLEX_CFIDS']))) {
                //loads tariff masks
                if (!empty($altercfg['COMPLEX_MASKS'])) {
                    $masksRaw = explode(",", $altercfg['COMPLEX_MASKS']);
                    if (!empty($masksRaw)) {
                        foreach ($masksRaw as $eachmask) {
                            $this->masks[] = "'" . trim($eachmask) . "%'";
                        }
                    }
                } else {
                    throw new Exception(self::CPL_EMPTY_EX);
                }

                //loads contract and enabled flags CFIDs
                if (!empty($altercfg['COMPLEX_CFIDS'])) {
                    if (!empty($altercfg['COMPLEX_CFIDS'])) {
                        $cfieldsRaw = explode(',', $altercfg['COMPLEX_CFIDS']);
                        if (sizeof($cfieldsRaw) != '3') {
                            $this->cfields['contract'] = trim($cfieldsRaw[0]);
                            $this->cfields['active'] = trim($cfieldsRaw[1]);
                        } else {
                            throw new Exception(self::CPL_COUNT_EX);
                        }
                    }
                } else {
                    throw new Exception(self::CPL_EMPTY_EX);
                }
            } else {
                throw new Exception(self::CPL_SETTINGS_EX);
            }
        }

        /*
         * get all users with complex service tariffs
         * 
         * @return void
         */

        protected function loadData() {
            $tariffLikes = '';
            if (!empty($this->masks)) {
                $tariffLikes = implode(' OR `Tariff` LIKE ', $this->masks);
            }
            $query = "SELECT * from `users` WHERE `Tariff` LIKE " . $tariffLikes . ";";
            $alldata = simple_queryall($query);

            if (!empty($alldata)) {
                $this->data = $alldata;
            }
            //loading complex service contracts
            $queryContracts = "SELECT `login`,`content` from `cfitems` WHERE `typeid`='" . $this->cfields['contract'] . "'";
            $allContracts = simple_queryall($queryContracts);
            if (!empty($allContracts)) {
                foreach ($allContracts as $ia => $eachContract) {
                    $this->contracts[$eachContract['login']] = $eachContract['content'];
                }
            }
            //loading complex services activity flags
            $queryActive = "SELECT `login`,`content` from `cfitems` WHERE `typeid`='" . $this->cfields['active'] . "'";
            $allActive = simple_queryall($queryActive);
            if (!empty($allActive)) {
                foreach ($allActive as $ib => $eachActive) {
                    $this->actives[$eachActive['login']] = $eachActive['content'];
                }
            }

            //extracting ukv cable seals if required
            if ($this->altCfg['UKV_ENABLED']) {
                $sealQuery = "SELECT `cableseal`,`inetlogin` from `ukv_users`";
                $rawSeals = simple_queryall($sealQuery);
                if (!empty($rawSeals)) {
                    foreach ($rawSeals as $io => $each) {
                        $this->ukvCableSeals[$each['inetlogin']] = $each['cableseal'];
                    }
                }
            }
        }

        /*
         * returns private propert data
         * 
         * @return array
         */

        public function getData() {
            $result = $this->data;
            return ($result);
        }

        public function printable($data) {
            $style = file_get_contents(CONFIG_PATH . "ukvprintable.css");

            $header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
        <head>                                                        
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style type="text/css">
        ' . $style . '
        </style>
        <script src="modules/jsc/sorttable.js" language="javascript"></script>
        </head>
        <body>
        ';

            $footer = '</body> </html>';

            $result = $header . $data . $footer;
            return ($result);
        }

        /*
         * renders all users report by existing private data props
         * 
         * @param $cutdata - bool cutting profile links and leds, for printing
         * 
         * @return string
         */

        public function renderAll($cutdata = false) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            $allrealnames = zb_UserGetAllRealnames();
            $userCounter = 0;

            $cells = '';
            if (!$cutdata) {
                $cells.= wf_TableCell(__('Login'));
            }
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            if ($this->altCfg['UKV_ENABLED']) {
                $cells.= wf_TableCell(__('Cable seal'));
            } else {
                $cells.= wf_TableCell(__('IP'));
            }

            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Credit'));
            $cells.= wf_TableCell(__('Contract'));
            $cells.= wf_TableCell(__('Service active'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    $cells = '';
                    if (!$cutdata) {
                        $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                        $cells = wf_TableCell($profileLink);
                    }
                    $cells.= wf_TableCell(@$alladdress[$each['login']]);
                    $cells.= wf_TableCell(@$allrealnames[$each['login']]);
                    if ($this->altCfg['UKV_ENABLED']) {
                        $cells.=wf_TableCell(@$this->ukvCableSeals[$each['login']]);
                    } else {
                        $cells.= wf_TableCell($each['IP']);
                    }
                    $cells.= wf_TableCell($each['Tariff']);
                    $cells.= wf_TableCell($each['Cash']);
                    $cells.= wf_TableCell($each['Credit']);
                    $cells.= wf_TableCell(@$this->contracts[$each['login']]);
                    $actFlag = web_bool_led(@$this->actives[$each['login']], true);
                    if ($cutdata) {
                        $actFlag = strip_tags($actFlag);
                    }
                    $cells.= wf_TableCell($actFlag, '', '', 'sorttable_customkey="' . @$this->actives[$each['login']] . '"');
                    $rows.= wf_TableRow($cells, 'row3');
                    $userCounter++;
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= __('Total') . ': ' . $userCounter;
            return ($result);
        }

        /*
         * renders debtors users report by existing private data props
         * 
         * @param $cutdata - bool cutting profile links and leds, for printing
         * 
         * @return string
         */

        public function renderDebtors($cutdata = false) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            $allrealnames = zb_UserGetAllRealnames();
            $userCounter = 0;

            $cells = '';
            if (!$cutdata) {
                $cells.= wf_TableCell(__('Login'));
            }
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            if ($this->altCfg['UKV_ENABLED']) {
                $cells.= wf_TableCell(__('Cable seal'));
            } else {
                $cells.= wf_TableCell(__('IP'));
            }

            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Credit'));
            $cells.= wf_TableCell(__('Contract'));
            $cells.= wf_TableCell(__('Service active'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    if ((($each['Cash'] < ('-' . $each['Credit'])) AND ( @$this->actives[$each['login']] == 1)) OR ( ($each['Passive'] == '1') AND ( @$this->actives[$each['login']] == 1))) {
                        $cells = '';
                        if (!$cutdata) {
                            $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                            $cells = wf_TableCell($profileLink);
                        }
                        $cells.= wf_TableCell(@$alladdress[$each['login']]);
                        $cells.= wf_TableCell(@$allrealnames[$each['login']]);
                        if ($this->altCfg['UKV_ENABLED']) {
                            $cells.= wf_TableCell(@$this->ukvCableSeals[$each['login']]);
                        } else {
                            $cells.= wf_TableCell($each['IP']);
                        }

                        $cells.= wf_TableCell($each['Tariff']);
                        $cells.= wf_TableCell($each['Cash']);
                        $cells.= wf_TableCell($each['Credit']);
                        $cells.= wf_TableCell(@$this->contracts[$each['login']]);
                        $actFlag = web_bool_led(@$this->actives[$each['login']], true);
                        if ($cutdata) {
                            $actFlag = strip_tags($actFlag);
                        }
                        $cells.= wf_TableCell($actFlag, '', '', 'sorttable_customkey="' . @$this->actives[$each['login']] . '"');
                        $rows.= wf_TableRow($cells, 'row3');
                        $userCounter++;
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= __('Total') . ': ' . $userCounter;
            return ($result);
        }

        /*
         * renders anti-debtors users report by existing private data props
         * 
         * @param $cutdata - bool cutting profile links and leds, for printing
         * 
         * @return string
         */

        public function renderAntiDebtors($cutdata = false) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            $allrealnames = zb_UserGetAllRealnames();
            $userCounter = 0;

            $cells = '';
            if (!$cutdata) {
                $cells.= wf_TableCell(__('Login'));
            }
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            if ($this->altCfg['UKV_ENABLED']) {
                $cells.=wf_TableCell(__('Cable seal'));
            } else {
                $cells.= wf_TableCell(__('IP'));
            }
            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Credit'));
            $cells.= wf_TableCell(__('Contract'));
            $cells.= wf_TableCell(__('Service active'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    if (($each['Cash'] >= ('-' . $each['Credit'])) AND ( @$this->actives[$each['login']] != 1) AND ( $each['Passive'] != 1)) {
                        $cells = '';
                        if (!$cutdata) {
                            $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                            $cells = wf_TableCell($profileLink);
                        }
                        $cells.= wf_TableCell(@$alladdress[$each['login']]);
                        $cells.= wf_TableCell(@$allrealnames[$each['login']]);
                        if ($this->altCfg['UKV_ENABLED']) {
                            $cells.=wf_TableCell(@$this->ukvCableSeals[$each['login']]);
                        } else {
                            $cells.= wf_TableCell($each['IP']);
                        }
                        $cells.= wf_TableCell($each['Tariff']);
                        $cells.= wf_TableCell($each['Cash']);
                        $cells.= wf_TableCell($each['Credit']);
                        $cells.= wf_TableCell(@$this->contracts[$each['login']]);
                        $actFlag = web_bool_led(@$this->actives[$each['login']], true);
                        if ($cutdata) {
                            $actFlag = strip_tags($actFlag);
                        }
                        $cells.= wf_TableCell($actFlag, '', '', 'sorttable_customkey="' . @$this->actives[$each['login']] . '"');
                        $rows.= wf_TableRow($cells, 'row3');
                        $userCounter++;
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= __('Total') . ': ' . $userCounter;
            return ($result);
        }

        /*
         * Shows navigation panel for reports
         * 
         */

        public function panel() {
            $controls = wf_Link('?module=report_complex', __('All users'), false, 'ubButton');
            $controls.= wf_Link('?module=report_complex&show=debtors', __('Debtors'), false, 'ubButton');
            $controls.= wf_Link('?module=report_complex&show=antidebtors', __('AntiDebtors'), false, 'ubButton');
            $controls.= wf_Link('?module=report_complex&show=nmcomplex', __('Complex service next month'), false, 'ubButton');
            return ($controls);
        }

    }

    class ReportComplexNM extends ReportComplex {
        /*
         * get all users with  with complex services from the next month
         * 
         * @return void
         */

        protected function loadData() {
            $tariffLikes = '';
            if (!empty($this->masks)) {
                $tariffLikes = implode(' OR `TariffChange` LIKE ', $this->masks);
            }
            $query = "SELECT * from `users` WHERE `TariffChange` LIKE " . $tariffLikes . ";";
            $alldata = simple_queryall($query);

            if (!empty($alldata)) {
                $this->data = $alldata;
            }
            //loading complex service contracts
            $queryContracts = "SELECT `login`,`content` from `cfitems` WHERE `typeid`='" . $this->cfields['contract'] . "'";
            $allContracts = simple_queryall($queryContracts);
            if (!empty($allContracts)) {
                foreach ($allContracts as $ia => $eachContract) {
                    $this->contracts[$eachContract['login']] = $eachContract['content'];
                }
            }
            //loading complex services activity flags
            $queryActive = "SELECT `login`,`content` from `cfitems` WHERE `typeid`='" . $this->cfields['active'] . "'";
            $allActive = simple_queryall($queryActive);
            if (!empty($allActive)) {
                foreach ($allActive as $ib => $eachActive) {
                    $this->actives[$eachActive['login']] = $eachActive['content'];
                }
            }
        }

        /*
         * renders all users report by existing private data props
         * 
         * @param $cutdata - bool cutting profile links and leds, for printing
         * 
         * @return string
         */

        public function renderAll($cutdata = false) {
            $alladdress = zb_AddressGetFulladdresslist();
            $allrealnames = zb_UserGetAllRealnames();
            $userCounter = 0;

            $cells = '';
            if (!$cutdata) {
                $cells.= wf_TableCell(__('Login'));
            }
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('IP'));
            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Next month'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Credit'));
            $cells.= wf_TableCell(__('Contract'));
            $cells.= wf_TableCell(__('Service active'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    $cells = '';
                    if (!$cutdata) {
                        $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                        $cells = wf_TableCell($profileLink);
                    }
                    $cells.= wf_TableCell(@$alladdress[$each['login']]);
                    $cells.= wf_TableCell(@$allrealnames[$each['login']]);
                    $cells.= wf_TableCell($each['IP']);
                    $cells.= wf_TableCell($each['Tariff']);
                    $cells.= wf_TableCell($each['TariffChange']);
                    $cells.= wf_TableCell($each['Cash']);
                    $cells.= wf_TableCell($each['Credit']);
                    $cells.= wf_TableCell(@$this->contracts[$each['login']]);
                    $actFlag = web_bool_led(@$this->actives[$each['login']], true);
                    if ($cutdata) {
                        $actFlag = strip_tags($actFlag);
                    }
                    $cells.= wf_TableCell($actFlag, '', '', 'sorttable_customkey="' . @$this->actives[$each['login']] . '"');
                    $rows.= wf_TableRow($cells, 'row3');
                    $userCounter++;
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= __('Total') . ': ' . $userCounter;
            return ($result);
        }

    }

    /**
     * controller and view section
     */

    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    if (isset($altercfg['COMPLEX_ENABLED'])) {
        if ($altercfg['COMPLEX_ENABLED']) {
            $complexReport = new ReportComplex();
            //showing navigation
            show_window(__('Users with complex services'), $complexReport->panel());

            if (!wf_CheckGet(array('show'))) {
                //show all users by default
                $reportHeader = __('All users with complex services');
                if (!wf_CheckGet(array('printable'))) {
                    $reportHeader.=' ' . wf_Link('?module=report_complex&printable=true', wf_img('skins/printer_small.gif', __('Print')), false, '');
                    show_window($reportHeader, $complexReport->renderAll());
                } else {
                    $reportData = wf_tag('h2') . $reportHeader . wf_tag('h2', true);
                    $reportData.= $complexReport->renderAll(true);
                    $reportData = $complexReport->printable($reportData);
                    die($reportData);
                }
            } else {
                $action = trim($_GET['show']);

                if ($action == 'debtors') {
                    $reportHeader = __('Debtors who need disabling additional service');
                    if (!wf_CheckGet(array('printable'))) {
                        //normal render
                        $reportHeader.=' ' . wf_Link('?module=report_complex&show=debtors&printable=true', wf_img('skins/printer_small.gif', __('Print')), false, '');
                        $reportData = $complexReport->renderDebtors();
                        show_window($reportHeader, $reportData);
                    } else {
                        //printable mode
                        $reportData = wf_tag('h2') . $reportHeader . wf_tag('h2', true);
                        $reportData.= $complexReport->renderDebtors(true);
                        $reportData = $complexReport->printable($reportData);
                        die($reportData);
                    }
                }

                if ($action == 'antidebtors') {
                    $reportHeader = __('AntiDebtors who need enabling additional service');
                    if (!wf_CheckGet(array('printable'))) {
                        //normal render
                        $reportHeader.=' ' . wf_Link('?module=report_complex&show=antidebtors&printable=true', wf_img('skins/printer_small.gif', __('Print')), false, '');
                        $reportData = $complexReport->renderAntiDebtors();
                        show_window($reportHeader, $reportData);
                    } else {
                        //printable mode
                        $reportData = wf_tag('h2') . $reportHeader . wf_tag('h2', true);
                        $reportData.= $complexReport->renderAntiDebtors(true);
                        $reportData = $complexReport->printable($reportData);
                        die($reportData);
                    }
                }

                if ($action == 'nmcomplex') {
                    $nmComplexReport = new ReportComplexNM();
                    $reportHeader = __('Complex service next month');

                    if (!wf_CheckGet(array('printable'))) {
                        //normal render
                        $reportHeader.=' ' . wf_Link('?module=report_complex&show=nmcomplex&printable=true', wf_img('skins/printer_small.gif', __('Print')), false, '');
                        $reportData = $nmComplexReport->renderAll();
                        show_window($reportHeader, $reportData);
                    } else {
                        //printable mode
                        $reportData = wf_tag('h2') . $reportHeader . wf_tag('h2', true);
                        $reportData.= $nmComplexReport->renderAll(true);
                        $reportData = $nmComplexReport->printable($reportData);
                        die($reportData);
                    }
                }
            }
        } else {
            show_error(__('This module is disabled'));
        }
    }
} else {
    show_error(__('You cant control this module'));
}
?>