<?php

/**
 * MikroTik/UBNT signal monitoring class
 */
class MTsigmon {

    /**
     * User login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * User assigned switch ID
     *
     * @var array
     */
    protected $userSwitch = '';

    /**
     * Data DEVICE id and his array mac data
     *
     * @var array
     */
    protected $deviceIdUsersMac = array();

    /**
     * All users MAC
     *
     * @var array
     */
    protected $allUsermacs = array();

    /**
     * All users Data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * All available MT devices
     *
     * @var array
     */
    protected $allMTDevices = array();

    /**
     * OLT devices snmp data as id=>snmp data array
     *
     * @var array
     */
    protected $allMTSnmp = array();

    /**
     * UbillingCache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Comments caching time
     *
     * @var int
     */
    protected $cacheTime = 2592000; //month by default

    /**
     * Contains system mussages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    const URL_ME = '?module=mtsigmon';
    const CACHE_PREFIX = 'MTSIGMON_';

    public function __construct() {
        $this->LoadUsersData();
        $this->initCache();
        if (wf_CheckGet(array('username'))) {
            $this->initLogin(vf($_GET['username']));
        }
        $this->getMTDevices();
        $this->initSNMP();
    }

    /**
     * Creates single instance of SNMPHelper object
     * 
     * @return void
     */
    protected function initSNMP() {
        $this->snmp = new SNMPHelper();
    }

    /**
     * If get login set $userLogin
     * 
     * @return void
     */
    protected function initLogin($login) {
        $this->userLogin = $login;
        $this->getMTidByUserMac();
    }

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * If get login set $userSwitch
     * 
     * @return void
     */
    protected function getMTidByUserMac() {
        $usermac = strtolower($this->allUsermacs[$this->userLogin]);
        $MT_fdb_arr = $this->cache->get(self::CACHE_PREFIX . 'MTID_UMAC', $this->cacheTime);
        if (!empty($MT_fdb_arr) and isset($usermac)) {
            foreach ($MT_fdb_arr as $mtid => $fdb_arr) {
                if (in_array($usermac, $fdb_arr)) {
                    $this->userSwitch = $mtid;
                    break;
                }
            }
        }
    }

