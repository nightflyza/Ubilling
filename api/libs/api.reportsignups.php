<?php

/**
 * User signups report
 */
class ReportSignups {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Selected year to show
     *
     * @var string
     */
    protected $year = '';

    /**
     * Signups database abstraction layer placeholder
     *
     * @var object
     */
    protected $signupsDb = '';

    /**
     * Cemetery enabling flag
     *
     * @var bool
     */
    protected $cemeteryEnabled = false;

    /**
     * City column and cities stats rendering flag
     *
     * @var bool
     */
    protected $cityRenderEnabled = false;

    /**
     * Tariff popularity sub-report rendering flag
     *
     * @var bool
     */
    protected $tariffRenderEnabled = false;

    /**
     * Administrators signups charts rendering flag
     *
     * @var bool
     */
    protected $admRenderEnabled = false;

    /**
     * Cemetery object placeholder
     *
     * @var object
     */
    protected $cemetery = '';

    /**
     * System cache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains all user data as login=>userData cached
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains unique tariff names extracted from users as name=>name
     *
     * @var array
     */
    protected $allTariffNames = array();

    /**
     * Contains user tariffs as login=>tariff
     *
     * @var array
     */
    protected $allUserTariffs = array();

    /**
     * Contains user cities as login=>cityname
     *
     * @var array
     */
    protected $userCities = array();

    /**
     * Contains already loaded signups as dateLike=>signupsData
     *
     * @var array
     */
    protected $signupsLoaded = array();

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some routes, urls, defines etc
     */
    const TABLE_SIGNUPS = 'userreg';
    const URL_ME = '?module=report_signup';
    const URL_PROFILE = '?module=userprofile&username=';
    const URL_SIGMAP = '?module=report_sigmap';
    const URL_PONLAST = '?module=report_ponlastsig';
    const ROUTE_MONTH = 'month';
    const ROUTE_DEADUSERS = 'showdeadusers';
    const ROUTE_DELETERECORD = 'deleterecord';
    const PROUTE_YEAR = 'yearsel';
    const CACHE_TIMEOUT = 86400;

    public function __construct() {
        $this->loadConfig();
        $this->initDb();
        $this->initAllUserData();
        $this->loadTariffsData();
        $this->loadCitiesData();
        $this->initMessages();
        $this->initCache();
        $this->setYear();
    }

    /**
     * Preloads required configs into protected properties
     *
     * @global object $ubillingConfig
     *
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->cemeteryEnabled = (@$this->altCfg['CEMETERY_ENABLED']) ? true : false;
        $this->cityRenderEnabled = (@$this->altCfg['SIGREP_CITYRENDER']) ? true : false;
        $this->tariffRenderEnabled = (@$this->altCfg['SIGREP_TARIFFRENDER']) ? true : false;
        $this->admRenderEnabled = (@$this->altCfg['SIGREP_ADMRENDER']) ? true : false;
    }

    /**
     * Inits signups database abstraction layer
     *
     * @return void
     */
    protected function initDb() {
        $this->signupsDb = new NyanORM(self::TABLE_SIGNUPS);
    }

