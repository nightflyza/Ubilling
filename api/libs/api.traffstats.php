<?php

/**
 * Basic traffic stats/charts report implementation
 */
class TraffStats {
    /**
     * System alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Ishimura enabled flag
     *
     * @var string
     */
    protected $ishimuraFlag = false;

    /**
     * Ishimura database abstraction layer
     *
     * @var object
     */
    protected $ishimuraDb = '';

    /**
     * OphanimFlow integration flag
     *
     * @var bool
     */
    protected $ophanimFlag = false;

    /**
     * OphanimFlow object instance
     *
     * @var object
     */
    protected $ophanimFlow = '';

    /**
     * Contains all available users data
     *
     * @var array
     */
    protected $userData = array();

    /**
     * Contains ishimura user traffic stats
     *
     * @var array
     */
    protected $ishimuraData = array();

    /**
     * Contains TRAFFSIZE option value
     *
     * @var string
     */
    protected $trafScale = 'float';

    /**
     * Previous months traffic stats database abstraction layer
     *
     * @var object
     */
    protected $statsDb = '';

    /**
     * Contains previous user traffic stats  as year=>month=>data
     *
     * @var array
     */
    protected $trafStats = array();

    /**
     * Contains available traffic directions/classes as index=>ruleNum
     *
     * @var array
     */
    protected $dirs = array();

    /**
     * Contains current instance login
     *
     * @var string
     */
    protected $login = '';

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    //some other predefined stuff
    const ROUTE_PROX_IMG = 'loadimg';

    /**
     * Creates new traffStats instance
     *
     * @param string $login
     */
    public function __construct($login = '') {
        $this->setLogin($login);
        $this->initMessages();
        $this->loadConfigs();
        $this->initDbLayers();
    }

    /**
     * Sets current instance login
     *
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login = '') {
        if (!empty($login)) {
            $this->login = ubRouting::filters($login, 'mres');
        }
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
     * Loads traffic directions
     *
     * @return void
     */
    protected function loadDirs() {
        $this->dirs = zb_DirectionsGetAll();
    }

    /**
     * Loads required config and sets some properties
     *
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();

        $this->trafScale = trim($this->altCfg['TRAFFSIZE']);

        if ($this->altCfg[MultiGen::OPTION_ISHIMURA]) {
            $this->ishimuraFlag = true;
        }

        if ($this->altCfg[OphanimFlow::OPTION_ENABLED]) {
            $this->ophanimFlag = true;
        }
    }

    /**
     * Loads all available users data
     *
     * @return void
     */
    protected function loadUserData() {
        if (!empty($this->login)) {
            $this->userData = zb_UserGetStargazerData($this->login);
        }
    }

    /**
     * Inits required database abstraction layers and required objects
     *
     * @return void
     */
    protected function initDbLayers() {
        $this->statsDb = new NyanORM('stat');

        if ($this->ishimuraFlag) {
            $this->ishimuraDb = new NyanORM(MultiGen::NAS_ISHIMURA);
        }

        if ($this->ophanimFlag) {
            $this->ophanimFlow = new OphanimFlow();
        }
    }

    /**
     * Loads ishimura user traffic data as year=>month=>D0/U0
     * 
     * @return void
     */
    protected function loadIshimuraStats() {
        if (!empty($this->login)) {
            if ($this->ishimuraFlag) {
                $this->ishimuraDb->where('login', '=', $this->login);
                $rawStats = $this->ishimuraDb->getAll();
                if (!empty($rawStats)) {
                    foreach ($rawStats as $io => $each) {
                        $this->ishimuraData[$each['year']][$each['month']] = $each;
                    }
                }
            }
        }
    }

    /**
     * Returns native stargazer user current month traffic counters
     *
     * @param int $dirNum
     * 
     * @return array
     */
    protected function getUserDataCounters($dirNum) {
        $result = array();
        $dClass = 'D' . $dirNum;
        $uClass = 'U' . $dirNum;
        if (isset($this->userData[$dClass])) {
            $result[$dClass] = $this->userData[$dClass];
            $result[$uClass] = $this->userData[$uClass];
        }
        return ($result);
    }