    /**
     * Returns array of monitored MikroTik devices with MTSIGMON label and enabled SNMP
     * 
     * @return array
     */
    protected function getMTDevices() {
        $query_where = ($this->userLogin and ! empty($this->userSwitch)) ? "AND `id` ='" . $this->userSwitch . "'" : '';
        $query = "SELECT `id`,`ip`,`location`,`snmp` from `switches` WHERE `desc` LIKE '%MTSIGMON%'" . $query_where;
        $alldevices = simple_queryall($query);
        if (!empty($alldevices)) {
            foreach ($alldevices as $io => $each) {
                $this->allMTDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];
                if (!empty($each['snmp'])) {
                    $this->allMTSnmp[$each['id']]['ip'] = $each['ip'];
                    $this->allMTSnmp[$each['id']]['community'] = $each['snmp'];
                }
            }
        }
    }

    /**
     * Load user data, mac, adress
     * 
     * @return array
     */
    protected function LoadUsersData() {
        $this->allUsermacs = zb_UserGetAllMACs();
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Performs available MT devices polling. Use only in remote API.
     * 
     * @param bool $quiet
     * 
     * @return void
     */
    public function MTDevicesPolling($quiet = false) {
        if (!empty($this->allMTDevices)) {
            foreach ($this->allMTDevices as $mtid => $each) {
                if (!$quiet) {
                    print('POLLING:' . $mtid . ' ' . $each . "\n");
                }
                $this->deviceQuery($mtid);
            }
            // Set cache for Device fdb table
            if (empty($this->userLogin) or ( !empty($this->userLogin) and empty($this->userSwitch))) {
                $this->cache->set(self::CACHE_PREFIX . 'MTID_UMAC', $this->deviceIdUsersMac, $this->cacheTime);
                $this->cache->set(self::CACHE_PREFIX . 'DATE', date("Y-m-d H:i:s"), $this->cacheTime);
            }
        }
    }

    /**
     * Returns array of MAC=>Signal data for some MikroTik/UBNT device
     * 
     * @param string $ip
     * @param string $community
     * @return array
     */
    protected function deviceQuery($mtid) {
        if (isset($this->allMTSnmp[$mtid]['community'])) {
            $ip = $this->allMTSnmp[$mtid]['ip'];
            $community = $this->allMTSnmp[$mtid]['community'];
            $oid = '.1.3.6.1.4.1.14988.1.1.1.2.1.3';    // - RX Signal Strength
            $oid2 = '.1.3.6.1.4.1.14988.1.1.1.2.1.19';  // - TX Signal Strength
            $mask_mac = false;
            $ubnt_shift = 0;
            $result = array();
            $rawsnmp = array();
            $rawsnmp2 = array();
            $result_fdb = array();

            $this->snmp->setBackground(false);
            $this->snmp->setMode('native');
            $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);
            $tmpSnmp2 = $this->snmp->walk($ip, $community, $oid2, false);

            // Returned string '.1.3.6.1.4.1.14988.1.1.1.2.1.3 = '
            // in AirOS 5.6 and newer
            if ($tmpSnmp === "$oid = ") {
                $oid = '.1.3.6.1.4.1.41112.1.4.7.1.3.1';
                $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);
                $ubnt_shift = 1;
            }

            if (!empty($tmpSnmp) and ( $tmpSnmp !== "$oid = ")) {
                $explodeData = explodeRows($tmpSnmp);
                if (!empty($explodeData)) {
                    foreach ($explodeData as $io => $each) {
                        $explodeRow = explode(' = ', $each);
                        if (isset($explodeRow[1])) {
                            $rawsnmp[$explodeRow[0]] = $explodeRow[1];
                        }
                    }
                }
            }

            if (!empty($tmpSnmp2) and ( $tmpSnmp2 !== "$oid2 = ")) {
                $explodeData = explodeRows($tmpSnmp2);
                if (!empty($explodeData)) {
                    foreach ($explodeData as $io => $each) {
                        $explodeRow = explode(' = ', $each);
                        if (isset($explodeRow[1])) {
                            $rawsnmp2[$explodeRow[0]] = $explodeRow[1];
                        }
                    }
                }
            }

            $rssi2 = '';
            $TXoid = '';

            if (!empty($rawsnmp)) {
                if (is_array($rawsnmp)) {
                    foreach ($rawsnmp as $indexOID => $rssi) {
                        $TXoid = (!empty($rawsnmp2)) ? str_replace($oid, $oid2, $indexOID) : '';

                        $oidarray = explode(".", $indexOID);
                        $end_num = sizeof($oidarray) + $ubnt_shift;
                        $mac = '';

                        for ($counter = 2; $counter < 8; $counter++) {
                            $temp = sprintf('%02x', $oidarray[$end_num - $counter]);

                            if (($counter < 5) && $mask_mac)
                                $mac = ":xx$mac";
                            else if ($counter == 7)
                                $mac = "$temp$mac";
                            else
                                $mac = ":$temp.$mac";
                        }

                        $mac = str_replace('.', '', $mac);
                        $mac = trim($mac);
                        $rssi = str_replace('INTEGER:', '', $rssi);
                        $rssi = trim($rssi);

                        if (!empty($TXoid)) {
                            $rssi2 = $rawsnmp2[$TXoid];
                            $rssi2 = str_replace('INTEGER:', '', $rssi2);
                            $rssi2 = trim($rssi2);
                            $rssi2 = ' / ' . $rssi2;
                        }

                        $result[$mac] = $rssi . $rssi2;
                        $result_fdb[] = $mac;
                    }
                }
            }
            if ($this->userLogin and $this->userSwitch) {
                $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
            } else {
                $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
                $this->deviceIdUsersMac[$mtid] = $result_fdb;
            }
        }
    }

    /**
     * Returns default list controls
     * 
     * @return string
     */
    public function controls() {
        // Load only when using web module
        $this->messages = new UbillingMessageHelper();
        $result = '';
        $cache_date = $this->cache->get(self::CACHE_PREFIX . 'DATE', $this->cacheTime);
        if ($this->userLogin) {
            $result.= wf_BackLink('?module=userprofile&username=' . $this->userLogin);
            $result.= wf_Link(self::URL_ME . '&forcepoll=true' . '&username=' . $this->userLogin, wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        } else {
            $result.= wf_Link(self::URL_ME . '&forcepoll=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        }
        if (!empty($cache_date)) {
            $result.= $this->messages->getStyledMessage(__('Cache state at time') . ': ' . wf_tag('b', false) . @$cache_date . wf_tag('b', true), 'info');
        } else {
            $result.= $this->messages->getStyledMessage(__('Devices are not polled yet'), 'warning');
        }
        $result.= wf_delimiter();

        return ($result);
    }

    /**
     * Renders available ONU JQDT list container
     * 
     * @return string
     */
    public function renderMTList() {
        $result = '';
        $columns = array();
        $opts = '"order": [[ 0, "desc" ]]';
        $columns[] = ('Login');
        $columns[] = ('Address');
        $columns[] = ('Real Name');
        $columns[] = ('Tariff');
        $columns[] = ('IP');
        $columns[] = ('MAC');
        $columns[] = __('Signal') . ' (' . __('dBm') . ')';
        if (empty($this->allMTDevices) and ! empty($this->userLogin) and empty($this->userSwitch)) {
            $result.= show_window('', $this->messages->getStyledMessage(__('User MAC not found on devises'), 'warning'));
        } elseif (!empty($this->allMTDevices) and ! empty($this->userLogin) and ! empty($this->userSwitch)) {
            $result .= show_window(wf_img('skins/wifi.png') . ' ' . __(@$this->allMTDevices[$this->userSwitch]), wf_JqDtLoader($columns, '' . self::URL_ME . '&ajaxmt=true&mtid=' . $this->userSwitch . '&username=' . $this->userLogin, false, __('results'), 100, $opts));
        } elseif (!empty($this->allMTDevices) and empty($this->userLogin)) {
            foreach ($this->allMTDevices as $MTId => $eachMT) {
                $MTsigmonData = $this->cache->get(self::CACHE_PREFIX . $MTId, $this->cacheTime);
                if (! empty($MTsigmonData)) {
                    foreach ($MTsigmonData as $eachmac => $eachsig) {
                        if (strpos($eachsig, '/') !== false) {
                            $columns[5] = __('Signal') . ' RX / TX (' . __('dBm') . ')';
                        } else {
                            $columns[5] = __('Signal') . ' (' . __('dBm') . ')';
                        }

                        break;
                    }
                }
                                
                $result .= show_window(wf_img('skins/wifi.png') . ' ' . __(@$eachMT), wf_JqDtLoader($columns, '' . self::URL_ME . '&ajaxmt=true&mtid=' . $MTId . '', false, __('results'), 100, $opts));
            }
        } else {
            $result.= show_window('', $this->messages->getStyledMessage(__('No devices for signal monitoring found'), 'warning'));
        }
        $result.= wf_delimiter();
        return ($result);
    }

    /**
     * Renders MTSIGMON list container
     * 
     * @return string
     */
    public function renderMTsigmonList($MTid) {
        // Get MTSigmon cache gtom stroage by MT id
        $MTsigmonData = $this->cache->get(self::CACHE_PREFIX . $MTid, $this->cacheTime);
        $json = new wf_JqDtHelper();
        if (!empty($MTsigmonData)) {
            $data = array();
            foreach ($MTsigmonData as $eachmac => $eachsig) {
                //signal coloring
                if ($eachsig < -79) {
                    $displaysig = wf_tag('font', false, '', 'color="#900000"') . $eachsig . wf_tag('font', true);
                } elseif ($eachsig > -80 and $eachsig < -74) {
                    $displaysig = wf_tag('font', false, '', 'color="#FF5500"') . $eachsig . wf_tag('font', true);
                } else {
                    $displaysig = wf_tag('font', false, '', 'color="#006600"') . $eachsig . wf_tag('font', true);
                }

                $login = in_array($eachmac, array_map('strtolower', $this->allUsermacs)) ? array_search($eachmac, array_map('strtolower', $this->allUsermacs)) : '';
                //user search highlight
                if ((!empty($this->userLogin)) AND ( $this->userLogin == $login)) {
                    $hlStart = wf_tag('font', false, '', 'color="#0045ac"');
                    $hlEnd = wf_tag('font', true);
                } else {
                    $hlStart = '';
                    $hlEnd = '';
                }
                
                $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$this->allUserData[$login]['login'] . '', false) : '';
                $userRealnames = $login ? @$this->allUserData[$login]['realname'] : '';
                $userTariff = $login ? @$this->allUserData[$login]['Tariff'] : '';
                $userIP = $login ? @$this->allUserData[$login]['ip'] : '';

                $data[] = $userLink;
                $data[] = $hlStart . @$this->allUserData[$login]['fulladress'] . $hlEnd;
                $data[] = $hlStart . $userRealnames . $hlEnd;
                $data[] = $hlStart . $userTariff . $hlEnd;
                $data[] = $hlStart . $userIP . $hlEnd;
                $data[] = $hlStart . $eachmac . $hlEnd;
                $data[] = $displaysig;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

}

?>