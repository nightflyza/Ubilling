<?php

/**
 * Class BGPMon
 *
 * This class is responsible for monitoring BGP peers states. 
 */
class BGPMon {
    /**
     * Contains all switch devices that shall be polled
     *
     * @var array
     */
    protected $allDevices = array();

    /**
     * System messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * system cahching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Caching timeout in seconds
     *
     * @var int
     */
    protected $cacheTimeout = 300;

    /**
     * Stardust process manager instance
     *
     * @var object
     */
    protected $starDust = '';

    /**
     * Peers descriptions database abstraction layer
     *
     * @var object
     */
    protected $peersDb = '';

    /**
     * Contains available peer descriptions as ip=>[name/short]
     *
     * @var array
     */
    protected $allPeerNames = array();

    /**
     * Contains all routers peers stats as id=>stats
     *
     * @var array
     */
    protected $allRoutersStats = array();

    //some predefined stuff
    const MONITOR_MARK = 'BGPMON';
    const TABLE_PEERS = 'bgppeers';
    const KEY_DEVS = 'BGPMON_DEVS';
    const KEY_PEERS = 'BGPMON_PEERS';
    const KEY_STATS = 'BGPMON_STATS';
    const PID_POLLING = 'BGPMON_POLL';
    const URL_ME = '?module=bgpmon';
    const URL_AS_LOOKUP = 'https://bgp.he.net/AS';
    const ROUTE_EDIT_NAMES = 'editpeer';
    const ROUTE_REFRESH = 'rundevspolling';
    const PROUTE_PEER_IP = 'editpeerip';
    const PROUTE_PEER_NAME = 'editpeername';
    const PROUTE_PEER_SHORT = 'editpeershort';

    /**
     * Два обличчя бачу на стіні,
     * Двоє посміхаються мені, 
     */
    public function __construct() {
        $this->initMessages();
        $this->initCache();
        $this->initStarDust();
        $this->initPeersDb();
        $this->loadAllDevices();
        $this->loadAllPeersNames();
    }

    /**
     * Initializes the messages property with an instance of UbillingMessageHelper.
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initializes the cache by creating a new instance of the UbillingCache class.
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Initializes the StarDust process manager object
     *
     * @return void
     */
    protected function initStarDust() {
        $this->starDust = new StarDust(self::PID_POLLING);
    }

    /**
     * Inits peers notes database abstraction layer
     *
     * @return void
     */
    protected function initPeersDb() {
        $this->peersDb = new NyanORM(self::TABLE_PEERS);
    }

    /**
     * Preloads all available peer names
     *
     * @return void
     */
    protected function loadAllPeersNames() {
        $cachedNames = $this->cache->get(self::KEY_PEERS, $this->cacheTimeout);
        if (empty($cachedNames)) {
            $this->allPeerNames = $this->peersDb->getAll('ip');
            $this->cache->set(self::KEY_PEERS, $this->allPeerNames, $this->cacheTimeout);
        } else {
            $this->allPeerNames = $cachedNames;
        }
    }

    /**
     * Loads all devices marked for peers monitoring
     *
     * @return void
     */
    protected function loadAllDevices() {
        $cachedDevs = $this->cache->get(self::KEY_DEVS, $this->cacheTimeout);
        if (empty($cachedDevs)) {
            $allSwitches = zb_SwitchesGetAll();
            if (!empty($allSwitches)) {
                foreach ($allSwitches as $io => $each) {
                    if (!empty($each['snmp']) and ispos($each['desc'], self::MONITOR_MARK)) {
                        $this->allDevices[$each['id']] = $each;
                    }
                }
            }
            $this->cache->set(self::KEY_DEVS, $this->allDevices, $this->cacheTimeout);
        } else {
            $this->allDevices = $cachedDevs;
        }
    }