    /**
     * Returns ishimura user traffic counters for selected year/month
     *
     * @param int $year
     * @param int $month
     * 
     * @return array
     */
    protected function getUserIshimuraCounters($year, $month) {
        $result = array();
        if (isset($this->ishimuraData[$year])) {
            if (isset($this->ishimuraData[$year][$month])) {
                $result['D0'] = $this->ishimuraData[$year][$month]['D0'];
                $result['U0'] = $this->ishimuraData[$year][$month]['U0'];
            }
        }
        return ($result);
    }

    /**
     * Returns fastly user IP from preloaded userdata
     *
     * @return string
     */
    protected function getUserIp() {
        $result = '';
        if (isset($this->userData['IP'])) {
            $result = $this->userData['IP'];
        }
        return ($result);
    }

    /**
     * Loads all previous user traffic stats into protected property
     *
     * @return void
     */
    protected function loadTraffStats() {
        if (!empty($this->login)) {
            $this->statsDb->where('login', '=', $this->login);
            $this->statsDb->orderBy('year`,`month', 'ASC');
            $this->trafStats = $this->statsDb->getAll();
        }
    }

    /**
     * Returns stats record for selected year/month/class
     *

     * @param int $classNum
     * 
     * @return array
     */
    protected function getUserPrevMonthTraff($classNum) {
        $result = array();
        if (!empty($this->trafStats)) {
            foreach ($this->trafStats as $io => $each) {
                if (isset($each['D' . $classNum])) {
                    $result[] = $each;
                }
            }
        }
        return ($result);
    }




