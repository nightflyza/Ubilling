<?php

if (cfr('REPORTSIGNUP')) {

    $altercfg = $ubillingConfig->getAlter();

    /**
     * Returns signups array with some custom options
     * 
     * @param string $where
     * 
     * @return array
     */
    function zb_SignupsGet($where) {
        $query = "SELECT * from `userreg` " . $where;
        $result = simple_queryall($query);
        return($result);
    }

    /**
     * returns array like $month_num=>$signup_count
     * 
     * @param int $year
     * @return array
     */
    function zb_SignupsGetCountYear($year) {
        $months = months_array();
        $result = array();
        foreach ($months as $monthNum => $monthName) {
            $result[$monthNum] = 0;
        }

        $allYearSignups_q = "SELECT * from `userreg` WHERE `date` LIKE '" . $year . "-%';";
        $allYearSignups = simple_queryall($allYearSignups_q);
        if (!empty($allYearSignups)) {
            foreach ($allYearSignups as $idx => $eachYearSignup) {
                $statsMonth = date("m", strtotime($eachYearSignup['date']));

                if (isset($result[$statsMonth])) {
                    $result[$statsMonth] ++;
                } else {
                    $result[$statsMonth] = 1;
                }
            }
        }

        return($result);
    }

    /**
     * Shows user signups by year with funny bars
     * 
     * @global object $ubillingConfig
     * @param int $year
     * 
     * @return void
     */
    function web_SignupsGraphYear($year) {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $cemeteryEnabled = (@$altCfg['CEMETERY_ENABLED']) ? true : false;
        if ($cemeteryEnabled) {
            $cemetery = new Cemetery();
        }

        $year = vf($year);
        $yearcount = zb_SignupsGetCountYear($year);
        $maxsignups = max($yearcount);
        $allmonths = months_array();
        $totalcount = 0;

        $tablecells = wf_TableCell('');
        $tablecells.=wf_TableCell(__('Month'));
        $tablecells.=wf_TableCell(__('Signups'));
        if ($cemeteryEnabled) {
            $tablecells.=wf_TableCell(__('Dead souls'));
            $tablecells.=wf_TableCell('', '10%');
        }
        $tablecells.=wf_TableCell(__('Visual'), '50%');
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($yearcount as $eachmonth => $count) {
            $totalcount = $totalcount + $count;
            $tablecells = wf_TableCell($eachmonth);
            $tablecells.=wf_TableCell(wf_Link('?module=report_signup&month=' . $year . '-' . $eachmonth, rcms_date_localise($allmonths[$eachmonth])));
            $tablecells.=wf_TableCell($count);
            if ($cemeteryEnabled) {
                $deadDateMask = $year . '-' . $eachmonth . '-';
                $deadCount = $cemetery->getDeadDateCount($deadDateMask);
                $deadBar = web_barTariffs($count, $deadCount);
                $tablecells.=wf_TableCell($deadCount);
                $tablecells.=wf_TableCell($deadBar);
            }
            $tablecells.=wf_TableCell(web_bar($count, $maxsignups), '', '', 'sorttable_customkey="' . $count . '"');
            $tablerows.=wf_TableRow($tablecells, 'row3');
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result.= wf_tag('b', false) . __('Total') . ': ' . $totalcount . wf_tag('b', true);
        show_window(__('User signups by year') . ' ' . $year, $result);
    }

    /**
     * Shows current month signups
     * 
     * @global object $altercfg
     * 
     * @return void
     */
    function web_SignupsShowCurrentMonth() {
        global $altercfg;
        $alltariffs = zb_TariffsGetAllUsers();
        $cmonth = curmonth();
        $where = "WHERE `date` LIKE '" . $cmonth . "%' ORDER by `date` DESC;";
        $signups = zb_SignupsGet($where);
        $curdate = curdate();
        $chartDataMonth = array();
        $chartDataDay = array();

        //cemetery hide processing
        $ignoreUsers = array();
        if ($altercfg['CEMETERY_ENABLED']) {
            $cemetery = new Cemetery();
            $ignoreUsers = $cemetery->getAllTagged();
        }

        $tablecells = wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Administrator'));
        if ($altercfg['SIGREP_CONTRACT']) {
            $tablecells.=wf_TableCell(__('Contract'));
            $allcontracts = array_flip(zb_UserGetAllContracts());
        }
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Full address'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($signups)) {
            @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            foreach ($signups as $io => $eachsignup) {
                $tablecells = wf_TableCell($eachsignup['id']);
                $tablecells.=wf_TableCell($eachsignup['date']);

                $administratorName = (isset($employeeLogins[$eachsignup['admin']])) ? $employeeLogins[$eachsignup['admin']] : $eachsignup['admin'];
                $tablecells.=wf_TableCell($administratorName);

                if ($altercfg['SIGREP_CONTRACT']) {
                    $tablecells.=wf_TableCell(@$allcontracts[$eachsignup['login']]);
                }
                @$sigTariff = $alltariffs[$eachsignup['login']];
                $tablecells.=wf_TableCell($eachsignup['login']);
                $tablecells.=wf_TableCell($sigTariff);
                $profilelink = wf_Link('?module=userprofile&username=' . trim($eachsignup['login']), web_profile_icon() . ' ' . $eachsignup['address']);
                $tablecells.=wf_TableCell($profilelink);
                if (ispos($eachsignup['date'], $curdate)) {
                    $rowClass = 'todaysig';
                    //today chart data
                    if (isset($chartDataDay[$administratorName])) {
                        $chartDataDay[$administratorName] ++;
                    } else {
                        $chartDataDay[$administratorName] = 1;
                    }
                } else {
                    $rowClass = 'row3';
                }
                //cemetary user
                if (isset($ignoreUsers[$eachsignup['login']])) {
                    $rowClass = 'sigcemeteryuser';
                }
                //ugly check - is user removed?
                if (empty($sigTariff)) {
                    $rowClass = 'sigdeleteduser';
                }

                //chart data filling
                if (isset($chartDataMonth[$administratorName])) {
                    $chartDataMonth[$administratorName] ++;
                } else {
                    $chartDataMonth[$administratorName] = 1;
                }

                $tablerows.=wf_TableRow($tablecells, $rowClass);
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result.= web_SignupsRenderChart($chartDataMonth, $chartDataDay);
        show_window(__('Current month user signups'), $result);
    }

    /**
     * Renders google charts for month signups data array
     * 
     * @param array $dataMonth
     * @param array $dataDay
     * 
     * @return string
     */
    function web_SignupsRenderChart($dataMonth, $dataDay) {
        $result = '';
        $options = "chartArea: {  width: '90%', height: '90%' },  pieSliceText: 'value-and-percentage', legend : {position: 'right'}, ";
        if (!empty($dataMonth)) {
            $chartMonth = wf_gcharts3DPie($dataMonth, __('Month'), '400px;', '300px;', $options);
        } else {
            $chartMonth = '';
        }
        if (!empty($dataDay)) {
            $chartDay = wf_gcharts3DPie($dataDay, __('Today'), '400px;', '300px;', $options);
        } else {
            $chartDay = '';
        }

        $cells = wf_TableCell($chartMonth);
        $cells.= wf_TableCell($chartDay);
        $rows = wf_TableRow($cells);

        $result.=wf_tag('h3') . __('Administrators') . wf_tag('h3', true);
        $result.= wf_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * Shows signups by another year-month
     * 
     * @global object $altercfg
     * @param string $cmonth
     * 
     * @return void
     */
    function web_SignupsShowAnotherYearMonth($cmonth) {
        global $altercfg;
        $alltariffs = zb_TariffsGetAllUsers();
        $cmonth = mysql_real_escape_string($cmonth);
        $where = "WHERE `date` LIKE '" . $cmonth . "%' ORDER by `date` DESC;";
        $signups = zb_SignupsGet($where);
        $curdate = curdate();
        $chartDataMonth = array();
        $chartDataDay = array();

        //cemetery hide processing
        $ignoreUsers = array();
        if ($altercfg['CEMETERY_ENABLED']) {
            $cemetery = new Cemetery();
            $ignoreUsers = $cemetery->getAllTagged();
        }


        $tablecells = wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Administrator'));
        if ($altercfg['SIGREP_CONTRACT']) {
            $tablecells.=wf_TableCell(__('Contract'));
            $allcontracts = array_flip(zb_UserGetAllContracts());
        }
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Full address'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($signups)) {
            @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            foreach ($signups as $io => $eachsignup) {
                $tablecells = wf_TableCell($eachsignup['id']);
                $tablecells.=wf_TableCell($eachsignup['date']);

                $administratorName = (isset($employeeLogins[$eachsignup['admin']])) ? $employeeLogins[$eachsignup['admin']] : $eachsignup['admin'];
                $tablecells.=wf_TableCell($administratorName);

                if ($altercfg['SIGREP_CONTRACT']) {
                    $tablecells.=wf_TableCell(@$allcontracts[$eachsignup['login']]);
                }
                $tablecells.=wf_TableCell($eachsignup['login']);
                @$sigTariff = $alltariffs[$eachsignup['login']];
                $tablecells.=wf_TableCell($sigTariff);
                $profilelink = wf_Link('?module=userprofile&username=' . $eachsignup['login'], web_profile_icon() . ' ' . $eachsignup['address']);
                $tablecells.=wf_TableCell($profilelink);
                if (ispos($eachsignup['date'], $curdate)) {
                    $rowClass = 'todaysig';
                    //today chart data
                    if (isset($chartDataDay[$administratorName])) {
                        $chartDataDay[$administratorName] ++;
                    } else {
                        $chartDataDay[$administratorName] = 1;
                    }
                } else {
                    $rowClass = 'row3';
                }
                //cemetary user
                if (isset($ignoreUsers[$eachsignup['login']])) {
                    $rowClass = 'sigcemeteryuser';
                }
                //ugly check - is user removed?
                if (empty($sigTariff)) {
                    $rowClass = 'sigdeleteduser';
                }

                //chart data filling per month
                if (isset($chartDataMonth[$administratorName])) {
                    $chartDataMonth[$administratorName] ++;
                } else {
                    $chartDataMonth[$administratorName] = 1;
                }

                $tablerows.=wf_TableRow($tablecells, $rowClass);
            }
        }


        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result.= web_SignupsRenderChart($chartDataMonth, $chartDataDay);
        show_window(__('User signups by month') . ' ' . $cmonth, $result);
    }

    /**
     * Shows signups performed today
     * 
     * @return void
     */
    function web_SignupsShowToday() {
        $messages = new UbillingMessageHelper();
        $query = "SELECT COUNT(`id`) from `userreg` WHERE `date` LIKE '" . curdate() . "%'";
        $sigcount = simple_query($query);
        $sigcount = $sigcount['COUNT(`id`)'];
        show_window('', $messages->getStyledMessage(__('Today signups') . ': ' . wf_tag('strong') . $sigcount . wf_tag('strong', true), 'info'));
    }

    /**
     * Shows signup tariffs popularity  chart
     * 
     * @return void
     */
    function web_SignupGraph() {
        if (!wf_CheckGet(array('month'))) {
            $cmonth = curmonth();
        } else {
            $cmonth = mysql_real_escape_string($_GET['month']);
        }
        $where = "WHERE `date` LIKE '" . $cmonth . "%'";
        $alltariffnames = zb_TariffsGetAll();
        $tariffusers = zb_TariffsGetAllUsers();
        $allsignups = zb_SignupsGet($where);

        $tcount = array();
        if (!empty($allsignups)) {
            foreach ($alltariffnames as $io => $eachtariff) {
                foreach ($allsignups as $ii => $eachsignup) {
                    if (@$tariffusers[$eachsignup['login']] == $eachtariff['name']) {
                        @$tcount[$eachtariff['name']] = $tcount[$eachtariff['name']] + 1;
                    }
                }
            }
        }

        $tablecells = wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Count'));
        $tablecells.=wf_TableCell(__('Visual'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($tcount)) {
            foreach ($tcount as $sigtariff => $eachcount) {

                $tablecells = wf_TableCell($sigtariff);
                $tablecells.=wf_TableCell($eachcount);
                $tablecells.=wf_TableCell(web_bar($eachcount, sizeof($allsignups)), '', '', 'sorttable_customkey="' . $eachcount . '"');
                $tablerows.=wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Tariffs report'), $result);
    }

    if (!isset($_POST['yearsel'])) {
        $year = curyear();
    } else {
        $year = $_POST['yearsel'];
    }

    $yearinputs = wf_YearSelector('yearsel');
    $yearinputs.=wf_Submit(__('Show'));
    $yearform = wf_Form('?module=report_signup', 'POST', $yearinputs, 'glamour');
    $yearform.= wf_CleanDiv();

    web_SignupsShowToday();
    show_window(__('Year'), $yearform);
    web_SignupsGraphYear($year);
    web_SignupGraph();
    if ($altercfg['CEMETERY_ENABLED']) {
        $cemetery = new Cemetery();
        show_window('', $cemetery->renderChart());
    }

    if (!wf_CheckGet(array('month'))) {
        web_SignupsShowCurrentMonth();
    } else {
        web_SignupsShowAnotherYearMonth($_GET['month']);
    }

    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
?>