    /**
     * Polls peer stats data from some device
     *
     * @param string $ip
     * @param string $community
     * 
     * @return array
     */
    protected function pollDevice($ip, $community) {
        $junBgp = new JunBGP($ip, $community);
        $result = $junBgp->getPeersData();
        return ($result);
    }

    /**
     * Performs polling of all monitored devices
     *
     * @return void
     */
    public function pollAllDevsStats() {
        $cachedStats = $this->cache->get(self::KEY_STATS, $this->cacheTimeout);
        if (empty($cachedStats)) {
            if ($this->starDust->notRunning()) {
                $this->starDust->start();

                if (!empty($this->allDevices)) {
                    foreach ($this->allDevices as $io => $each) {
                        $this->allRoutersStats[$each['id']] = $this->pollDevice($each['ip'], $each['snmp']);
                    }
                }
                $this->starDust->stop();
            }
            $this->cache->set(self::KEY_STATS, $this->allRoutersStats, $this->cacheTimeout);
        } else {
            $this->allRoutersStats = $cachedStats;
        }
    }

    /**
     * Flushes the cache by deleting specific cache keys.
     *
     * @return void
     */

    public function flushCache() {
        $this->cache->delete(self::KEY_DEVS);
        $this->cache->delete(self::KEY_PEERS);
        $this->cache->delete(self::KEY_STATS);
    }

    /**
     * Retrieves the label for a given peer IP address.
     *
     * @param string $ip The IP address of the peer.
     * @return string
     */
    protected function getPeerLabel($ip) {
        $result = '';
        if (isset($this->allPeerNames[$ip])) {
            $peerData = $this->allPeerNames[$ip];
            if (!empty($peerData['short'])) {
                $result .= $peerData['short'];
            }

            if (!empty($peerData['short']) and !empty($peerData['name'])) {
                $result .= ' - ';
            }

            if (!empty($peerData['name'])) {
                $result .= $peerData['name'];
            }
        }
        return ($result);
    }