    /**
     * Render current month traffic report part
     * 
     * @return string
     */
    protected function renderCurMonthStats() {
        $result = '';
        $cells = wf_TableCell(__('Traffic classes'));
        $cells .= wf_TableCell(__('Downloaded'));
        $cells .= wf_TableCell(__('Uploaded'));
        $cells .= wf_TableCell(__('Total'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($this->dirs)) {
            foreach ($this->dirs as $dir) {
                $downup = $this->getUserDataCounters($dir['rulenumber']);

                //yeah, no classes at all
                if ($dir['rulenumber'] == 0) {
                    //ishimura enabled?
                    if ($this->ishimuraFlag) {
                        $dataHideki = $this->getUserIshimuraCounters(curyear(), date("n"));
                        if (isset($downup['D0'])) {
                            @$downup['D0'] += $dataHideki['D0'];
                            @$downup['U0'] += $dataHideki['U0'];
                        } else {
                            $downup['D0'] = $dataHideki['D0'];
                            $downup['U0'] = $dataHideki['U0'];
                        }
                    }

                    //or ophanim flow may be?
                    if ($this->ophanimFlag) {
                        $ophanimCmonth = $this->ophanimFlow->getUserCurMonthTraff($this->login);
                        if (isset($downup['D0'])) {
                            $downup['D0'] += $ophanimCmonth['D0'];
                            $downup['U0'] += $ophanimCmonth['U0'];
                        } else {
                            $downup['D0'] = $ophanimCmonth['D0'];
                            $downup['U0'] = $ophanimCmonth['U0'];
                        }
                    }
                }

                $dLabel = zb_convertSize($downup['D' . $dir['rulenumber']], $this->trafScale);
                $uLabel = zb_convertSize($downup['U' . $dir['rulenumber']], $this->trafScale);
                $totalLabel = zb_convertSize(($downup['U' . $dir['rulenumber']] + $downup['D' . $dir['rulenumber']]), $this->trafScale);

                $cells = wf_TableCell($dir['rulename']);
                $cells .= wf_TableCell($dLabel, '', '', 'sorttable_customkey="' . $downup['D' . $dir['rulenumber']] . '"');
                $cells .= wf_TableCell($uLabel, '', '', 'sorttable_customkey="' . $downup['U' . $dir['rulenumber']] . '"');
                $cells .= wf_TableCell($totalLabel, '', '', 'sorttable_customkey="' . ($downup['U' . $dir['rulenumber']] + $downup['D' . $dir['rulenumber']]) . '"');
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result .= wf_tag('h3') . __('Current month traffic stats') . wf_tag('h3', true);
        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Generates user IP graph images links array
     * 
     * @return array
     */
    protected function genGraphLinks() {
        global $ubillingConfig;
        $urls = array();
        $zbxGraphsEnabled = $ubillingConfig->getAlterParam('ZABBIX_USER_TRAFFIC_GRAPHS');
        $zbxGraphsSearchIdnetify = ($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHIDENTIFY')) ? $ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHIDENTIFY') : 'MAC';
        $zbxGraphsSearchField = ($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHFIELD')) ? $ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHFIELD') : 'name';
        $zbxGraphsExtended = wf_getBoolFromVar($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_EXTENDED'));
        $mlgUseMikrotikGraphs = wf_getBoolFromVar($ubillingConfig->getAlterParam('MULTIGEN_USE_ROS_TRAFFIC_GRAPHS'));

        $ip = $this->getUserIp();
        $bwdUrl = zb_BandwidthdGetUrl($ip);
        $netid = zb_NetworkGetByIp($ip);
        $nasid = zb_NasGetByNet($netid);
        $nasdata = zb_NasGetData($nasid);
        $nastype = ($mlgUseMikrotikGraphs) ? 'mikrotik' : $nasdata['nastype'];

        $zbxAllGraphs = array();

        if ($zbxGraphsEnabled) {
            $zbxAllGraphs = getCachedZabbixNASGraphIDs();
        }

        if (!empty($zbxAllGraphs) and isset($zbxAllGraphs[$nasdata['nasip']])) {
            switch ($zbxGraphsSearchIdnetify) {
                case 'MAC':
                    $userSearchIdentify = zb_MultinetGetMAC($ip);
                    break;

                case 'login':
                    $userSearchIdentify = zb_UserGetLoginByIp($ip);
                    break;

                default:
                    $userSearchIdentify = $ip;
            }

            $urls = getZabbixUserGraphLinks($ip, $zbxGraphsSearchField, $userSearchIdentify, $zbxAllGraphs, $zbxGraphsExtended);
        } else {
            if (!empty($bwdUrl)) {
                // RouterOS graph model:
                if ($nastype == 'mikrotik') {
                    // Get user's IP array:
                    $alluserips = zb_UserGetAllIPs();
                    $alluserips = array_flip($alluserips);
                    if (!ispos($bwdUrl, 'pppoe') and !$mlgUseMikrotikGraphs) {
                        // Generate graphs paths:
                        $urls['dayr'] = $bwdUrl . '/' . $alluserips[$ip] . '/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . '/' . $alluserips[$ip] . '/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . '/' . $alluserips[$ip] . '/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . '/' . $alluserips[$ip] . '/yearly.gif';
                        $urls['years'] = null;
                    } elseif ($mlgUseMikrotikGraphs) {
                        $urls['dayr'] = $bwdUrl . '/' . 'mlg_' . $ip . '/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . '/' . 'mlg_' . $ip . '/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . '/' . 'mlg_' . $ip . '/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . '/' . 'mlg_' . $ip . '/yearly.gif';
                    } else {
                        $urls['dayr'] = $bwdUrl . $alluserips[$ip] . '>/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . $alluserips[$ip] . '>/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . $alluserips[$ip] . '>/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . $alluserips[$ip] . '>/yearly.gif';
                        $urls['years'] = null;
                    }
                } else {
                    // Banwidthd graphs model:
                    $urls['dayr'] = $bwdUrl . '/' . $ip . '-1-R.png';
                    $urls['days'] = $bwdUrl . '/' . $ip . '-1-S.png';
                    $urls['weekr'] = $bwdUrl . '/' . $ip . '-2-R.png';
                    $urls['weeks'] = $bwdUrl . '/' . $ip . '-2-S.png';
                    $urls['monthr'] = $bwdUrl . '/' . $ip . '-3-R.png';
                    $urls['months'] = $bwdUrl . '/' . $ip . '-3-S.png';
                    $urls['yearr'] = $bwdUrl . '/' . $ip . '-4-R.png';
                    $urls['years'] = $bwdUrl . '/' . $ip . '-4-S.png';

                    //OphanimFlow graphs
                    if (ispos($bwdUrl, 'OphanimFlow') or ispos($bwdUrl, 'of/')) {
                        $urls['hourr'] = $bwdUrl . '/?module=graph&dir=R&period=hour&ip=' . $ip;
                        $urls['hours'] = $bwdUrl . '/?module=graph&dir=S&period=hour&ip=' . $ip;
                        $urls['dayr'] = $bwdUrl . '/?module=graph&dir=R&period=day&ip=' . $ip;
                        $urls['days'] = $bwdUrl . '/?module=graph&dir=S&period=day&ip=' . $ip;
                        $urls['weekr'] = $bwdUrl . '/?module=graph&dir=R&period=week&ip=' . $ip;
                        $urls['weeks'] = $bwdUrl . '/?module=graph&dir=S&period=week&ip=' . $ip;
                        $urls['monthr'] = $bwdUrl . '/?module=graph&dir=R&period=month&ip=' . $ip;
                        $urls['months'] = $bwdUrl . '/?module=graph&dir=S&period=month&ip=' . $ip;
                        $urls['yearr'] = $bwdUrl . '/?module=graph&dir=R&period=year&ip=' . $ip;
                        $urls['years'] = $bwdUrl . '/?module=graph&dir=S&period=year&ip=' . $ip;
                    }
                }
                //MikroTik Multigen Hotspot users
                if (ispos($bwdUrl, 'mlgmths')) {
                    $bwdUrl = str_replace('mlgmths', 'graphs/queue/', $bwdUrl);
                    $allUserMacs = zb_UserGetAllIpMACs();
                    if (isset($allUserMacs[$ip])) {
                        $userMac = $allUserMacs[$ip];
                        $userMacUpper = strtoupper($userMac);
                        $queueName = '<hotspot-' . urlencode($userMacUpper);

                        $urls['dayr'] = $bwdUrl . $queueName . '>/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . $queueName . '>/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . $queueName . '>/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . $queueName . '>/yearly.gif';
                        $urls['years'] = null;
                    }
                }
                //MikroTik Multigen PPP
                if (ispos($bwdUrl, 'mlgmtppp')) {
                    $bwdUrl = str_replace('mlgmtppp', 'graphs/queue/', $bwdUrl);

                    $alluserips = zb_UserGetAllIPs();
                    $alluserips = array_flip($alluserips);

                    if (isset($alluserips[$ip])) {
                        $userLogin = $alluserips[$ip];
                        $queueName = urlencode('<pppoe-' . $userLogin . '>');

                        $urls['dayr'] = $bwdUrl . $queueName . '/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . $queueName . '/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . $queueName . '/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . $queueName . '/yearly.gif';
                        $urls['years'] = null;
                    }
                }

                //MikroTik Multigen DHCP
                if (ispos($bwdUrl, 'mlgmtdhcp')) {
                    $bwdUrl = str_replace('mlgmtdhcp', 'graphs/queue/', $bwdUrl);

                    $allUserMacs = zb_UserGetAllIpMACs();
                    if (isset($allUserMacs[$ip])) {
                        $userMac = $allUserMacs[$ip];
                        $userMacUpper = strtoupper($userMac);
                        $queueName = 'dhcp-ds<' . urlencode($userMacUpper);

                        $urls['dayr'] = $bwdUrl . $queueName . '>/daily.gif';
                        $urls['days'] = null;
                        $urls['weekr'] = $bwdUrl . $queueName . '>/weekly.gif';
                        $urls['weeks'] = null;
                        $urls['monthr'] = $bwdUrl . $queueName . '>/monthly.gif';
                        $urls['months'] = null;
                        $urls['yearr'] = $bwdUrl . $queueName . '>/yearly.gif';
                        $urls['years'] = null;
                    }
                }
            }
        }

        return ($urls);
    }

    /**
     * Renders user traffic charts
     * 
     * @return string
     */
    protected function renderCharts() {
        $result = '';
        $bwd = $this->genGraphLinks();

        if (!empty($bwd)) {
            $chartCategories = array(
                'hour' => __('Hour'),
                'day' => __('Graph by day'),
                'week' => __('Graph by week'),
                'month' => __('Graph by month'),
                'year' => __('Graph by year'),
            );

            $icon = wf_img_sized('skins/icon_stats.gif', '', '16', '16');

            // Hour button:
            $hourbw = '';
            if (isset($bwd['hourr']) and isset($bwd['hours'])) {
                $hourbw = wf_img(zb_BandwidthdImgLink($bwd['hourr']), __('Downloaded'));
                $hourbw .= wf_img(zb_BandwidthdImgLink($bwd['hours']), __('Uploaded'));
            }

            // Daily graph button:
            $daybw = wf_img(zb_BandwidthdImgLink($bwd['dayr']), __('Downloaded'));
            if (!empty($bwd['days'])) {
                $daybw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['days']), __('Uploaded'));
            }

            // Weekly graph button:
            $weekbw = wf_img(zb_BandwidthdImgLink($bwd['weekr']), __('Downloaded'));
            if (!empty($bwd['weeks'])) {
                $weekbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['weeks']), __('Uploaded'));
            }

            // Monthly graph button:
            $monthbw = wf_img(zb_BandwidthdImgLink($bwd['monthr']), __('Downloaded'));
            if (!empty($bwd['months'])) {
                $monthbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['months']), __('Uploaded'));
            }

            // Yearly graph button:
            $yearbw = wf_img(zb_BandwidthdImgLink($bwd['yearr']), __('Downloaded'));
            if (!empty($bwd['years'])) {
                $yearbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['years']), __('Uploaded'));
            }

            $result .= wf_delimiter();
            $result .= wf_tag('h3') . __('Graphs') . wf_tag('h3', true);

            $bwcells = '';
            $zbxExtended = (isset($bwd['zbxexten']) and $bwd['zbxexten'] == true);

            if ($zbxExtended) {
                $fiveminsbw = wf_img($bwd['5mins'], __('Downloaded'));
                $zbxLink = $bwd['zbxlink'];
                $zbxIcon = wf_img_sized('skins/zabbix_ico.png', '', '16', '16');

                $bwcells .= wf_TableCell(wf_link($zbxLink,  $zbxIcon . ' ' . __('Go to graph on Zabbix server'), false, 'ubButton', 'target="__blank"'));
                $bwcells .= wf_TableCell(wf_modalAuto($icon . ' ' . __('Graph by 5 minutes'), __('Graph by 5 minutes'), $fiveminsbw, 'ubButton'));
            }


            foreach ($chartCategories as $categoryId => $categoryTitle) {
                $chartBody = '';
                $dlKey = $categoryId . 'r';
                $ulKey = $categoryId . 's';
                $chartTitle = $categoryTitle;

                if (isset($bwd[$dlKey])) {
                    $imgLink = zb_BandwidthdImgLink($bwd[$dlKey]);
                    $imgBody = wf_img_sized($imgLink, __('Downloaded'), '100%');
                    $chartBody .= $imgBody;
                }

                if (isset($bwd[$ulKey])) {
                    $imgLink = zb_BandwidthdImgLink($bwd[$ulKey]);
                    $imgBody = wf_delimiter() . wf_img_sized($imgLink, __('Uploaded'), '100%');
                    $chartBody .= $imgBody;
                }

                if (!empty($chartBody)) {
                    $chartBody = wf_tag('div', false, '', 'style="width:100%;"') . $chartBody . wf_tag('div', true);
                    $bwcells .= wf_TableCell(wf_modalAuto($icon . ' ' . $chartTitle, $chartTitle, $chartBody, 'ubButton'));
                }
            }


            // Adding graphs buttons to result:
            $bwrows = wf_TableRow($bwcells);
            $result .= wf_TableBody($bwrows, '', '0', '');
            $result .= wf_delimiter();
        }

        return ($result);
    }


    /**
     * Renders all previous months user traffic stats
     *
     * @return string
     */
    protected function renderPrevMonthStats() {
        $result = '';
        $ishimuraTable = 'mlg_ishimura';
        $monthNames = months_array_wz();
        $result .= wf_tag('h3') . __('Previous month traffic stats') . wf_tag('h3', true);

        $cells = wf_TableCell(__('Year'));
        $cells .= wf_TableCell(__('Month'));
        $cells .= wf_TableCell(__('Traffic classes'));
        $cells .= wf_TableCell(__('Downloaded'));
        $cells .= wf_TableCell(__('Uploaded'));
        $cells .= wf_TableCell(__('Total'));
        $cells .= wf_TableCell(__('Cash'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->dirs)) {
            foreach ($this->dirs as $dir) {
                $prevmonths = $this->getUserPrevMonthTraff($dir['rulenumber']);

                //and again no classes
                if ($dir['rulenumber'] == 0) {
                    //ishimura traffic accounting?
                    if ($this->ishimuraFlag) {
                        $dataHideki = $this->ishimuraData;
                        if (!empty($dataHideki)) {
                            foreach ($dataHideki as $year => $month) {
                                foreach ($month as $each) {
                                    foreach ($prevmonths as $ia => $stgEach) {
                                        if ($stgEach['year'] == $each['year'] and $stgEach['month'] == $each['month']) {
                                            $prevmonths[$ia]['D0'] += $each['D0'];
                                            $prevmonths[$ia]['U0'] += $each['U0'];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //or just OphanimFlow integration?
                    if ($this->ophanimFlag) {
                        $ophRaw = $this->ophanimFlow->getUserAllTraff($this->login);
                        if (!empty($ophRaw)) {
                            foreach ($ophRaw as $io => $each) {
                                foreach ($prevmonths as $ia => $stgEach) {
                                    if ($stgEach['year'] == $each['year'] and $stgEach['month'] == $each['month']) {
                                        $prevmonths[$ia]['D0'] += $each['D0'];
                                        $prevmonths[$ia]['U0'] += $each['U0'];
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($prevmonths)) {
                    $prevmonths = array_reverse($prevmonths);
                }


                if (!empty($prevmonths)) {
                    foreach ($prevmonths as $prevmonth) {
                        $cells = wf_TableCell($prevmonth['year']);
                        $cells .= wf_TableCell(rcms_date_localise($monthNames[$prevmonth['month']]));
                        $cells .= wf_TableCell($dir['rulename']);
                        $cells .= wf_TableCell(stg_convert_size($prevmonth['D' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $prevmonth['D' . $dir['rulenumber']] . '"');
                        $cells .= wf_TableCell(stg_convert_size($prevmonth['U' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $prevmonth['U' . $dir['rulenumber']] . '"');
                        $cells .= wf_TableCell(stg_convert_size(($prevmonth['U' . $dir['rulenumber']] + $prevmonth['D' . $dir['rulenumber']])), '', '', 'sorttable_customkey="' . ($prevmonth['U' . $dir['rulenumber']] + $prevmonth['D' . $dir['rulenumber']]) . '"');
                        $cells .= wf_TableCell(round($prevmonth['cash'], 2));
                        $rows .= wf_TableRow($cells, 'row3');
                    }
                }
            }
        }

        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Generates user`s traffic full statistics report
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderUserTraffStats() {
        $result = '';

        $this->loadUserData();
        if (!empty($this->userData)) {
            $this->loadDirs();
            $this->loadTraffStats();
            $this->loadIshimuraStats();

            // Current month traffic stats
            $result .= $this->renderCurMonthStats();

            // Some charts here
            $result .= $this->renderCharts();

            // Traffic statistic by previous months
            $result .= $this->renderPrevMonthStats();

            // and some user controls
            $result .= web_UserControls($this->login);
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': EMPTY_DATABASE_USERDATA', 'error');
            $result .= wf_delimiter(0);
            $result .= wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true);
            $result .= wf_delimiter();
        }
        return ($result);
    }

    /**
     * Catches image proxy request and renders some image
     *
     * @return void
     */
    public function catchImgProxyRequest() {
        if ($this->altCfg['BANDWIDTHD_PROXY']) {
            if (ubRouting::checkGet(self::ROUTE_PROX_IMG)) {
                $remoteImageUrl = base64_decode(ubRouting::get(self::ROUTE_PROX_IMG));
                $remoteImageUrl = trim($remoteImageUrl);
                if (!empty($remoteImageUrl)) {
                    $remoteImg = new OmaeUrl($remoteImageUrl);
                    $remoteImg->setTimeout(1);
                    $rawImg = $remoteImg->response();
                    $recvErr = $remoteImg->error();
                    $type = '';
                    if (ispos($remoteImageUrl, '.png') or ispos($remoteImageUrl, 'module=graph')) {
                        $type = 'png';
                    } else {
                        if (ispos($remoteImageUrl, '.gif')) {
                            $type = 'gif';
                        }
                    }

                    if (empty($recvErr) and !ispos($rawImg, '404')) {
                        if (!empty($type)) {
                            header('Content-Type: image/' . $type);
                        }

                        die($rawImg);
                    } else {
                        header('Content-Type: image/jpeg');
                        $noImage = file_get_contents('skins/noimage.jpg');
                        die($noImage);
                    }
                } else {
                    header('Content-Type: image/jpeg');
                    $noImage = file_get_contents('skins/noimage.jpg');
                    die($noImage);
                }
            }
        }
    }
}
