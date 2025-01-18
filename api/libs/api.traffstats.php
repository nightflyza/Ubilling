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
     * On-demand charts loading flag
     *
     * @var bool
     */
    protected $ondemandFlag = true;

    /**
     * System caching object instance
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default caching timeout
     *
     * @var int
     */
    protected $cachingTimeout = 3600;

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    //some other predefined stuff
    const URL_ME = '?module=traffstats';
    const ROUTE_PROX_IMG = 'loadimg';
    const ROUTE_LOGIN = 'username';
    const ROUTE_AJUSER = 'defferedgraph';
    const ROUTE_AJCAT = 'grcat';
    const ROUTE_EXPLICT = 'explict';
    const PROUTE_DATE_FROM = 'datefrom';
    const PROUTE_DATE_TO = 'dateto';
    const PROUTE_TIME_FROM = 'timefrom';
    const PROUTE_TIME_TO = 'timeto';
    const KEY_GRAPH = 'DEFFEREDGRAPH';
    const AJ_CONTAINER = 'ajdefferedcontainer';

    /**
     * Creates new traffStats instance
     *
     * @param string $login
     */
    public function __construct($login = '') {
        $this->setLogin($login);
        $this->initMessages();
        $this->initCache();
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
     * Inits system caching engine
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
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
        $this->ondemandFlag = $ubillingConfig->getAlterParam('ONDEMAND_CHARTS', false);

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
            $this->statsDb->orderBy('year,month', 'ASC');
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
     * Returns the custom dimensions for OphanimFlow charts. 
     * The custom dimensions in the format '&w={width}&h={height}' or array[w,h], or an empty string|array if no dimensions are found.
     * 
     * @param bool $asArray Whether to return the dimensions as an array or a string.
     *
     * @return string|array
     */
    protected function getOphCustomDimensions($asArray = false) {
        $result = ($asArray) ? array() : '';
        $delimiter = 'x';
        if (isset($this->altCfg[OphanimFlow::OPTION_DIMENSIONS])) {
            $optionValue = $this->altCfg[OphanimFlow::OPTION_DIMENSIONS];
            if (!empty($optionValue) and ispos($optionValue, $delimiter)) {
                $raw = explode($delimiter, $optionValue);
                if (isset($raw[0]) and isset($raw[1])) {
                    $w = ubRouting::filters($raw[0], 'int');
                    $h = ubRouting::filters($raw[1], 'int');
                    if ($w and $h) {
                        if ($asArray) {
                            $result['w'] = $w;
                            $result['h'] = $h;
                        } else {
                            $result .= '&w=' . $w . '&h=' . $h;
                        }
                    }
                }
            }
        }
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
        $ofUseMikrotikGraphs = wf_getBoolFromVar($ubillingConfig->getAlterParam('OPHANIM_USE_ROS_TRAFFIC_GRAPHS'));

        $ip = $this->getUserIp();
        $bwdUrl = zb_BandwidthdGetUrl($ip);
        $netid = zb_NetworkGetByIp($ip);
        $nasid = zb_NasGetByNet($netid);
        if ($nasid) {
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
                    if ($nastype == 'mikrotik' and !$ofUseMikrotikGraphs) {
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
                            $customDimensions = $this->getOphCustomDimensions();
                            $urls['hourr'] = $bwdUrl . '/?module=graph&dir=R&period=hour&ip=' . $ip . $customDimensions;
                            $urls['hours'] = $bwdUrl . '/?module=graph&dir=S&period=hour&ip=' . $ip . $customDimensions;
                            $urls['dayr'] = $bwdUrl . '/?module=graph&dir=R&period=day&ip=' . $ip . $customDimensions;
                            $urls['days'] = $bwdUrl . '/?module=graph&dir=S&period=day&ip=' . $ip . $customDimensions;
                            $urls['weekr'] = $bwdUrl . '/?module=graph&dir=R&period=week&ip=' . $ip . $customDimensions;
                            $urls['weeks'] = $bwdUrl . '/?module=graph&dir=S&period=week&ip=' . $ip . $customDimensions;
                            $urls['monthr'] = $bwdUrl . '/?module=graph&dir=R&period=month&ip=' . $ip . $customDimensions;
                            $urls['months'] = $bwdUrl . '/?module=graph&dir=S&period=month&ip=' . $ip . $customDimensions;
                            $urls['yearr'] = $bwdUrl . '/?module=graph&dir=R&period=year&ip=' . $ip . $customDimensions;
                            $urls['years'] = $bwdUrl . '/?module=graph&dir=S&period=year&ip=' . $ip . $customDimensions;
                            $urls['explict'] = $bwdUrl . '/?module=graph&period=explict&ip=' . $ip . $customDimensions;
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
        }
        return ($urls);
    }


    /**
     * Returns explict time interval selection form and results
     *
     * @param string $explictUrl base64 encoded base charts URL
     * 
     * @return void
     */
    public function renderExplictChartsForm($explictUrl) {
        $result = '';
        if (!empty($explictUrl)) {
            $baseUrl = base64_decode($explictUrl);

            $dateFrom = (ubRouting::checkPost(self::PROUTE_DATE_FROM)) ? ubRouting::post(self::PROUTE_DATE_FROM) : curdate();
            $dateTo = (ubRouting::checkPost(self::PROUTE_DATE_TO)) ? ubRouting::post(self::PROUTE_DATE_TO) : curdate();
            $timeFrom = (ubRouting::checkPost(self::PROUTE_TIME_FROM)) ? ubRouting::post(self::PROUTE_TIME_FROM) : '00:00';
            $timeTo = (ubRouting::checkPost(self::PROUTE_TIME_TO)) ? ubRouting::post(self::PROUTE_TIME_TO) : '23:59';
            $timestampFrom = strtotime($dateFrom . ' ' . $timeFrom . ':00');
            $timestampTo = strtotime($dateTo . ' ' . $timeTo . ':59');

            //interval selection form here
            $inputs = '';
            $inputs .= __('From') . ': ';
            $inputs .= wf_DatePickerPreset(self::PROUTE_DATE_FROM, $dateFrom, true);
            $inputs .= wf_TimePickerPreset(self::PROUTE_TIME_FROM, $timeFrom);
            $inputs .= __('To') . ': ';
            $inputs .= wf_DatePickerPreset(self::PROUTE_DATE_TO, $dateTo, true);
            $inputs .= wf_TimePickerPreset(self::PROUTE_TIME_TO, $timeTo) . ' ';
            $inputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');

            //charts rendering
            if ($timestampFrom <= $timestampTo) {
                $completeUrl = $baseUrl . '&from=' . $timestampFrom . '&to=' . $timestampTo;
                $downloadUrl = zb_BandwidthdImgLink(($completeUrl . '&dir=R'));
                $uploadUrl = zb_BandwidthdImgLink(($completeUrl . '&dir=S'));

                $result .= wf_delimiter();
                $result .= wf_img_sized($downloadUrl, __('Downloaded'), '100%');
                $result .= wf_delimiter(0);
                $result .= wf_img_sized($uploadUrl, __('Uploaded'), '100%');
            } else {
                $result .= $this->messages->getStyledMessage(__('Wrong date format'), 'error');
                $result .= wf_delimiter(0);
                $result .= wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true);
                $result .= wf_delimiter();
            }
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_LOGIN . '=' . $this->login);
        return ($result);
    }

    /**
     * Renders user traffic charts
     * 
     * @return string
     */
    protected function renderCharts() {
        $result = '';
        $bwd = $this->genGraphLinks();
        $cachedUserCharts = array();
        $cachedCharts = array();
        $updateCache = false;

        $chartCategories = array(
            'hour' => __('Hour'),
            'day' => __('Graph by day'),
            'week' => __('Graph by week'),
            'month' => __('Graph by month'),
            'year' => __('Graph by year'),
        );


        if (!empty($bwd)) {
            $icon = wf_img_sized('skins/icon_stats.gif', '', '16', '16');
            $result .= wf_delimiter();
            $result .= wf_tag('h3') . __('Graphs') . wf_tag('h3', true);

            $bwcells = '';
            $zbxExtended = (isset($bwd['zbxexten']) and $bwd['zbxexten'] == true);

            if ($zbxExtended) {
                $fiveminsbw = wf_img($bwd['5mins'], __('Downloaded'));
                $zbxLink = $bwd['zbxlink'];
                $zbxIcon = wf_img_sized('skins/zabbix_ico.png', '', '16', '16');

                $bwcells .= wf_link($zbxLink,  $zbxIcon . ' ' . __('Go to graph on Zabbix server'), false, 'ubButton', 'target="__blank"');
                $bwcells .= wf_modalAuto($icon . ' ' . __('Graph by 5 minutes'), __('Graph by 5 minutes'), $fiveminsbw, 'ubButton');
            }


            if ($this->ondemandFlag) {
                $cachedCharts = $this->cache->get(self::KEY_GRAPH, $this->cachingTimeout);
                if (!empty($cachedCharts)) {
                    if (isset($cachedCharts[$this->login])) {
                        $cachedUserCharts = $cachedCharts[$this->login];
                    }
                } else {
                    $cachedCharts = array();
                }
            }

            if ($this->ondemandFlag) {
                if (empty($cachedUserCharts)) {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            //charts cats processing here
            if ($updateCache) {
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
                        if ($this->ondemandFlag) {
                            $cachedCharts[$this->login][$categoryId] = array('title' => $chartTitle, 'body' => $chartBody);
                            $defLink = $this->getAjChartLink($categoryId, $chartTitle);
                            $bwcells .= $defLink;
                        } else {
                            $chartBody = wf_tag('div', false, '', 'style="width:100%;"') . $chartBody . wf_tag('div', true);
                            $bwcells .= wf_modalAuto($icon . ' ' . $chartTitle, $chartTitle, $chartBody, 'ubButton');
                        }
                    }
                }

                //cache update
                if ($this->ondemandFlag) {
                    $this->cache->set(self::KEY_GRAPH, $cachedCharts, $this->cachingTimeout);
                }
            } else {
                //deffered cache filled
                if ($this->ondemandFlag) {
                    foreach ($chartCategories as $categoryId => $categoryTitle) {
                        $defLink = $this->getAjChartLink($categoryId, $categoryTitle);
                        $bwcells .= wf_TableCell($defLink);
                    }
                }
            }

            // Explict ophanim charts controls here
            if (isset($bwd['explict'])) {
                $encUrl = base64_encode($bwd['explict']);
                $explictUrl = self::URL_ME . '&' . self::ROUTE_LOGIN . '=' . $this->login . '&' . self::ROUTE_EXPLICT . '=' . $encUrl;
                $bwcells .= wf_Link($explictUrl, web_icon_calendar() . ' ' . __('Explict interval'), false, 'ubButton');
            }

            // Adding graphs buttons to result:
            $result .= $bwcells;
            if ($this->ondemandFlag) {
                $result .= wf_AjaxLoader();
                $result .= wf_AjaxContainer(self::AJ_CONTAINER);
            }

            $result .= wf_delimiter();
        }

        return ($result);
    }

    /**
     * Returns link body for deffered charts loading
     *
     * @param string $graphCat
     * @param string $title
     * 
     * @return string
     */
    protected function getAjChartLink($graphCat, $title) {
        $result = '';
        $icon = wf_img_sized('skins/icon_stats.gif', '', '16', '16');
        $defUrl = self::URL_ME . '&' . self::ROUTE_AJUSER . '=' . $this->login . '&' . self::ROUTE_AJCAT . '=' . $graphCat;
        $result .= wf_AjaxLink($defUrl, $icon . ' ' . $title, self::AJ_CONTAINER, false, 'ubButton');
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

    /**
     * Renders deffered chart request data
     *
     * @return void
     */
    public function catchDefferedCallback() {
        $result = '';
        $notice = __('Something went wrong') . ': ';
        if ($this->ondemandFlag) {
            if (ubRouting::checkGet(array(self::ROUTE_AJUSER, self::ROUTE_AJCAT))) {

                $userLogin = ubRouting::get(self::ROUTE_AJUSER, 'mres');
                $chartCat = ubRouting::get(self::ROUTE_AJCAT, 'mres');

                if ($userLogin and $chartCat) {
                    $cachedCharts = $this->cache->get(self::KEY_GRAPH, $this->cachingTimeout);
                    if (!empty($cachedCharts)) {
                        if (isset($cachedCharts[$userLogin])) {
                            $userCache = $cachedCharts[$userLogin];
                            if (isset($userCache[$chartCat])) {
                                $styling = 'width:1540px; height:810px; border:0px solid;';
                                $chartBody = wf_tag('div', false, '', 'style="' . $styling . '"') . $userCache[$chartCat]['body'] . wf_tag('div', true);
                                $result .= wf_modalOpenedAuto($userCache[$chartCat]['title'], $chartBody);
                            } else {
                                $result .= $this->messages->getStyledMessage($notice . __('no cached cat') . ' [' . $chartCat . ']', 'error');
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage($notice . __('No user charts'), 'error');
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage($notice . __('Empty charts cache'), 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage($notice . __('Empty params'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage($notice . __('Missed params'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage($notice . __('Disabled'), 'error');
        }
        die($result);
    }

    //TODO: refactor following two methods of global traffic report. They was backported as is.

    /**
     * Renders the global traffic report.
     *
     * This method retrieves traffic data for each traffic class and generates a report.
     * It calculates the total traffic for each class by summing the download and upload traffic values from the 'users' table.
     * If the traffic class is 0, it also includes additional traffic data from the 'ishimura' table and the 'ophanim' flow.
     *
     * @return string
     */
    public function renderTrafficReport() {
        $ishimuraOption = MultiGen::OPTION_ISHIMURA;
        $ishimuraTable = MultiGen::NAS_ISHIMURA;

        $allclasses = zb_DirectionsGetAll();
        $classtraff = array();
        $traffCells = wf_TableCell(__('Traffic classes'), '20%');
        $traffCells .= wf_TableCell(__('Traffic'), '20%');
        $traffCells .= wf_TableCell(__('Traffic classes'));
        $traffRows = wf_TableRow($traffCells, 'row1');

        if (!empty($allclasses)) {
            foreach ($allclasses as $eachclass) {
                $d_name = 'D' . $eachclass['rulenumber'];
                $u_name = 'U' . $eachclass['rulenumber'];
                $query_d = "SELECT SUM(`" . $d_name . "`) FROM `users`";
                $query_u = "SELECT SUM(`" . $u_name . "`) FROM `users`";
                $classdown = simple_query($query_d);
                $classdown = $classdown['SUM(`' . $d_name . '`)'];
                $classup = simple_query($query_u);
                $classup = $classup['SUM(`' . $u_name . '`)'];
                $classtraff[$eachclass['rulename']] = $classdown + $classup;

                //Yep, no traffic classes at all. Just internet accounting here.
                if ($eachclass['rulenumber'] == 0) {
                    //ishimura data
                    if ($this->altCfg[$ishimuraOption]) {
                        $query_hideki = "SELECT SUM(`D0`) as `downloaded`, SUM(`U0`) as `uploaded` from `" . $ishimuraTable . "` WHERE  `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
                        $dataHideki = simple_query($query_hideki);
                        if (isset($classtraff[$eachclass['rulename']])) {
                            @$classtraff[$eachclass['rulename']] += $dataHideki['downloaded'] + $dataHideki['uploaded'];
                        } else {
                            $classtraff[$eachclass['rulename']] = $dataHideki['downloaded'] + $dataHideki['uploaded'];
                        }
                    }

                    //or ophanim flow may be?
                    if ($this->altCfg[OphanimFlow::OPTION_ENABLED]) {
                        $ophanim = new OphanimFlow();
                        $ophTraff = $ophanim->getAllUsersAggrTraff();
                        if (!empty($ophTraff)) {
                            foreach ($ophTraff as $io => $each) {
                                $classtraff[$eachclass['rulename']] += $each;
                            }
                        }
                    }
                }
            }

            if (!empty($classtraff)) {
                $total = max($classtraff);
                foreach ($classtraff as $name => $count) {
                    $traffCells = wf_TableCell($name);
                    $traffCells .= wf_TableCell(stg_convert_size($count), '', '', 'sorttable_customkey="' . $count . '"');
                    $traffCells .= wf_TableCell(web_bar($count, $total), '', '', 'sorttable_customkey="' . $count . '"');
                    $traffRows .= wf_TableRow($traffCells, 'row3');
                }
            }
        }
        $result = wf_TableBody($traffRows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Renders the traffic report NAS charts.
     *
     * This method retrieves the NAS list with bandwidthd set up and generates charts for each NAS.
     * The charts include daily, weekly, monthly, and yearly traffic data.
     *
     * @return string
     */
    public function renderTrafficReportNasCharts() {

        // Get NAS list with bandwidth setted up:
        $query = 'SELECT * FROM `nas` WHERE `bandw` != "" GROUP by `bandw`';
        $result = simple_queryall($query);

        // Check presence of any entry:
        if (!empty($result)) {
            $graphRows = null;

            foreach ($result as $nas) {
                $bwd = $nas['bandw'];
                switch ($nas['nastype']) {
                    case 'local':
                    case 'radius':
                    case 'other':                        
                    case 'rscriptd':
                        //normal bandwidthd
                        if (!ispos($bwd, 'mlgmths') and !ispos($bwd, 'mlgmtppp') and !ispos($bwd, 'mlgmtdhcp')) {
                            // Extention:
                            $ext = '.png';
                            // Modals:
                            $width = 940;
                            $height = 666;

                            // Links:
                            $d_day = $bwd . 'Total-1-R' . $ext;
                            $d_week = $bwd . 'Total-2-R' . $ext;
                            $d_month = $bwd . 'Total-3-R' . $ext;
                            $d_year = $bwd . 'Total-4-R' . $ext;
                            $u_day = $bwd . 'Total-1-S' . $ext;
                            $u_week = $bwd . 'Total-2-S' . $ext;
                            $u_month = $bwd . 'Total-3-S' . $ext;
                            $u_year = $bwd . 'Total-4-S' . $ext;

                            //OphanimFlow graphs
                            if (ispos($bwd, 'OphanimFlow') or ispos($bwd, 'of/')) {
                                $height = "'auto'";
                                $width = "'auto'";

                                $custParams = '';
                                $customDimensions = $this->getOphCustomDimensions(true);
                                if ($customDimensions) {
                                    $custParams = $this->getOphCustomDimensions(false); //as string
                                }
                                $d_day = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=day' . $custParams;
                                $d_week = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=week' . $custParams;
                                $d_month = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=month' . $custParams;
                                $d_year = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=year' . $custParams;
                                $u_day = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=day' . $custParams;
                                $u_week = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=week' . $custParams;
                                $u_month = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=month' . $custParams;
                                $u_year = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=year' . $custParams;

                                $daygraph = __('Downloaded') .  wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($d_day),'','100%') . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($u_day),'','100%');
                                $weekgraph = __('Downloaded') .  wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($d_week),'','100%') . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($u_week),'','100%');
                                $monthgraph = __('Downloaded') .  wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($d_month),'','100%') . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($u_month),'','100%');
                                $yeargraph = __('Downloaded') . wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($d_year),'','100%') . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img_sized(zb_BandwidthdImgLink($u_year),'','100%');
                                $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
    
                                //100% width container for auto windows on OphanimFlow
                                $daygraph = wf_tag('div', false, '', 'style="width:100%;"') . $daygraph . wf_tag('div', true);
                                $weekgraph = wf_tag('div', false, '', 'style="width:100%;"') . $weekgraph . wf_tag('div', true);
                                $monthgraph = wf_tag('div', false, '', 'style="width:100%;"') . $monthgraph . wf_tag('div', true);
                                $yeargraph = wf_tag('div', false, '', 'style="width:100%;"') . $yeargraph . wf_tag('div', true);
    
                            } else {
                                $daygraph = __('Downloaded') .  wf_tag('br') . wf_img(zb_BandwidthdImgLink($d_day)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_day));
                                $weekgraph = __('Downloaded') .  wf_tag('br') . wf_img(zb_BandwidthdImgLink($d_week)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_week));
                                $monthgraph = __('Downloaded') .  wf_tag('br') . wf_img(zb_BandwidthdImgLink($d_month)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_month));
                                $yeargraph = __('Downloaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($d_year)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_year));
                                $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
                            }

                        } else {
                            //Multigen Mikrotik hotspot
                            $bwd = str_replace('mlgmths', 'graphs/iface/bridge', $bwd);
                            $bwd = str_replace('mlgmtppp', 'graphs/iface/bridge', $bwd);
                            $bwd = str_replace('mlgmtdhcp', 'graphs/iface/bridge', $bwd);

                            $ext = '.gif';
                            $daily = $bwd . '/daily' . $ext;
                            $weekly = $bwd . '/weekly' . $ext;
                            $monthly = $bwd . '/monthly' . $ext;
                            $yearly = $bwd . '/yearly' . $ext;

                            // Modals:
                            $width = 530;
                            $height = 250;
                            $daygraph = wf_img(zb_BandwidthdImgLink($daily));
                            $weekgraph = wf_img(zb_BandwidthdImgLink($weekly));
                            $monthgraph = wf_img(zb_BandwidthdImgLink($monthly));
                            $yeargraph = wf_img(zb_BandwidthdImgLink($yearly));
                            $graphLegend = '';
                        }
                        break;
                    case 'mikrotik':
                        if (!ispos($bwd, 'pppoe')) {
                            $options = zb_NasOptionsGet($nas['id']);
                            if (!empty($options['graph_interface'])) {
                                // Extention:
                                $ext = '.gif';

                                // Links:
                                $daily = $bwd . '/../iface/' . $options['graph_interface'] . '/daily' . $ext;
                                $weekly = $bwd . '/../iface/' . $options['graph_interface'] . '/weekly' . $ext;
                                $monthly = $bwd . '/../iface/' . $options['graph_interface'] . '/monthly' . $ext;
                                $yearly = $bwd . '/../iface/' . $options['graph_interface'] . '/yearly' . $ext;

                                // Modals:
                                $width = 530;
                                $height = 230;
                                $daygraph = wf_img($daily);
                                $weekgraph = wf_img($weekly);
                                $monthgraph = wf_img($monthly);
                                $yeargraph = wf_img($yearly);
                                $graphLegend = '';
                                break;
                            } else {
                                show_error(__('For NAS') . ' `' . $nas['nasname'] . '` ' . __('was not set correct graph interface'));
                            }
                        } else {
                            $width = 530;
                            $height = 230;
                            $daygraph = '';
                            $weekgraph = '';
                            $monthgraph = '';
                            $yeargraph = '';
                            $graphLegend = '';
                        }
                }

                if (!ispos($bwd, 'OphanimFlow') and !ispos($bwd, 'of/')) {
                    $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
                } else {
                    $graphLegend = '';
                }

                // Buttons:
                $gday = wf_modal(__('Graph by day'), __('Graph by day'), $daygraph . $graphLegend, '', $width, $height);
                $gweek = wf_modal(__('Graph by week'), __('Graph by week'), $weekgraph . $graphLegend, '', $width, $height);
                $gmonth = wf_modal(__('Graph by month'), __('Graph by month'), $monthgraph . $graphLegend, '', $width, $height);
                $gyear = wf_modal(__('Graph by year'), __('Graph by year'), $yeargraph . $graphLegend, '', $width, $height);

                // Put buttons to table row:
                $graphCells = wf_TableCell($nas['nasname'], '', 'row2');
                $graphCells .= wf_TableCell($gday);
                $graphCells .= wf_TableCell($gweek);
                $graphCells .= wf_TableCell($gmonth);
                $graphCells .= wf_TableCell($gyear);
                $graphRows .= wf_TableRow($graphCells, 'row3');
            }

            $result = wf_TableBody($graphRows, '100%', 0, '');
        } else {
            $result = '';
        }
        return ($result);
    }
}
