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

    const URL_ME = '?module=mtsigmon';
    const CACHE_PREFIX = 'MTSIGMON_';

    public function __construct () {
        if (wf_CheckGet(array('username'))) {
            $this->initLogin(vf($_GET['username']));
        }
        $this->getMTDevices();
        $this->initSNMP();
        $this->initCache();
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
        $query = "SELECT `switchid` from `switchportassign` WHERE `login`='" . mysql_real_escape_string($this->userLogin) . "'";
        $this->userSwitch = simple_query($query);
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
     * Returns array of monitored MikroTik devices with MTSIGMON label and enabled SNMP
     * 
     * @return array
     */
    protected function getMTDevices() {
        $query_where = ($this->userLogin) ? "AND `id` ='" . $this->userSwitch['switchid'] . "'" : '';
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
    public function LoadUsersData() {
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
            $oid = '.1.3.6.1.4.1.14988.1.1.1.2.1.3';
            $mask_mac = false;
            $ubnt_shift = 0;
            $result = array();
            $rawsnmp = array();

            //$this->snmp->setBackground(false);
            //$this->snmp->setMode('native');
            $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);

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

            if (!empty($rawsnmp)) {
                if (is_array($rawsnmp)) {
                    foreach ($rawsnmp as $indexOID => $rssi) {
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
                        $result[$mac] = $rssi;
                    }
                }
            }
            if ($this->userLogin) {
                $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
            } else {
                $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
                $this->cache->set(self::CACHE_PREFIX . 'DATE', date("Y-m-d H:i:s"), $this->cacheTime);
            }

        }
    }

    /**
     * Returns default list controls
     * 
     * @return string
     */
    public function controls() {
        $result = '';
        if ($this->userLogin) {
            $result.= wf_BackLink('?module=userprofile&username='. $this->userLogin);
            $result.= wf_Link(self::URL_ME . '&forcepoll=true' . '&username='. $this->userLogin, wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        } else {
            $result.= wf_Link(self::URL_ME . '&forcepoll=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
        }
        $result.=wf_delimiter();
        $result.= __('Cache state at time') . ': ' . @$this->cache->get(self::CACHE_PREFIX . 'DATE', $this->cacheTime);;
        return ($result);
    }

    /**
     * Renders available ONU JQDT list container
     * 
     * @return string
     */
    public function renderMTList() {

        $columns = array();
        $opts = '"order": [[ 0, "desc" ]]';

        $columns[] = ('Address');
        $columns[] = ('Real Name');
        $columns[] = ('Tariff');
        $columns[] = ('IP');
        $columns[] = ('MAC');
        $columns[] = __('Signal') . ' (' . __('dBm') . ')';

        $result = '';
        foreach ($this->allMTDevices as $MTId => $eachMT) {
            $result .= show_window(wf_img('skins/wifi.png') . ' ' . __(@$eachMT), wf_JqDtLoader($columns, '' . self::URL_ME . '&ajaxmt=true&mtid=' . $MTId . '', false, 'MTSigmon', 100, $opts));
        }
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

                $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' (' . @$this->allUserData[$login]['login'] .') ' . @$this->allUserData[$login]['fulladress'], false) : '';
                $userRealnames = $login ? @$this->allUserData[$login]['realname'] : '';
                $userTariff = $login ? @$this->allUserData[$login]['Tariff'] : '';
                $userIP = $login ? @$this->allUserData[$login]['ip'] : '';

                $data[] = $userLink;
                $data[] = $userRealnames;
                $data[] = $userTariff;
                $data[] = $userIP;
                $data[] = $eachmac;
                $data[] = $displaysig;
                $json->addRow($data);
                unset($data);
            }
        }

        print_r ($json->getJson());
    }
}

?>