    /**
     * Inits system message helper for further usage
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits system cache object for further usage
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits all user data cache
     *
     * @return void
     */
    protected function initAllUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Extracts tariffs data from already loaded users data
     *
     * @return void
     */
    protected function loadTariffsData() {
        $this->allTariffNames = array();
        $this->allUserTariffs = array();
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $login => $userData) {
                $userTariff = (isset($userData['Tariff'])) ? $userData['Tariff'] : '';
                $this->allUserTariffs[$login] = $userTariff;
                if (!empty($userTariff)) {
                    $this->allTariffNames[$userTariff] = $userTariff;
                }
            }
        }
    }

    /**
     * Loads user cities data if city rendering is enabled
     *
     * @return void
     */
    protected function loadCitiesData() {
        $this->userCities = array();
        if ($this->cityRenderEnabled) {
            $this->userCities = zb_AddressGetCityUsers();
        }
    }

    /**
     * Inits cemetery instance if this feature is enabled
     *
     * @return void
     */
    protected function initCemetery() {
        if ($this->cemeteryEnabled) {
            if (empty($this->cemetery)) {
                $this->cemetery = new Cemetery();
            }
        }
    }

    /**
     * Sets selected year from request or current year as default
     *
     * @return void
     */
    protected function setYear() {
        if (!ubRouting::checkPost(self::PROUTE_YEAR)) {
            $this->year = curyear();
        } else {
            $this->year = ubRouting::post(self::PROUTE_YEAR);
        }
    }

    /**
     * Returns signups array filtered by date LIKE mask
     *
     * @param string $dateLike
     * @param bool $orderDesc
     *
     * @return array
     */
    protected function getSignups($dateLike, $orderDesc = true) {
        $result = array();
        if (isset($this->signupsLoaded[$dateLike])) {
            $result = $this->signupsLoaded[$dateLike];
        } else {
            $this->signupsDb->where('date', 'LIKE', $dateLike);
            if ($orderDesc) {
                $this->signupsDb->orderBy('date', 'DESC');
            }
            $result = $this->signupsDb->getAll();
            $this->signupsLoaded[$dateLike] = $result;
        }
        return ($result);
    }

    /**
     * Returns array like month_num=>signup_count
     *
     * @param int $year
     *
     * @return array
     */
    protected function getCountYear($year) {
        $months = months_array();
        $result = array();
        foreach ($months as $monthNum => $monthName) {
            $result[$monthNum] = 0;
        }

        $allYearSignups = $this->getSignups($year . '-%');
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

        return ($result);
    }

    /**
     * Check is user active right now?
     *
     * @param array $userData
     *
     * @return bool
     */
    protected function isUserActive($userData) {
        $result = false;
        if (!empty($userData)) {
            if (($userData['Cash'] >= '-' . $userData['Credit']) and ($userData['AlwaysOnline'] == 1) and ($userData['Passive'] == 0) and ($userData['Down'] == 0)) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Returns alive/dead/total/deadlogins stats for some year
     *
     * @param int $year
     *
     * @return array
     */
    protected function getAliveStats($year) {
        $result = array();
        $aliveTotal = 0;
        $deadTotal = 0;
        $year = vf($year, 3);
        $deadUserData = array();
        $allUsersData = zb_UserGetAllStargazerDataAssoc();
        if (!empty($year)) {
            $all = $this->getSignups($year . '-%');
            if (!empty($all)) {
                foreach ($all as $io => $eachReg) {
                    if (isset($allUsersData[$eachReg['login']])) {
                        $userData = $allUsersData[$eachReg['login']];
                        if ($this->isUserActive($userData)) {
                            $aliveTotal++;
                        } else {
                            $deadTotal++;
                            $deadUserData[$eachReg['login']] = $eachReg['login'];
                        }
                    } else {
                        $deadTotal++;
                    }
                }
            }
        }

        $result['alive'] = $aliveTotal;
        $result['dead'] = $deadTotal;
        $result['total'] = $aliveTotal + $deadTotal;
        $result['deadlogins'] = $deadUserData;
        return ($result);
    }

    /**
     * Returns cached alive stats for some year
     *
     * @param int $year
     *
     * @return array
     */
    protected function getCachedAliveStats($year) {
        $result = array();
        $cacheKey = 'SIGALIVESTATS_' . $year;
        $result = $this->cache->get($cacheKey, self::CACHE_TIMEOUT);
        if (!$result) {
            $result = $this->getAliveStats($year);
            $this->cache->set($cacheKey, $result, self::CACHE_TIMEOUT);
        }
        return ($result);
    }

    /**
     * Deletes record from signups report database
     *
     * @param int $sigrepid
     *
     * @return void
     */
    protected function deleteRecord($sigrepid) {
        if (!empty($sigrepid)) {
            $this->signupsDb->where('id', '=', $sigrepid);
            $recordToDelete = $this->signupsDb->getAll('', false);
            if (!empty($recordToDelete)) {
                $this->signupsDb->delete();
                log_register('SIGREP DELETE [' . $sigrepid . '] FOR (' . $recordToDelete[0]['login'] . ') DATE `' . $recordToDelete[0]['date'] . '`');
            } else {
                $this->signupsDb->where();
            }
        }
    }

    /**
     * Returns year selector form
     *
     * @return string
     */
    protected function renderYearForm() {
        $result = '';
        $yearinputs = wf_YearSelectorPreset(self::PROUTE_YEAR, '', false, $this->year);
        $yearinputs .= wf_Submit(__('Show'));
        $result .= wf_Form(self::URL_ME, 'POST', $yearinputs, 'glamour');
        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Shows signups performed today
     *
     * @return void
     */
    protected function renderToday() {
        $this->signupsDb->where('date', 'LIKE', curdate() . '%');
        $sigcount = $this->signupsDb->getFieldsCount('id');
        show_window('', $this->messages->getStyledMessage(__('Today signups') . ': ' . wf_tag('strong') . $sigcount . wf_tag('strong', true), 'info'));
    }

    /**
     * Shows user signups by year with funny bars
     *
     * @param int $year
     *
     * @return void
     */
    protected function renderYearGraph($year) {
        if ($this->cemeteryEnabled) {
            $this->initCemetery();
        }

        $year = vf($year);
        $yearcount = $this->getCountYear($year);
        $maxsignups = max($yearcount);
        $allmonths = months_array();
        $totalcount = 0;

        $tablecells = wf_TableCell('');
        $tablecells .= wf_TableCell(__('Month'));
        $tablecells .= wf_TableCell(__('Signups'));
        if ($this->cemeteryEnabled) {
            $tablecells .= wf_TableCell(__('Dead souls'));
            $tablecells .= wf_TableCell('', '10%');
        }
        $tablecells .= wf_TableCell(__('Visual'), '50%');
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($yearcount as $eachmonth => $count) {
            $totalcount = $totalcount + $count;
            $tablecells = wf_TableCell($eachmonth);
            $tablecells .= wf_TableCell(wf_Link(self::URL_ME . '&' . self::ROUTE_MONTH . '=' . $year . '-' . $eachmonth, rcms_date_localise($allmonths[$eachmonth])));
            $tablecells .= wf_TableCell($count);
            if ($this->cemeteryEnabled) {
                $deadDateMask = $year . '-' . $eachmonth . '-';
                $deadCount = $this->cemetery->getDeadDateCount($deadDateMask);
                $deadBar = web_barTariffs(abs($count), abs($deadCount));
                $tablecells .= wf_TableCell($deadCount);
                $tablecells .= wf_TableCell($deadBar);
            }
            $tablecells .= wf_TableCell(web_bar($count, $maxsignups), '', '', 'sorttable_customkey="' . $count . '"');
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }

        $aliveStats = $this->getCachedAliveStats($year);

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_tag('b', false) . __('Total users registered') . ': ' . $totalcount . wf_tag('b', true);
        if ($totalcount > 0) {
            $result .= wf_tag('br');
            $result .= ' ' . $aliveStats['alive'] . ' ' . __('of them remain active');
            $result .= ' ' . __('and') . ' ' . $aliveStats['dead'] . ' ' . wf_Link(self::URL_ME . '&' . self::ROUTE_DEADUSERS . '=' . $year, __('now is dead')) . ' (' . zb_PercentValue($aliveStats['total'], $aliveStats['dead']) . '%)';
        }

        $sigMapLinkControls = '';
        if (cfr('REPORTSIGNUP') and cfr('USERSMAP')) {
            $sigMapLinkControls .= wf_tag('div', false, '', 'style="float:right; margin-left: 5px; padding-top: 0px;"');
            $sigMapLinkControls .= wf_Link(self::URL_SIGMAP, wf_img_sized('skins/swmapsmall.png', '', '12') . ' ' . __('Signups map'), false, 'ubButton') . ' ';
            $sigMapLinkControls .= wf_tag('div', true);
        }

        $ponLastLinkControls = '';
        if (cfr('PON')) {
            if (@$this->altCfg['PON_ENABLED']) {
                $ponLastLinkControls .= wf_tag('div', false, '', 'style="float:right;  margin-left: 5px; padding-top: 0px;"');
                $ponLastLinkControls .= wf_Link(self::URL_PONLAST, wf_img_sized('skins/switch_models.png', '', '12') . ' ' . __('ONU signals'), false, 'ubButton') . ' ';
                $ponLastLinkControls .= wf_tag('div', true);
            }
        }

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
    protected function renderDeadUsers($year) {
        $year = vf($year, 3);
        $aliveStats = $this->getCachedAliveStats($year);
        $result = '';
        $result .= wf_BackLink(self::URL_ME);
        $result .= wf_delimiter();

        if (!empty($aliveStats)) {
            if (!empty($aliveStats['deadlogins'])) {
                $extraColumns = array();
                $phonesColumn = array();
                $contractDateColumn = array();
                $contractDates = new ContractDates();
                $allContractDates = $contractDates->getAllDatesBasic();
                if (@$this->altCfg['MOBILES_EXT']) {
                    $mobilesExt = new MobilesExt();
                }

                foreach ($aliveStats['deadlogins'] as $io => $login) {
                    $userData = @$this->allUserData[$login];
                    $userExtMobiles = '';
                    $allExt = array();
                    if (@$this->altCfg['MOBILES_EXT']) {
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
                    $phonesColumn[$login] = @$userData['mobile'] . ' ' . @$userData['phone'] . ' ' . $userExtMobiles;
                    $userContract = isset($userData['contract']) ? $userData['contract'] : '';
                    $contractDateColumn[$login] = isset($allContractDates[$userContract]) ? $allContractDates[$userContract] : '';
                }

                $extraColumns['Phones'] = $phonesColumn;
                $extraColumns['Contract date'] = $contractDateColumn;
                $options = '"dom": \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\', \'print\']';
                $result .= web_UserArrayShower($aliveStats['deadlogins'], $extraColumns, true, $options);
            }
        }

        show_window(__('Inactive') . ' ' . $year, $result);
    }

    /**
     * Shows current month signups
     *
     * @param string $yearMonth
     *
     * @return void
     */
    protected function renderMonth($yearMonth = '') {
        if (empty($yearMonth)) {
            $cmonth = curmonth();
        } else {
            $cmonth = ubRouting::filters($yearMonth, 'mres');
        }

        $deleatableFlag = @$this->altCfg['SIGREP_DELETABLE'] ? true : false;
        $cityColumn = $this->cityRenderEnabled;

        $curdate = curdate();
        $totalCount = 0;
        $frozenCount = 0;
        $aliveCount = 0;

        $signups = $this->getSignups($cmonth . '%');

        $ignoreUsers = array();
        if ($this->cemeteryEnabled) {
            $this->initCemetery();
            $ignoreUsers = $this->cemetery->getAllTagged();
        }

        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Date'));
        $tablecells .= wf_TableCell(__('Administrator'));
        if (@$this->altCfg['SIGREP_CONTRACT']) {
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
                $tablecells .= wf_TableCell('');
            }
        }
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($signups)) {
            $employeeLogins = ts_GetAllEmployeeLoginsAssocCached();
            foreach ($signups as $io => $eachsignup) {
                $tablecells = wf_TableCell($eachsignup['id']);
                $tablecells .= wf_TableCell($eachsignup['date']);

                $administratorName = (isset($employeeLogins[$eachsignup['admin']])) ? $employeeLogins[$eachsignup['admin']] : $eachsignup['admin'];
                $tablecells .= wf_TableCell($administratorName);

                if (@$this->altCfg['SIGREP_CONTRACT']) {
                    $tablecells .= wf_TableCell(@$allcontracts[$eachsignup['login']]);
                }
                $sigTariff = @$this->allUserTariffs[$eachsignup['login']];
                $tariffCellClass = ($sigTariff == '*_NO_TARIFF_*') ? 'undone' : '';

                $tablecells .= wf_TableCell($eachsignup['login']);
                $tablecells .= wf_TableCell($sigTariff, '', $tariffCellClass);
                $userState = '';
                $userStateMark = '';
                if (isset($this->allUserData[$eachsignup['login']])) {
                    $userData = $this->allUserData[$eachsignup['login']];
                    if ($this->isUserActive($userData)) {
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
                    $userState .= wf_img_sized('skins/icon_skull.png', __('Deleted'), '12');
                    $userStateMark = 'D';
                }
                $tablecells .= wf_TableCell($userState, '', '', 'sorttable_customkey="' . $userStateMark . '"');

                if ($cityColumn) {
                    $tablecells .= wf_TableCell(@$this->userCities[$eachsignup['login']]);
                }

                $profilelink = wf_Link(self::URL_PROFILE . trim($eachsignup['login']), web_profile_icon() . ' ' . $eachsignup['address']);
                $tablecells .= wf_TableCell($profilelink);
                if (ispos($eachsignup['date'], $curdate)) {
                    $rowClass = 'todaysig';
                } else {
                    $rowClass = 'row5';
                }
                if (isset($ignoreUsers[$eachsignup['login']])) {
                    $rowClass = 'sigcemeteryuser';
                }
                if (empty($sigTariff)) {
                    $rowClass = 'sigdeleteduser';
                }

                if ($deleatableFlag) {
                    if (cfr('ROOT')) {
                        if (empty($sigTariff)) {
                            $deletionLink = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETERECORD . '=' . $eachsignup['id'], __('Delete'), $this->messages->getDeleteAlert());
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
        $result .= wf_img_sized('skins/icon_stats_16.gif', '', '12') . ' ' . __('Total') . ': ' . $totalCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_ok.gif', '', '12') . ' ' . __('Alive') . ': ' . $aliveCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_passive.gif', '', '12') . ' ' . __('Frozen') . ': ' . $frozenCount . wf_tag('br');

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
    protected function renderChart($dataMonth, $dataDay) {
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
        $result .= wf_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * Shows administrators signups charts for selected or current month
     *
     * @return void
     */
    protected function renderAdmins() {
        if ($this->admRenderEnabled) {
            if (!ubRouting::checkGet(self::ROUTE_MONTH)) {
                $cmonth = curmonth();
            } else {
                $cmonth = ubRouting::get(self::ROUTE_MONTH, 'mres');
            }

            $allsignups = $this->getSignups($cmonth . '%');
            $chartDataMonth = array();
            $chartDataDay = array();
            $curdate = curdate();
            if (!empty($allsignups)) {
                $employeeLogins = ts_GetAllEmployeeLoginsAssocCached();
                foreach ($allsignups as $io => $eachsignup) {
                    $administratorName = (isset($employeeLogins[$eachsignup['admin']])) ? $employeeLogins[$eachsignup['admin']] : $eachsignup['admin'];
                    if (isset($chartDataMonth[$administratorName])) {
                        $chartDataMonth[$administratorName] ++;
                    } else {
                        $chartDataMonth[$administratorName] = 1;
                    }
                    if (ispos($eachsignup['date'], $curdate)) {
                        if (isset($chartDataDay[$administratorName])) {
                            $chartDataDay[$administratorName] ++;
                        } else {
                            $chartDataDay[$administratorName] = 1;
                        }
                    }
                }
            }

            $result = $this->renderChart($chartDataMonth, $chartDataDay);
            show_window(__('Administrators'), $result);
        }
    }

    /**
     * Shows signup tariffs popularity chart
     *
     * @return void
     */
    protected function renderTariffs() {
        if ($this->tariffRenderEnabled) {
            if (!ubRouting::checkGet(self::ROUTE_MONTH)) {
                $cmonth = curmonth();
            } else {
                $cmonth = ubRouting::get(self::ROUTE_MONTH, 'mres');
            }
            $allsignups = $this->getSignups($cmonth . '%');

            $tcount = array();
            if (!empty($allsignups)) {
                foreach ($this->allTariffNames as $io => $eachtariff) {
                    foreach ($allsignups as $ii => $eachsignup) {
                        if (@$this->allUserTariffs[$eachsignup['login']] == $eachtariff) {
                            @$tcount[$eachtariff] = $tcount[$eachtariff] + 1;
                        }
                    }
                }
            }

            $tablecells = wf_TableCell(__('Tariff'),'25%');
            $tablecells .= wf_TableCell(__('Count'),'25%');
            $tablecells .= wf_TableCell(__('Visual'), '50%');
            $tablerows = wf_TableRow($tablecells, 'row1');

            if (!empty($tcount)) {
                foreach ($tcount as $sigtariff => $eachcount) {
                    $tablecells = wf_TableCell($sigtariff);
                    $tablecells .= wf_TableCell($eachcount);
                    $tablecells .= wf_TableCell(web_bar($eachcount, sizeof($allsignups)), '', '', 'sorttable_customkey="' . $eachcount . '"');
                    $tablerows .= wf_TableRow($tablecells, 'row5');
                }
            }

            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
            show_window(__('Tariffs report'), $result);
        }
    }

    /**
     * Shows per-city signups stats for selected or current month
     *
     * @return void
     */
    protected function renderCities() {
        if ($this->cityRenderEnabled) {
            if (!ubRouting::checkGet(self::ROUTE_MONTH)) {
                $cmonth = curmonth();
            } else {
                $cmonth = ubRouting::get(self::ROUTE_MONTH, 'mres');
            }

            $allsignups = $this->getSignups($cmonth . '%');
            $cityCounts = array();
            if (!empty($allsignups)) {
                foreach ($allsignups as $io => $eachsignup) {
                    $userCity = (isset($this->userCities[$eachsignup['login']])) ? $this->userCities[$eachsignup['login']] : '';
                    if (!empty($userCity)) {
                        if (isset($cityCounts[$userCity])) {
                            $cityCounts[$userCity] ++;
                        } else {
                            $cityCounts[$userCity] = 1;
                        }
                    }
                }
            }

            if (!empty($cityCounts)) {
                $cells = wf_TableCell(__('City'),'25%');
                $cells .= wf_TableCell(__('Count'),'25%');
                $cells .= wf_TableCell(__('Visual'), '50%');
                $rows = wf_TableRow($cells, 'row1');
                foreach ($cityCounts as $cityName => $cityCount) {
                    $cells = wf_TableCell($cityName);
                    $cells .= wf_TableCell($cityCount);
                    $cells .= wf_TableCell(web_bar($cityCount, sizeof($allsignups)), '', '', 'sorttable_customkey="' . $cityCount . '"');
                    $rows .= wf_TableRow($cells, 'row5');
                }

                $result = wf_TableBody($rows, '100%', '0', 'sortable');
                show_window(__('Cities'), $result);
            }
        }
    }

    /**
     * Renders full signups report
     *
     * @return void
     */
    public function render() {
        if (ubRouting::checkGet(self::ROUTE_DELETERECORD)) {
            $this->deleteRecord(ubRouting::get(self::ROUTE_DELETERECORD, 'int'));
            ubRouting::nav(self::URL_ME);
        }

        if (!ubRouting::checkGet(self::ROUTE_DEADUSERS)) {
            $this->renderToday();
            show_window(__('Year'), $this->renderYearForm());
            $this->renderYearGraph($this->year);
            $this->renderTariffs();
            $this->renderCities();
            if ($this->cemeteryEnabled) {
                $this->initCemetery();
                show_window('', $this->cemetery->renderChart());
            }
            $this->renderMonth(ubRouting::get(self::ROUTE_MONTH));
            $this->renderAdmins();
        } else {
            $this->renderDeadUsers(ubRouting::get(self::ROUTE_DEADUSERS));
        }
    }

}
