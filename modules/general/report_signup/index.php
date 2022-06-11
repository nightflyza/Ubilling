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
     * 
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
        $cache = new UbillingCache();
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
        $tablecells .= wf_TableCell(__('Month'));
        $tablecells .= wf_TableCell(__('Signups'));
        if ($cemeteryEnabled) {
            $tablecells .= wf_TableCell(__('Dead souls'));
            $tablecells .= wf_TableCell('', '10%');
        }
        $tablecells .= wf_TableCell(__('Visual'), '50%');
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($yearcount as $eachmonth => $count) {
            $totalcount = $totalcount + $count;
            $tablecells = wf_TableCell($eachmonth);
            $tablecells .= wf_TableCell(wf_Link('?module=report_signup&month=' . $year . '-' . $eachmonth, rcms_date_localise($allmonths[$eachmonth])));
            $tablecells .= wf_TableCell($count);
            if ($cemeteryEnabled) {
                $deadDateMask = $year . '-' . $eachmonth . '-';
                $deadCount = $cemetery->getDeadDateCount($deadDateMask);
                $deadBar = web_barTariffs(abs($count), abs($deadCount));
                $tablecells .= wf_TableCell($deadCount);
                $tablecells .= wf_TableCell($deadBar);
            }
            $tablecells .= wf_TableCell(web_bar($count, $maxsignups), '', '', 'sorttable_customkey="' . $count . '"');
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }



        $aliveStats = $cache->getCallback('SIGALIVESTATS_' . $year, function () use ($year) {
            return (zb_SignupsGetAilveStats($year));
        }, 86400); //cached for 1 day

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_tag('b', false) . __('Total users registered') . ': ' . $totalcount . wf_tag('b', true);
        if ($totalcount > 0) {
            $result .= wf_tag('br');
            $result .= ' ' . $aliveStats['alive'] . ' ' . __('of them remain active');
            $result .= ' ' . __('and') . ' ' . $aliveStats['dead'] . ' ' . wf_Link('?module=report_signup&showdeadusers=' . $year, __('now is dead')) . ' (' . zb_PercentValue($aliveStats['total'], $aliveStats['dead']) . '%)';
        }

        $sigMapLinkControls = '';
        if (cfr('REPORTSIGNUP') AND cfr('USERSMAP')) {
            $sigMapLinkControls .= wf_tag('div', false, '', 'style="float:right; margin-left: 5px; padding-top: 0px;"');
            $sigMapLinkControls .= wf_Link('?module=report_sigmap', wf_img_sized('skins/swmapsmall.png', '', '12') . ' ' . __('Signups map'), false, 'ubButton') . ' ';
            $sigMapLinkControls .= wf_tag('div', true);
        }

        $ponLastLinkControls = '';
        if (cfr('PON')) {
            if ($ubillingConfig->getAlterParam('PON_ENABLED')) {
                $ponLastLinkControls .= wf_tag('div', false, '', 'style="float:right;  margin-left: 5px; padding-top: 0px;"');
                $ponLastLinkControls .= wf_Link('?module=report_ponlastsig', wf_img_sized('skins/switch_models.png', '', '12') . ' ' . __('Latest ONU signals'), false, 'ubButton') . ' ';
                $ponLastLinkControls .= wf_tag('div', true);
            }
        }

        //some additional controls here
        $result .= $sigMapLinkControls;
        $result .= $ponLastLinkControls;

        show_window(__('User signups by year') . ' ' . $year, $result);
    }

    /**
     * Renders dead users for some year
     * 
     * @param int $year
     * 
     * @return void
     */
    function web_SignupsShowDeadUsers($year) {
        $year = vf($year, 3);
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $cache = new UbillingCache();
        if ($altCfg['MOBILES_EXT']) {
            $mobilesExt = new MobilesExt();
        }
        $aliveStats = $cache->getCallback('SIGALIVESTATS_' . $year, function () use ($year) {
            return (zb_SignupsGetAilveStats($year));
        }, 86400); //cached for 1 day
        $allUserData = zb_UserGetAllDataCache();
        $allContractDates = zb_UserContractDatesGetAll();
        $deadCount = 0;
        $frozenCount = 0;
        $result = '';
        $result .= wf_BackLink('?module=report_signup');
        $result .= wf_delimiter();

        if (!empty($aliveStats)) {
            if (!empty($aliveStats['deadlogins'])) {


                $cells = wf_TableCell(__('Login'));
                $cells .= wf_TableCell(__('Address'));
                $cells .= wf_TableCell(__('Real Name'));
                $cells .= wf_TableCell(__('IP'));
                $cells .= wf_TableCell(__('Tariff'));
                $cells .= wf_TableCell(__('Active'));
                $cells .= wf_TableCell(__('Balance'));
                $cells .= wf_TableCell(__('Credit'));
                $cells .= wf_TableCell(__('Phones'));
                $cells .= wf_TableCell(__('Contract date'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($aliveStats['deadlogins'] as $io => $login) {
                    $userData = @$allUserData[$login];
                    $userExtMobiles = '';
                    $allExt = array();
                    if ($altCfg['MOBILES_EXT']) {
                        $extMobilesTmp = $mobilesExt->getUserMobiles($login);
                        if (!empty($extMobilesTmp)) {
                            if (!empty($extMobilesTmp)) {
                                foreach ($extMobilesTmp as $ia => $each) {
                                    $allExt[] = $each['mobile'];
                                }
                            }
                            $userExtMobiles = implode(',', $allExt);
                        }
                    }
                    $cells = wf_TableCell(wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . $login));
                    $cells .= wf_TableCell(@$userData['fulladress']);
                    $cells .= wf_TableCell(@$userData['realname']);
                    $cells .= wf_TableCell(@$userData['ip']);
                    $cells .= wf_TableCell(@$userData['Tariff']);
                    $actFlag = ($userData['Cash'] >= -$userData['Credit']) ? web_bool_led(true) : web_bool_led(false);
                    if ($userData['Passive']) {
                        $freezeFlag = ' ' . wf_img('skins/icon_passive.gif', __('User is frozen'));
                        $frozenCount++;
                    } else {
                        $freezeFlag = '';
                    }
                    $cells .= wf_TableCell($actFlag . $freezeFlag);
                    $cells .= wf_TableCell($userData['Cash']);
                    $cells .= wf_TableCell($userData['Credit']);
                    $cells .= wf_TableCell($userData['mobile'] . ' ' . $userData['phone'] . ' ' . $userExtMobiles);
                    $cells .= wf_TableCell(@$allContractDates[$userData['contract']]);
                    $rows .= wf_TableRow($cells, 'row5');
                    $deadCount++;
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                $result .= __('Total') . ': ' . $deadCount . wf_tag('br');
                $result .= __('Frozen') . ': ' . $frozenCount;
            }
        }

        show_window(__('Inactive') . ' ' . $year, $result);
    }

    /**
     * Shows current month signups
     * 
     * @global object $altercfg
     * 
     * @param string $yearMonth
     * 
     * @return void
     */
    function web_SignupsMonthShow($yearMonth = '') {
        global $altercfg;
        if (empty($yearMonth)) {
            $cmonth = curmonth();
        } else {
            $cmonth = ubRouting::filters($yearMonth, 'mres');
        }

        $messages = new UbillingMessageHelper();
        $alltariffs = zb_TariffsGetAllUsers();
        $deleatableFlag = @$altercfg['SIGREP_DELETABLE'] ? true : false;
        $cityColumn = @$altercfg['SIGREP_CITYRENDER'] ? true : false;
        $userCities = array();

        $curdate = curdate();
        $totalCount = 0;
        $frozenCount = 0;
        $aliveCount = 0;
        $chartDataMonth = array();
        $chartDataDay = array();

        $where = "WHERE `date` LIKE '" . $cmonth . "%' ORDER by `date` DESC;";
        $signups = zb_SignupsGet($where);

        //preload cities data
        if ($cityColumn) {
            $userCities = zb_AddressGetCityUsers();
        }

        //cemetery hide processing
        $ignoreUsers = array();
        if ($altercfg['CEMETERY_ENABLED']) {
            $cemetery = new Cemetery();
            $ignoreUsers = $cemetery->getAllTagged();
        }

        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Date'));
        $tablecells .= wf_TableCell(__('Administrator'));
        if ($altercfg['SIGREP_CONTRACT']) {
            $tablecells .= wf_TableCell(__('Contract'));
            $allcontracts = array_flip(zb_UserGetAllContracts());
        }
        $tablecells .= wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell(__('Tariff'));
        $tablecells .= wf_TableCell(__('Status'));
        if ($cityColumn) {
            $tablecells .= wf_TableCell(__('City'));
        }
        $tablecells .= wf_TableCell(__('Address'));
        if ($deleatableFlag) {
            if (cfr('ROOT')) {
                $tablecells .= wf_TableCell(''); //just column placeholder
            }
        }
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($signups)) {
            $employeeLogins = ts_GetAllEmployeeLoginsAssocCached();
            $allUserData = zb_UserGetAllDataCache();
            foreach ($signups as $io => $eachsignup) {
                $tablecells = wf_TableCell($eachsignup['id']);
                $tablecells .= wf_TableCell($eachsignup['date']);

                $administratorName = (isset($employeeLogins[$eachsignup['admin']])) ? $employeeLogins[$eachsignup['admin']] : $eachsignup['admin'];
                $tablecells .= wf_TableCell($administratorName);

                if ($altercfg['SIGREP_CONTRACT']) {
                    $tablecells .= wf_TableCell(@$allcontracts[$eachsignup['login']]);
                }
                $sigTariff = @$alltariffs[$eachsignup['login']];
                $tariffCellClass = ($sigTariff == '*_NO_TARIFF_*') ? 'undone' : '';

                $tablecells .= wf_TableCell($eachsignup['login']);
                $tablecells .= wf_TableCell($sigTariff, '', $tariffCellClass);
                $userState = '';
                $userStateMark = '';
                if (isset($allUserData[$eachsignup['login']])) {
                    $userData = $allUserData[$eachsignup['login']];
                    if (zb_SignupCheckIsUserActive($userData)) {
                        $userState .= wf_img_sized('skins/icon_ok.gif', __('Alive'), '12');
                        $aliveCount++;
                        $userStateMark = 'A';
                    } else {
                        if ($userData['Passive']) {
                            $userState .= wf_img_sized('skins/icon_passive.gif', __('Frozen user'), '12');
                            $frozenCount++;
                            $userStateMark = 'F';
                        } else {
                            $userState .= wf_img_sized('skins/icon_inactive.gif', __('Inactive'), '12');
                            $userStateMark = 'I';
                        }
                    }
                } else {
                    $userState .= wf_img_sized('skins/skull.png', __('Deleted'), '12');
                    $userStateMark = 'D';
                }
                $tablecells .= wf_TableCell($userState, '', '', 'sorttable_customkey="' . $userStateMark . '"');

                if ($cityColumn) {
                    $tablecells .= wf_TableCell(@$userCities[$eachsignup['login']]);
                }

                $profilelink = wf_Link('?module=userprofile&username=' . trim($eachsignup['login']), web_profile_icon() . ' ' . $eachsignup['address']);
                $tablecells .= wf_TableCell($profilelink);
                if (ispos($eachsignup['date'], $curdate)) {
                    $rowClass = 'todaysig';
                    //today chart data
                    if (isset($chartDataDay[$administratorName])) {
                        $chartDataDay[$administratorName] ++;
                    } else {
                        $chartDataDay[$administratorName] = 1;
                    }
                } else {
                    $rowClass = 'row5';
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

                //record deletion control
                if ($deleatableFlag) {
                    if (cfr('ROOT')) {
                        if (empty($sigTariff)) {
                            $deletionLink = wf_JSAlert('?module=report_signup&deleterecord=' . $eachsignup['id'], __('Delete'), $messages->getDeleteAlert());
                        } else {
                            $deletionLink = '';
                        }

                        $tablecells .= wf_TableCell($deletionLink);
                    }
                }

                $tablerows .= wf_TableRow($tablecells, $rowClass);
                $totalCount++;
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        //stats
        $result .= wf_img_sized('skins/icon_stats_16.gif', '', '12') . ' ' . __('Total') . ': ' . $totalCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_ok.gif', '', '12') . ' ' . __('Alive') . ': ' . $aliveCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_passive.gif', '', '12') . ' ' . __('Frozen') . ': ' . $frozenCount . wf_tag('br');
        $result .= wf_tag('br');
        $result .= web_SignupsRenderChart($chartDataMonth, $chartDataDay);

        $reportTitle = (empty($yearMonth)) ? __('Current month user signups') : __('User signups by month') . ' ' . $cmonth;
        show_window($reportTitle, $result);
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
        $cells .= wf_TableCell($chartDay);
        $rows = wf_TableRow($cells);

        $result .= wf_tag('h3') . __('Administrators') . wf_tag('h3', true);
        $result .= wf_TableBody($rows, '100%', '0', '');
        return ($result);
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
        $tablecells .= wf_TableCell(__('Count'));
        $tablecells .= wf_TableCell(__('Visual'), '50%');
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($tcount)) {
            foreach ($tcount as $sigtariff => $eachcount) {

                $tablecells = wf_TableCell($sigtariff);
                $tablecells .= wf_TableCell($eachcount);
                $tablecells .= wf_TableCell(web_bar($eachcount, sizeof($allsignups)), '', '', 'sorttable_customkey="' . $eachcount . '"');
                $tablerows .= wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Tariffs report'), $result);
    }

    /**
     * Check is user active right now?
     * 
     * @param array $userData
     * 
     * @return bool
     */
    function zb_SignupCheckIsUserActive($userData) {
        $result = false;
        if (!empty($userData)) {
            if (($userData['Cash'] >= '-' . $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0) AND ( $userData['Down'] == 0)) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * 
     * @param int $year
     * 
     * @return array => alive/dead/total/deadlogins
     */
    function zb_SignupsGetAilveStats($year) {
        $result = array();
        $aliveTotal = 0;
        $deadTotal = 0;
        $year = vf($year, 3);
        $deadUserData = array();
        $allUsersData = array();
        $allUsersData = zb_UserGetAllStargazerDataAssoc();
        if (!empty($year)) {
            $query = "SELECT * from `userreg` WHERE `date` LIKE '" . $year . "-%';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $eachReg) {
                    //is user deleted?
                    if (isset($allUsersData[$eachReg['login']])) {
                        $userData = $allUsersData[$eachReg['login']];
                        if (zb_SignupCheckIsUserActive($userData)) {
                            $aliveTotal++;
                        } else {
                            $deadTotal++;
                            $deadUserData[$eachReg['login']] = $eachReg['login'];
                        }
                    } else {
                        //he is dead if deleted, yeah
                        $deadTotal++;
                    }
                }
            }
        }


        //forming results
        $result['alive'] = $aliveTotal;
        $result['dead'] = $deadTotal;
        $result['total'] = $aliveTotal + $deadTotal;
        $result['deadlogins'] = $deadUserData;
        return ($result);
    }

    /**
     * Deletes record from signups report database
     * 
     * @param int $sigrepid
     * 
     * @return void
     */
    function zb_SignupsDeleteRecord($sigrepid) {
        if (!empty($sigrepid)) {
            $signups = new NyanORM('userreg');
            $signups->where('id', '=', $sigrepid);
            $recordToDelete = $signups->getAll('', false); //dont flush where struct
            if (!empty($recordToDelete)) {
                $signups->delete();
                log_register('SIGREP DELETE [' . $sigrepid . '] FOR (' . $recordToDelete[0]['login'] . ') DATE `' . $recordToDelete[0]['date'] . '`');
            }
        }
    }

    //record deletion
    if (ubRouting::checkGet('deleterecord')) {
        zb_SignupsDeleteRecord(ubRouting::get('deleterecord', 'int'));
        ubRouting::nav('?module=report_signup');
    }

    //other report routines
    if (!ubRouting::checkGet('showdeadusers')) {
        if (!ubRouting::checkPost('yearsel')) {
            $year = curyear();
        } else {
            $year = ubRouting::post('yearsel');
        }

        $yearinputs = wf_YearSelectorPreset('yearsel', '', false, $year);
        $yearinputs .= wf_Submit(__('Show'));
        $yearform = wf_Form('?module=report_signup', 'POST', $yearinputs, 'glamour');
        $yearform .= wf_CleanDiv();

        web_SignupsShowToday();
        show_window(__('Year'), $yearform);
        web_SignupsGraphYear($year);
        web_SignupGraph();
        if ($altercfg['CEMETERY_ENABLED']) {
            $cemetery = new Cemetery();
            show_window('', $cemetery->renderChart());
        }

        //render some signups data by selected or current month
        web_SignupsMonthShow(ubRouting::get('month'));
    } else {
        web_SignupsShowDeadUsers(ubRouting::get('showdeadusers'));
    }

    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}