    /**
     * Renders some module controls
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        if (!ubRouting::checkGet(self::ROUTE_EDIT_NAMES)) {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_REFRESH . '=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton') . ' ';
        } else {
            $result .= wf_BackLink(self::URL_ME);
        }

        return ($result);
    }

    /**
     * Renders peer data editing form
     * 
     * @param string $ip
     *
     * @return void
     */
    public function renderPeersEditForm($ip) {
        $result = '';
        $inputs = wf_HiddenInput(self::PROUTE_PEER_IP, $ip);
        $inputs .= wf_TextInput(self::PROUTE_PEER_NAME, __('Description'), @$this->allPeerNames[$ip]['name'], true, 20);
        $inputs .= wf_TextInput(self::PROUTE_PEER_SHORT, __('Short ID'), @$this->allPeerNames[$ip]['short'], true, 5);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Saves peer description/name into database if required
     *
     * @return void
     */
    public function savePeerName() {
        if (ubRouting::checkPost(self::PROUTE_PEER_IP)) {
            $peerIp = ubRouting::post(self::PROUTE_PEER_IP, 'mres');
            $peerName = ubRouting::post(self::PROUTE_PEER_NAME, 'safe');
            $peerShort = ubRouting::post(self::PROUTE_PEER_SHORT, 'gigasafe');


            $this->peersDb->data('ip', $peerIp);
            $this->peersDb->data('name', $peerName);
            $this->peersDb->data('short', $peerShort);

            //updating record
            if (isset($this->allPeerNames[$peerIp])) {
                $recordId = $this->allPeerNames[$peerIp]['id'];
                $this->peersDb->where('id', '=', $recordId);
                $this->peersDb->save();
            } else {
                //or creating new one
                $this->peersDb->create();
            }
            //flushing key
            $this->cache->delete(self::KEY_PEERS);
            log_register('BGPMON SAVE PEER `' . $peerIp . '` SHORT `' . $peerShort . '` NAME `' . $peerName . '`');
        }
    }


    /**
     * Renders peers report. What did you expect?
     *
     * @return string
     */
    public function renderReport() {
        $this->pollAllDevsStats(); //preloading stats data
        $result = '';
        if (!empty($this->allRoutersStats)) {
            foreach ($this->allRoutersStats as $routerId => $eachRouterStats) {
                if (!empty($eachRouterStats)) {
                    $cells = wf_TableCell(__('Neighbor'));
                    $cells .= wf_TableCell(__('AS'));
                    $cells .= wf_TableCell(__('Status'));
                    $cells .= wf_TableCell(__('Connection'));
                    $cells .= wf_TableCell(__('Time'));
                    $cells .= wf_TableCell(__('Notes'));
                    if (cfr('ROOT')) {
                        $cells .= wf_TableCell(__('Actions'));
                    }
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($eachRouterStats as $io => $each) {
                        $stateIcon = ($each['state'] == 6) ? web_bool_led(true) : web_bool_led(false);
                        if ($each['status'] == 1) {
                            $stateIcon = web_yellow_led(__('Disabled'));
                        }
                        $cells = wf_TableCell($each['ip']);
                        $asControl = wf_Link(self::URL_AS_LOOKUP . $each['as'], $each['as'], false, '', 'target="_blank"');
                        $cells .= wf_TableCell($asControl);
                        $cells .= wf_TableCell($stateIcon, '', '', 'sorttable_customkey="' . $each['state'] . '"');
                        $cells .= wf_TableCell(__($each['stateName']));
                        $cells .= wf_TableCell(zb_formatTime($each['timer']), '', '', 'sorttable_customkey="' . $each['timer'] . '"');
                        $cells .= wf_TableCell($this->getPeerLabel($each['ip']));
                        if (cfr('ROOT')) {
                            $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT_NAMES . '=' . $each['ip'], web_edit_icon());
                            $cells .= wf_TableCell($actLinks);
                        }
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    if (sizeof($this->allRoutersStats) > 1) {
                        $result .= '';
                        $result .= wf_tag('b') . @$this->allDevices[$routerId]['ip'] . ' ' . @$this->allDevices[$routerId]['location'] . wf_tag('b', true);
                    }
                    $result .= wf_delimiter(0);
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }


        return ($result);
    }

    /**
     * Returns peer stats of all routers as routerId=>fullPeerStats
     *
     * @return array
     */
    public function getAllPeersStats() {
        $result = array();
        $this->pollAllDevsStats(); //preloading stats data
        if (!empty($this->allRoutersStats)) {
            foreach ($this->allRoutersStats as $routerId => $eachRouterStats) {
                if (!empty($eachRouterStats)) {
                    foreach ($eachRouterStats as $io => $each) {
                        $peerData = array(
                            'name' => '-',
                            'short' => '-'
                        );
                        if (isset($this->allPeerNames[$each['ip']])) {
                            $peerData['name'] = $this->allPeerNames[$each['ip']]['name'];
                            $peerData['short'] = $this->allPeerNames[$each['ip']]['short'];
                        }

                        $result[$routerId][$each['ip']] = $each;
                        $result[$routerId][$each['ip']] += $peerData;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns some alert control. Used in DarkVoid.
     * 
     * @return string
     */
    public function getPeersAlerts() {
        $result = '';
        $allPeersStats = $this->getAllPeersStats();
        $deadCount = 0;
        if (!empty($allPeersStats)) {
            foreach ($allPeersStats as $eachRouterId => $eachRouterStats) {
                if (!empty($eachRouterStats)) {
                    foreach ($eachRouterStats as $io => $each) {
                        if ($each['state'] != 6) {
                            $deadCount++;
                        }
                    }
                }
            }
        }

        if ($deadCount > 0) {
            $result = wf_Link(self::URL_ME, wf_img('skins/bgpmonalert.png', __('BGP problems') . ': ' . $deadCount . ' ' . __('peers is dead')), false, '');
        }
        return ($result);
    }
}
