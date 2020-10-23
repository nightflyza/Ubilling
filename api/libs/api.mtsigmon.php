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
     * All users CPE MAC
     *
     * @var array
     */
    protected $allUserCpeMacs = array();

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

    /**
     * Contains value of MTSIGMON_QUICK_AP_LINKS from alter.ini
     *
     * @var bool
     */
    protected $EnableQuickAPLinks = false;

    /**
     * Contains value of MTSIGMON_CPE_AUTOPOLL from alter.ini
     *
     * @var bool
     */
    protected $EnableCPEAutoPoll = false;

    /**
     * Is WCPE module enabled? Contains value of WIFICPE_ENABLED from alter.ini
     *
     * @var bool
     */
    protected $WCPEEnabled = false;

    /**
     * Placeholder for UbillingConfig object instance
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Sorting order of APs in lists and dropdowns
     * Possible values: id, ip, location
     *
     * @var string
     */
    protected $apSortOrder = "id";

    /**
     * Placeholder for SWITCH_GROUPS_ENABLED alter.ini option
     *
     * @var bool
     */
    protected $switchGroupsEnabled = false;

    /**
     * Placeholder for SIGMON_GROUP_AP_BY_SWITCHGROUP_WITH_TABS alter.ini option
     *
     * @var bool
     */
    protected $groupAPsBySwitchGroupWithTabs = false;

    /**
     * Contains array which represents sigmon devices and their groups, like: mtId => switchGroup
     *
     * @var array
     */
    protected $allMTSwitchGroups = array();

    /**
     * Contains groups in which only sigmon devices are present
     *
     * @var array
     */
    protected $existingMTSwitchGroups = array();

    const URL_ME = '?module=mtsigmon';
    const CACHE_PREFIX = 'MTSIGMON_';
    const CPE_SIG_PATH = 'content/documents/wifi_cpe_sig_hist/';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->EnableQuickAPLinks   = $this->ubConfig->getAlterParam('MTSIGMON_QUICK_AP_LINKS');
        $this->EnableCPEAutoPoll    = $this->ubConfig->getAlterParam('MTSIGMON_CPE_AUTOPOLL');
        $this->WCPEEnabled          = $this->ubConfig->getAlterParam('WIFICPE_ENABLED');
        $this->apSortOrder          = ($this->ubConfig->getAlterParam('SIGMON_WCPE_AP_LIST_SORT')) ? $this->ubConfig->getAlterParam('SIGMON_WCPE_AP_LIST_SORT') : 'id';
        $this->switchGroupsEnabled  = $this->ubConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
        $this->groupAPsBySwitchGroupWithTabs = $this->ubConfig->getAlterParam('SIGMON_GROUP_AP_BY_SWITCHGROUP_WITH_TABS');

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
        $userCpeMac = (isset($this->allUserCpeMacs[$this->userLogin])) ? strtolower($this->allUserCpeMacs[$this->userLogin]) : 'F0:14:78:87:41:0F';
        $MT_fdb_arr = $this->cache->get(self::CACHE_PREFIX . 'MTID_UMAC', $this->cacheTime);
        if (!empty($MT_fdb_arr) and isset($usermac)) {
            foreach ($MT_fdb_arr as $mtid => $fdb_arr) {
                if (in_array($usermac, $fdb_arr) or in_array($userCpeMac, $fdb_arr)) {
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
        $query_where = ($this->userLogin and !empty($this->userSwitch)) ? " AND `id` = '" . $this->userSwitch . "' " : '';

        if ($this->switchGroupsEnabled and $this->groupAPsBySwitchGroupWithTabs) {
            $query = "SELECT `switches`.`id`, `switches`.`ip`, `switches`.`location`, `switches`.`snmp`, COALESCE(`swgrp`.`groupname`, '') AS groupname, `swgrp`.`groupdescr`
                        FROM `switches`
                          LEFT JOIN (SELECT `switch_groups_relations`.`switch_id`, `switch_groups`.`groupname`, `switch_groups`.`groupdescr` 
                                        FROM `switch_groups_relations`
                                          LEFT JOIN `switch_groups` 
                                            ON `switch_groups_relations`.`sw_group_id` = `switch_groups`.`id`) AS swgrp
                            ON `switches`.`id` = `swgrp`.`switch_id` 
                        WHERE `desc` LIKE '%MTSIGMON%'" . $query_where;
        } else {
            $query = "SELECT `id`, `ip`, `location`, `snmp` FROM `switches` WHERE `desc` LIKE '%MTSIGMON%'" . $query_where;
        }

        if ($this->switchGroupsEnabled and $this->groupAPsBySwitchGroupWithTabs) {
            switch ($this->apSortOrder) {
                case "ip":
                    $query .= ' GROUP BY `groupname`, `ip`';
                    break;

                case "location":
                    $query .= ' GROUP BY `groupname`, `location`';
            }
        } else {
            switch ($this->apSortOrder) {
                case "ip":
                    $query.= ' ORDER BY `ip`';
                    break;

                case "location":
                    $query.= ' ORDER BY `location`';
            }
        }

        $alldevices = simple_queryall($query);

        if (!empty($alldevices)) {
            foreach ($alldevices as $io => $each) {
                $this->allMTDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];

                if (!empty($each['snmp'])) {
                    $this->allMTSnmp[$each['id']]['ip'] = $each['ip'];
                    $this->allMTSnmp[$each['id']]['community'] = $each['snmp'];
                }

                if ($this->switchGroupsEnabled and $this->groupAPsBySwitchGroupWithTabs) {
                    $this->allMTSwitchGroups[$each['id']]['groupname'] = $each['groupname'];
                    $this->allMTSwitchGroups[$each['id']]['groupdescr'] = $each['groupdescr'];

                    if (!in_array($each['groupname'], $this->existingMTSwitchGroups)) {
                        $this->existingMTSwitchGroups[] = $each['groupname'];
                    }
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
        if ($this->WCPEEnabled) {
            $this->LoadUsersCpeMACs();
        }
    }

    /**
     * Load user data, mac, adress
     * 
     * @return array
     */
    protected function LoadUsersCpeMACs() {
        $query = "SELECT `login`,`mac` FROM `wcpeusers` INNER JOIN (SELECT `id`,`mac`,`bridge` FROM `wcpedevices`) AS wcd ON (`wcpeusers`.`cpeid`=`wcd`.`id`) WHERE `bridge` = '1'";
        $usersCpeMacs = simple_queryall($query);
        if (!empty($usersCpeMacs)) {
            foreach ($usersCpeMacs as $io => $each) {
                $this->allUserCpeMacs[$each['login']] = $each['mac'];
            }
        }
    }

    /**
     * Performs available MT devices polling. Use only in remote API.
     * 
     * @param bool $quiet
     * @param string $apid
     *
     * @return void
     */
    public function MTDevicesPolling($quiet = false, $apid = '') {
        if (!empty($this->allMTDevices)) {
            if (empty($apid)) {
                foreach ($this->allMTDevices as $mtid => $each) {
                    if (!$quiet) {
                        print('POLLING:' . $mtid . ' ' . $each . "\n");
                    }

                    $this->deviceQuery($mtid);
                }
            } else {
                $this->deviceQuery($apid);
            }

            // Set cache for Device fdb table
            if (empty($this->userLogin) or ( !empty($this->userLogin) and empty($this->userSwitch))) {
                $this->cache->set(self::CACHE_PREFIX . 'MTID_UMAC', $this->deviceIdUsersMac, $this->cacheTime);
                $this->cache->set(self::CACHE_PREFIX . 'DATE', date("Y-m-d H:i:s"), $this->cacheTime);
            }
        }

        if ($this->EnableCPEAutoPoll && $this->WCPEEnabled) {
            $WCPE = new WifiCPE();
            $AllCPEs = $WCPE->getAllCPE();

            if ( !empty($AllCPEs) ) {
                foreach ($AllCPEs as $io => $each) {
                    $this->deviceQuery(0, $each['ip'], $each['mac'], $each['snmp']);
                }
            }
        }
    }

    /**
     * Performs getting string representation of AP/CPE devices signal levels from cache.
     * Can re-poll the devices, before taking data from cache, to get the most fresh values.
     * IP and SNMP community for AP is taken from APs dictionary.
     * For an individual CPE - IP and SNMP community must be given as a parameter
     *
     * @param string $WiFiCPEMAC
     * @param string $WiFiAPID
     * @param string $WiFiCPEIP
     * @param string $WiFiCPECommunity
     * @param bool $GetFromAP
     * @param bool $Repoll
     *
     * @return array
    */
    public function getCPESignalData($WiFiCPEMAC, $WiFiAPID = '', $WiFiCPEIP = '', $WiFiCPECommunity = 'public', $GetFromAP = false, $Repoll = false) {
        if ( empty($WiFiCPEMAC) or (empty($WiFiAPID) and empty($WiFiCPEIP)) ) {
            return array();
        }

        $BillCfg = $this->ubConfig->getBilling();

        if ($GetFromAP) {
            $HistoryFile = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_AP';

            if ($Repoll and !empty($WiFiAPID)) { $this->MTDevicesPolling(false, $WiFiAPID); }

        } else {
            $HistoryFile = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_CPE';

            if ($Repoll and !empty($WiFiCPEIP)) { $this->deviceQuery(0, $WiFiCPEIP, $WiFiCPEMAC, $WiFiCPECommunity); }
        }

        if (file_exists($HistoryFile)) {
            //$GREPString = ( empty($GREPBy) ) ? '' : ' | ' . $BillCfg['GREP'] . ' ' . $GREPBy;
            //$RawDataLastLine = strstr(shell_exec($GetDataCmd), "\n", true);

            $GetDataCmd = $BillCfg['TAIL'] . ' -n 1 ' . $HistoryFile;
            $RawDataLastLine = shell_exec($GetDataCmd);
            $LastLineArray = explode(',', trim($RawDataLastLine));

            $LastPollDate = $LastLineArray[0];
            $SignalRX = $LastLineArray[1];

            if (isset($LastLineArray[2]) and !empty($LastLineArray[2])) {
                $SignalCheck = (($SignalRX > $LastLineArray[2]) ? $LastLineArray[2] : $SignalRX);
                $SignalTX = ' / ' . $LastLineArray[2];
            } else {
                $SignalCheck = $SignalRX;
                $SignalTX = '';
            }

            $SignalLevel = $SignalRX . $SignalTX;

            if ($SignalCheck < -79) {
                $SignalLevel = wf_tag('font', false, '', 'color="ab0000" style="font-weight: 700"') . $SignalLevel . wf_tag('font', true);
            } elseif ($SignalCheck > -80 and $SignalCheck < -74) {
                $SignalLevel = wf_tag('font', false, '', 'color="#FF5500" style="font-weight: 700"') . $SignalLevel . wf_tag('font', true);
            } else {
                $SignalLevel = wf_tag('font', false, '', 'color="#005502" style="font-weight: 700"') . $SignalLevel . wf_tag('font', true);
            }

            //return ( wf_CheckGet(array('cpeMAC')) ) ? array("LastPollDate" => $LastPollDate, "SignalLevel" => $SignalLevel) : array($LastPollDate, $SignalLevel);
            return ( $Repoll ) ? array("LastPollDate" => $LastPollDate, "SignalLevel" => $SignalLevel) : array($LastPollDate, $SignalLevel);
        }
    }

    /**
     * Renders signal graphs for specified CPE if there are some history data already
     * Returns ready-to-use piece of HTML
     *
     * @param string $WiFiCPEMAC
     * @param bool $FromAP
     * @param bool $ShowTitle
     * @param bool $ShowXLabel
     * @param bool $ShowYLabel
     * @param bool $ShowRangeSelector
     * @return string
     */
    public function renderSignalGraphs ($WiFiCPEMAC, $FromAP = false, $ShowTitle = false, $ShowXLabel = false, $ShowYLabel = false, $ShowRangeSelector = false) {
        $result = '';
        $BillCfg = $this->ubConfig->getBilling();

        if ($FromAP) {
            // get signal data on AP for this CPE
            $HistoryFile        = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_AP';
            $HistoryFileMonth   = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_AP_month';
        } else {
            // get signal data for this CPE itself
            $HistoryFile = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_CPE';
        }

        if (file_exists($HistoryFile )) {
            $curdate = curdate();
            $curmonth = curmonth() . '-';
            $getMonthDataCmd = $BillCfg['CAT'] . ' ' . $HistoryFile . ' | ' . $BillCfg['GREP'] . ' ' . $curmonth;
            $rawData = shell_exec($getMonthDataCmd);
            $result .= wf_delimiter();

            $todaySignal = '';
            if (!empty($rawData)) {
                $todayTmp = explodeRows($rawData);
                if (!empty($todayTmp)) {
                    foreach ($todayTmp as $io => $each) {
                        if (ispos($each, $curdate)) {
                            $todaySignal .= $each . "\n";
                        }
                    }
                }
            }

            $GraphTitle  = ($ShowTitle)  ? __('Today') : '';
            $GraphXLabel = ($ShowXLabel) ? __('Time') : '';
            $GraphYLabel = ($ShowYLabel) ? __('Signal') : '';
            $result .= wf_Graph($todaySignal, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
            $result .= wf_delimiter(2);

            //current month signal levels
            $monthSignal = '';
            $curmonth = curmonth();
            if (!empty($rawData)) {
                $monthTmp = explodeRows($rawData);
                if (!empty($monthTmp)) {
                    foreach ($monthTmp as $io => $each) {
                        if (ispos($each, $curmonth)) {
                            $monthSignal .= $each . "\n";
                        }
                    }
                }
            }

            $GraphTitle  = ($ShowTitle)  ? __('Monthly graph') : '';
            $GraphXLabel = ($ShowXLabel) ? __('Date') : '';
            if ($FromAP) {
                file_put_contents($HistoryFileMonth, $monthSignal);
                $result .= wf_GraphCSV($HistoryFileMonth, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
            } else {
                $result .= wf_Graph($monthSignal, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
            }

            $result .= wf_delimiter(2);

            //all time signal history
            $GraphTitle  = ($ShowTitle)  ? __('All time graph') : '';
            $result .= wf_GraphCSV($HistoryFile, '800', '300', false, $GraphTitle, $GraphXLabel, $GraphYLabel, $ShowRangeSelector);
            $result .= wf_delimiter();
        }

        return $result;
    }

/*
 * Common for all
 * .1.3.6.1.2.1.1.1.0                   - AP sys description
 * .1.3.6.1.2.1.1.3.0                   - AP uptime
 * .1.3.6.1.2.1.1.5.0                   - AP sys name
 *
 * .1.3.6.1.2.1.2.2.1.6                 - AP interaces MACs list - too many afforts needed to get correct wireless MAC
 *                                        cause we first need to determine the wireless interface correctly,
 *                                        then get it's index and only then we can get APs MAC for sure.
*                                         But on different devices there are different approaches to get the wireless interface correctly:
 *                                          on Mikrotiks we can not rely on iface description, but can rely on iface type;
 *                                          on other devices we can supposedly rely on iface description, but not 100%
 *
 *
 * Mikrotik *
 * .1.3.6.1.2.1.25.3.3.1.2.1            - CPU load average
 * .1.3.6.1.4.1.14988.1.1.1.3.1.4       - AP ssid
 * .1.3.6.1.4.1.14988.1.1.1.3.1.7       - AP freq
 * .1.3.6.1.4.1.14988.1.1.1.3.1.8       - AP band
 * .1.3.6.1.2.1.2.2.1.6.1               - AP wireless MAC
 *
 *
 * Ubiquity b/g/n AirOS version >= 5.6
 * .1.3.6.1.4.1.41112.1.4.1.1.4       - AP freq
 * .1.3.6.1.4.1.41112.1.4.5.1.2       - AP ssid
 * .1.3.6.1.4.1.41112.1.4.5.1.14      - AP channel width
 * .1.2.840.10036.1.1.1.1.5           - AP wireless MAC
 *
 *
 * Ubiquity b/g
 * only common data can be got
 *
 *
 * Ligowave DLB
 * .1.3.6.1.4.1.32750.3.10.1.2.1.1.1    - AP wireless MAC
 * .1.3.6.1.4.1.32750.3.10.1.2.1.1.4    - AP ssid
 * .1.3.6.1.4.1.32750.3.10.1.2.1.1.6    - AP freq
 * .1.3.6.1.4.1.32750.3.10.1.2.1.1.8    - AP channel width
 *
 * Deliberant APC Series
 * .1.3.6.1.4.1.32761.3.5.1.2.1.1.4   - AP ssid
 * .1.3.6.1.4.1.32761.3.5.1.2.1.1.7   - AP freq
 * .1.3.6.1.4.1.32761.3.5.1.2.1.1.9   - AP channel width
 *
 * .1.3.6.1.4.1.32761.3.5.1.2.1.1.14  - CPE signal level, but need wireless iface index...
 */


    /**
     * Gets essential system info about AP via SNMP and returns it as HTML table or array
     *
     * @param $APID
     * @param bool $ReturnHTML
     * @param bool $ReturnInSpoiler
     * @param bool $SpoilerClosed
     *
     * @return array|string
     */
    public function getAPEssentialData($APID, $ReturnHTML = false, $ReturnInSpoiler = false, $SpoilerClosed = false) {
        if ( isset($this->allMTSnmp[$APID]['community']) ) {
            $this->snmp->setMode('native');
            $APIP = $this->allMTSnmp[$APID]['ip'];
            $APCommunity = $this->allMTSnmp[$APID]['community'];

            $APSysDescr     = '';
            $APUptime       = '';
            $APSysName      = '';
            $APSSID         = '';
            $APFreq         = '';
            $APBandChWidth  = '';
            $MTikCPULoad    = '';
            $APMAC          = '';
            $SNMPDataArray  = array();

        //getting common data for all devices
            // sys description
            $tmpOID = '.1.3.6.1.2.1.1.1.0';
            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
            $APSysDescr = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

            // uptime
            $tmpOID = '.1.3.6.1.2.1.1.3.0';
            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
            $APUptime = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ')') + 1) );

            // sys name
            $tmpOID = '.1.3.6.1.2.1.1.5.0';
            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
            $APSysName = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );


        // suppose it's Mikrotik
            $tmpOID = '.1.3.6.1.4.1.14988.1.1.1.3.1.4';
            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);

            if ( !empty($tmpSNMP) && $tmpSNMP !== "$tmpOID = " ) {
                $APSSID = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                $tmpOID = '.1.3.6.1.4.1.14988.1.1.1.3.1.7';
                $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                $APFreq = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                $tmpOID = '.1.3.6.1.4.1.14988.1.1.1.3.1.8';
                $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                $APBandChWidth = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                $tmpOID = '.1.3.6.1.2.1.25.3.3.1.2.1';
                $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                $MTikCPULoad = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                // Device MAC for Mikrotik
                $tmpOID = '.1.3.6.1.2.1.2.2.1.6.1';
                $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                $APMAC = (empty($tmpSNMP) && $tmpSNMP === "$tmpOID = ") ? '' : $this->getMACFromSNMPStr($tmpSNMP);
            } else {
        // now suppose it's Ubnt AirOS version >= 5.6
                $tmpOID = '.1.3.6.1.4.1.41112.1.4.1.1.4';
                $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);

                if ( !empty($tmpSNMP) && $tmpSNMP !== "$tmpOID = " ) {
                    $APFreq = (empty($tmpSNMP) && $tmpSNMP === "$tmpOID = ") ? '' : trim(substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                    $tmpOID = '.1.3.6.1.4.1.41112.1.4.5.1.2';
                    $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                    $APSSID = (empty($tmpSNMP) && $tmpSNMP === "$tmpOID = ") ? '' : trim(substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                    $tmpOID = '.1.3.6.1.4.1.41112.1.4.5.1.14';
                    $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                    $APBandChWidth = (empty($tmpSNMP) && $tmpSNMP === "$tmpOID = ") ? '' : trim(substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                    // Device MAC for Loco M2
                    $tmpOID = '.1.2.840.10036.1.1.1.1.5';
                    $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                    $APMAC = (empty($tmpSNMP) && $tmpSNMP === "$tmpOID = ") ? '' : $this->getMACFromSNMPStr($tmpSNMP);

                } else {
        // now suppose it's Ligowave DLB
                    $tmpOID = '.1.3.6.1.4.1.32750.3.10.1.2.1.1.1';
                    $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);

                    if ( !empty($tmpSNMP) && $tmpSNMP !== "$tmpOID = " ) {
                        $APMAC = $this->getMACFromSNMPStr($tmpSNMP);

                        $tmpOID = '.1.3.6.1.4.1.32750.3.10.1.2.1.1.4';
                        $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                        $APSSID = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                        $tmpOID = '.1.3.6.1.4.1.32750.3.10.1.2.1.1.6';
                        $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                        $APFreq = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                        $tmpOID = '.1.3.6.1.4.1.32750.3.10.1.2.1.1.8';
                        $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                        $APBandChWidth = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );
                    } else {
        // now suppose it's Deliberant APC Series
                        $tmpOID = '.1.3.6.1.4.1.32761.3.5.1.2.1.1.4';
                        $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);

                        if ( !empty($tmpSNMP) && $tmpSNMP !== "$tmpOID = " ) {
                            $APSSID = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                            $tmpOID = '.1.3.6.1.4.1.32761.3.5.1.2.1.1.7';
                            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                            $APFreq = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );

                            $tmpOID = '.1.3.6.1.4.1.32761.3.5.1.2.1.1.9';
                            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);
                            $APBandChWidth = ( empty($tmpSNMP) && $tmpSNMP === "$tmpOID = " ) ? '' : trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) );
                        } else {
                             // Device MAC for UBNT

                            $tmpOID = '.1.2.840.10036.1.1.1.1.5';
                            $tmpSNMP = $this->snmp->walk($APIP, $APCommunity, $tmpOID, false);

                            if ( !empty($tmpSNMP) && $tmpSNMP !== "$tmpOID = " ) {
                                $APMAC = $this->getMACFromSNMPStr($tmpSNMP);
                            } else {
                            //    WHAT A HELL ARE YOU?!
                            }
                        }
                    }

                }

            }

            if ($ReturnHTML) {
                $APInfoRows   = '';
                $APInfoHTML = '';

                if ( !empty($APSysDescr) ) {
                    $cells = wf_TableCell(__('System description'), '20%', 'row2');
                    $cells .= wf_TableCell($APSysDescr);
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APSysName) ) {
                    $cells = wf_TableCell(__('System name'), '20%', 'row2');
                    $cells .= wf_TableCell($APSysName);
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APUptime) ) {
                    $cells = wf_TableCell(__('Uptime'), '20%', 'row2');
                    $cells .= wf_TableCell($APUptime);
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APSSID) ) {
                    $cells = wf_TableCell(__('SSID'), '20%', 'row2');
                    $cells .= wf_TableCell($APSSID);
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APFreq) ) {
                    $cells = wf_TableCell(__('Frequency'), '20%', 'row2');
                    $cells .= wf_TableCell($APFreq . ' MHz');
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APBandChWidth) ) {
                    $cells = wf_TableCell(__('Band/channel width'), '20%', 'row2');
                    $cells .= wf_TableCell($APBandChWidth . ' MHz');
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($MTikCPULoad) ) {
                    $cells = wf_TableCell(__('CPU load'), '20%', 'row2');
                    $cells .= wf_TableCell($MTikCPULoad . '%');
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                if ( !empty($APMAC) ) {
                    $cells = wf_TableCell(__('MAC address'), '20%', 'row2');
                    $cells .= wf_TableCell($APMAC);
                    $APInfoRows .= wf_TableRow($cells, 'row3');
                }

                $APInfoHTML = ( empty($APInfoRows) ) ? '' : wf_TableBody($APInfoRows, '88%', 0, '', 'style="margin: 0 auto;"');

                if ($ReturnInSpoiler) {
                    $APInfoHTML = wf_Spoiler($APInfoHTML, __('System AP info'), $SpoilerClosed, '', '', '', '', 'style="margin: 10px auto;"');
                }

                return $APInfoHTML;
            } else {
                $SNMPDataArray = array( 'APSysDescr'     => $APSysDescr,
                                        'APUptime'       => $APUptime,
                                        'APSysName'      => $ReturnHTML,
                                        'APSSID'         => $APSSID,
                                        'APFreq'         => $APFreq,
                                        'APBandChWidth'  => $APBandChWidth,
                                        'MTikCPULoad'    => $MTikCPULoad,
                                        'APMAC'          => $APMAC
                                     );

                return $SNMPDataArray;
            }
        }
    }


    /**
     * Returns MAC in 'XX:XX:XX:XX:XX:XX' format from something like this: '.1.3.6.1.2.1.2.2.1.6.1 = Hex-STRING: E4 8D 8C 27 2F 7B'
     * or
     * Returns MAC in 'XX:XX:XX:XX:XX:XX' format from something like this: '.1.2.840.10036.1.1.1.1.5 = STRING: "00:27:22:90:11:AE"'
     * or
     * Returns MAC in 'XX:XX:XX:XX:XX:XX' format from something like this: '.1.3.6.1.2.1.2.2.1.6.1 = STRING: 0:c:42:da:af:4'
     *
     * @param $SNMPString
     *
     * @return string
     */
    protected function getMACFromSNMPStr($SNMPString, $MACDelimiter = '') {
        $APMAC = '';
        $MACDelimiter = ( empty($MACDelimiter)) ? ':' : $MACDelimiter;

        $tmpOidDataArray = explode(': ', $SNMPString);
        if ( isset($tmpOidDataArray[1]) ) {
            $tmpData = trim($tmpOidDataArray[1]);
            $tmpData = preg_replace('/"/', '', $tmpData);
            $tmpDataArray = preg_split('/[\s:]+/', $tmpData); // alternative for function explode for two and more parametrs

            if (count($tmpDataArray) == 6) {
                $APMAC = vsprintf('%02s' . $MACDelimiter . '%02s' . $MACDelimiter . '%02s' . $MACDelimiter . '%02s' . $MACDelimiter . '%02s' . $MACDelimiter . '%02s', $tmpDataArray);
            }
        }

        return $APMAC;
    }

    /**
     * Polls wireless APs/CPEs and stores data to cache
     *
     * @param int $mtid
     * @param string $WiFiCPEIP
     * @param string $WiFiCPEMAC
     * @param string $WiFiCPECommunity
     *
     * @return void
     */
    protected function deviceQuery($mtid, $WiFiCPEIP = '', $WiFiCPEMAC = '', $WiFiCPECommunity = 'public') {
        if ( isset($this->allMTSnmp[$mtid]['community']) or (!empty($WiFiCPEIP) and !empty($WiFiCPEMAC)) ) {
            $ip = ( empty($WiFiCPEIP) ) ? $this->allMTSnmp[$mtid]['ip'] : $WiFiCPEIP;
            $community = ( empty($WiFiCPEIP) ) ? $this->allMTSnmp[$mtid]['community'] : $WiFiCPECommunity;
            global $ubillingConfig;
            $alterCfg = $ubillingConfig->getAlter();

            $oid  = '.1.3.6.1.4.1.14988.1.1.1.2.1.3';    // - RX Signal Strength
            $oid2 = '.1.3.6.1.4.1.14988.1.1.1.2.1.19';  // - TX Signal Strength
            $oid3 = '.1.2.840.10036.1.1.1.1.5';        // - MAC adress of Device WLAN interface
            $mask_mac = false;
            $ubnt_shift = 0;
            $result = array();
            $rawsnmp = array();
            $rawsnmp2 = array();
            $result_fdb = array();
            $DeliberantClient = false;

            $this->snmp->setBackground(false);
            $this->snmp->setMode('native');
            $tmpSnmp  = $this->snmp->walk($ip, $community, $oid, false);
            $tmpSnmp2 = $this->snmp->walk($ip, $community, $oid2, false);
            $tmpSnmp3 = $this->snmp->walk($ip, $community, $oid3, false);

            // If returned string '.1.3.6.1.4.1.14988.1.1.1.2.1.3 = ' - then:
            // For AirOS 5.6 and newer
            if ($tmpSnmp === "$oid = ") {
                $oid = '.1.3.6.1.4.1.41112.1.4.7.1.3.1';
                $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);
                $ubnt_shift = 1;
            }

            // For Ligowave DLB 2-90
            if ($tmpSnmp === "$oid = ") {
                $oid = '.1.3.6.1.4.1.32750.3.10.1.3.2.1.5.5';
                $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);
            }

            // For Ligowave DLB 2-90 after 7.59 firmware version
            if ($tmpSnmp === "$oid = ") {
                $oid = '.1.3.6.1.4.1.32750.3.10.1.3.2.1.5.7';
                $tmpSnmp = $this->snmp->walk($ip, $community, $oid, false);
            }

            /*
            // For Deliberant APC Series clients. Won't work for APs, cause there is no ability to monitor
            // Deliberant APC Series APs clients signal level via SNMP. Only on clients itself
            if ($tmpSnmp === "$oid = ") {
                $DeliberantClient = true;
                $oid  = '.1.3.6.1.4.1.32761.3.5.1.2.1.1.14.6';
                $oid2 = '.1.3.6.1.2.1.2.2.1.6.6';
                $tmpSnmp  = $this->snmp->walk($ip, $community, $oid, false);
                $tmpSnmp2 = $this->snmp->walk($ip, $community, $oid2, false);
            }*/

            if ($alterCfg['SWITCHES_SNMP_MAC_EXORCISM']) {
                $APMAC = '';
                // Check and write MAC adress of Device WLAN interface
                // For AirOS 5.6 and newer
                if (!empty($tmpSnmp3) && $tmpSnmp3 !== "$oid3 = " ) {
                    $APMAC = $this->getMACFromSNMPStr($tmpSnmp3);
                } else {
                    // For Ligowave DLB
                    $oid3 = '.1.3.6.1.4.1.32750.3.10.1.2.1.1.1';
                    $tmpSnmp3 = $this->snmp->walk($ip, $community, $oid3, false);

                    if (!empty($tmpSnmp3) && $tmpSnmp3 !== "$oid3 = " ) {
                        $APMAC = $this->getMACFromSNMPStr($tmpSnmp3);
                    } else {
                        // For Mikrotik
                        $oid3 = '.1.3.6.1.2.1.2.2.1.6.1';
                        $tmpSnmp3 = $this->snmp->walk($ip, $community, $oid3, false);

                        if (!empty($tmpSnmp3) && $tmpSnmp3 !== "$oid3 = " ) {
                            $APMAC = $this->getMACFromSNMPStr($tmpSnmp3);
                        }
                    }
                }

                // Write Device MAC address to file
                if (!empty($APMAC)) {
                    file_put_contents('exports/' . $ip . '_MAC', strtolower($APMAC));
                }
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

            if (!empty($tmpSnmp2) and ( $tmpSnmp2 !== "$oid2 = ") and !$DeliberantClient) {
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

            $rssi  = '';
            $rssi2 = '';
            $TXoid = '';

            if (!empty($rawsnmp)) {
                if (is_array($rawsnmp)) {
                    foreach ($rawsnmp as $indexOID => $rssi) {
                        $mac = '';

                        if ($DeliberantClient) {
                            $mac = $this->getMACFromSNMPStr($tmpSnmp2);
                        } else {
                            $TXoid = (!empty($rawsnmp2)) ? str_replace($oid, $oid2, $indexOID) : '';

                            $oidarray = explode(".", $indexOID);
                            $end_num = sizeof($oidarray) + $ubnt_shift;

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
                        }

                        $rssi = str_replace('INTEGER:', '', $rssi);
                        $rssi = trim($rssi);

                        if (!empty($TXoid)) {
                            $rssi2 = $rawsnmp2[$TXoid];
                            $rssi2 = str_replace('INTEGER:', '', $rssi2);
                            $rssi2 = trim($rssi2);
                            $rssi2 = ' / ' . $rssi2;
                        }

                        if ( empty($WiFiCPEIP) ) {
                            $result[$mac] = $rssi . $rssi2;
                            $result_fdb[] = $mac;

                            $HistoryFile = self::CPE_SIG_PATH . md5($mac) . '_AP';
                        } else { $HistoryFile = self::CPE_SIG_PATH . md5($WiFiCPEMAC) . '_CPE'; }

                        file_put_contents($HistoryFile, curdatetime() . ',' . $rssi . ',' . mb_substr($rssi2, 3) . "\n", FILE_APPEND);
                    }
                }
            }

            if ( empty($WiFiCPEIP) ) {
                if ($this->userLogin and $this->userSwitch) {
                    $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
                } else {
                    $this->cache->set(self::CACHE_PREFIX . $mtid, $result, $this->cacheTime);
                    $this->deviceIdUsersMac[$mtid] = $result_fdb;
                }
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

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= 'function APIndividualRefresh(APID, JQAjaxTab, RefreshButtonSelector) {                        
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",
                            data: {IndividualRefresh:true, apid:APID},
                            success: function(result) {
                                        if ($.type(JQAjaxTab) === \'string\') {
                                            $("#"+JQAjaxTab).DataTable().ajax.reload();
                                        } else {
                                            $(JQAjaxTab).DataTable().ajax.reload();
                                        }
                                        
                                        if ($.type(RefreshButtonSelector) === \'string\') {
                                            $("#"+RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        } else {
                                            $(RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        }
                                     }
                        });
                    };

                    function getAPInfo(APID, InfoBlckSelector, ReturnHTML = false, InSpoiler = false, RefreshButtonSelector) {                        
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",
                            data: { IndividualRefresh:true, 
                                    GetAPInfo:true, 
                                    apid:APID,
                                    returnAsHTML:ReturnHTML,
                                    returnInSpoiler:InSpoiler
                                  },
                            success: function(result) {                       
                                        if ($.type(RefreshButtonSelector) === \'string\') {
                                            $("#"+RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        } else {
                                            $(RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        }
                                        
                                        var InfoBlck = $(InfoBlckSelector);                                        
                                        if ( !InfoBlck.length || !(InfoBlck instanceof jQuery)) {return false;}
                                              
                                        $(InfoBlck).html(result);
                                     }
                        });
                    }
                    ';

        // making an event binding for "DelUserAssignment" button("red cross" near user's login) on "CPE create&assign form"
        // to be able to create "CPE create&assign form" dynamically and not to put it's content to every "Create CPE" button in JqDt tables
        // creating of "CPE create&assign form" dynamically reduces the amount of text and page weight dramatically
        $result.= '$(document).on("click", ".__UsrDelAssignButton", function(evt) {
                            $("[name=assignoncreate]").val("");
                            $(\'.__UsrAssignBlock\').html("' . __('Do not assign WiFi equipment to any user') . '");
                            evt.preventDefault();
                            return false;
                    });
                    
                    ';

        // making an event binding for "CPE create&assign form" 'Submit' action to be able to create "CPE create&assign form" dynamically
        $result .= '$(document).on("submit", ".__CPEAssignAndCreateForm", function(evt) {
                            //var FrmAction = \'"\' + $(".__CPEAssignAndCreateForm").attr("action") + \'"\';                            
                            var FrmAction = $(".__CPEAssignAndCreateForm").attr("action");
                            
                            if ( $(".__CPEAACFormNoRedirChck").is(\':checked\') ) {
                                var FrmData = $(".__CPEAssignAndCreateForm").serialize();
                                evt.preventDefault();
                                
                                $.ajax({
                                    type: "POST",
                                    url: FrmAction,
                                    data: FrmData,
                                    success: function() {
                                                if ( $(".__CPEAACFormPageReloadChck").is(\':checked\') ) { location.reload(); }
                                                
                                                $( \'#\'+$(".__CPEAACFormReplaceCtrlID").val() ).replaceWith(\'' . web_ok_icon() . '\');                                                
                                                $( \'#\'+$(".__CPEAACFormModalWindowID").val() ).dialog("close");
                                            }
                                });
                            }
                        });
                        ';
        $result .= wf_tag('script', true);

        $result .= wf_delimiter();

        return ($result);
    }

    /**
     * Renders available CPE JQDT list container
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

        if ($this->WCPEEnabled) { $columns[] = __('Actions'); }

        if (empty($this->allMTDevices) and ! empty($this->userLogin) and empty($this->userSwitch)) {
            $result.= show_window('', $this->messages->getStyledMessage(__('User MAC not found on devises'), 'warning'));
        } elseif (!empty($this->allMTDevices) and ! empty($this->userLogin) and ! empty($this->userSwitch)) {
            $result .= show_window(wf_img('skins/wifi.png') . ' ' . __(@$this->allMTDevices[$this->userSwitch]), wf_JqDtLoader($columns, '' . self::URL_ME . '&ajaxmt=true&mtid=' . $this->userSwitch . '&username=' . $this->userLogin, false, __('results'), 100, $opts));
        } elseif (!empty($this->allMTDevices) and empty($this->userLogin)) {
            // to prevent changing the keys order of $this->allMTDevices we are using "+" opreator and not all those "array_merge" and so on
            $QickAPsArray   = array(-9999 => '') + $this->allMTDevices;

            foreach ($this->allMTDevices as $MTId => $eachMT) {
                $MTsigmonData = $this->cache->get(self::CACHE_PREFIX . $MTId, $this->cacheTime);
                if (! empty($MTsigmonData)) {
                    foreach ($MTsigmonData as $eachmac => $eachsig) {
                        if (strpos($eachsig, '/') !== false) {
                            $columns[6] = __('Signal') . ' RX / TX (' . __('dBm') . ')';
                        } else {
                            $columns[6] = __('Signal') . ' (' . __('dBm') . ')';
                        }

                        break;
                    }
                }

                $AjaxURLStr     = '' . self::URL_ME . '&ajaxmt=true&mtid=' . $MTId . '';
                $JQDTId         = 'jqdt_' . md5($AjaxURLStr);
                $APIDStr        = 'APID_' . $MTId;
                $InfoButtonID   = 'InfID_' . $MTId;
                $InfoBlockID    = 'InfBlck_' . $MTId;
                $QuickAPLinkID  = 'QuickAPLinkID_' . $MTId;
                $QuickAPDDLName = 'QuickAPDDL_' . wf_InputId();
                $QuickAPLink    =   wf_tag('span', false, '', 'id="' . $QuickAPLinkID . '"') .
                                    wf_img('skins/wifi.png') . wf_tag('span', true);

                if ( isset($this->allMTSnmp[$MTId]['ip']) ) {
                    $apWebIfaceLink = wf_tag('a', false, '', 'href="http://' . $this->allMTSnmp[$MTId]['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                    $apWebIfaceLink .= wf_img('skins/ymaps/network.png');
                    $apWebIfaceLink .= wf_tag('a', true);
                } else {
                    $apWebIfaceLink = '';
                }

                $APInfoBlock = wf_tag('div', false, '', 'id="' . $InfoBlockID . '"');
                $APInfoBlock .= wf_tag('div', true);

                $APInfoButton   = wf_tag('a', false, '', 'href="#" id="' . $InfoButtonID . '" title="' . __('Get system info for this AP') . '"');
                $APInfoButton .= wf_img('skins/icn_alert_info.png');
                $APInfoButton .= wf_tag('a', true);
                $APInfoButton .= wf_tag('script', false, '', 'type="text/javascript"');
                $APInfoButton .= '$(\'#' . $InfoButtonID . '\').click(function(evt) {
                                        $(\'img\', this).toggleClass("image_rotate");
                                        getAPInfo(' . $MTId . ', "#' . $InfoBlockID . '", true, true, ' . $InfoButtonID . ');                                        
                                        evt.preventDefault();
                                        return false;                
                                    });';
                $APInfoButton .= wf_tag('script', true);

                $refresh_button = wf_tag('a', false, '', 'href="#" id="' . $APIDStr . '" title="' . __('Refresh data for this AP') . '"');
                $refresh_button .= wf_img('skins/refresh.gif');
                $refresh_button .= wf_tag('a', true);
                $refresh_button .= wf_tag('script', false, '', 'type="text/javascript"');
                $refresh_button .= '$(\'#' . $APIDStr . '\').click(function(evt) {
                                        $(\'img\', this).toggleClass("image_rotate");
                                        APIndividualRefresh(' . $MTId . ', ' . $JQDTId . ', ' . $APIDStr . ');                                        
                                        evt.preventDefault();
                                        return false;                
                                    });';
                $refresh_button .= wf_tag('script', true);

                if ($this->EnableQuickAPLinks) {
                    $QuickAPLinkInput = wf_tag('div', false, '', 'style="width: 100%; text-align: right; margin-top: 15px; margin-bottom: 20px"') .
                                        wf_tag('font', false, '', 'style="font-weight: 600"') . __('Go to AP') . wf_tag('font', true) .
                                        '&nbsp&nbsp' . wf_Selector($QuickAPDDLName, $QickAPsArray, '', '', true) .
                                        wf_tag('script', false, '', 'type="text/javascript"') .
                                        '$(\'[name="' . $QuickAPDDLName . '"]\').change(function(evt) {                                            
                                            //var LinkIDObjFromVal = $(\'a[href="#\'+$(this).val()+\'"]\');                                            
                                            //$(\'body,html\').animate( { scrollTop: $(LinkIDObjFromVal).offset().top - 30 }, 4500 );
                                            var LinkIDObjFromVal = $(\'#QuickAPLinkID_\'+$(this).val());
                                            $(\'body,html\').scrollTop( $(LinkIDObjFromVal).offset().top - 25 );
                                        });' .
                                        wf_tag('script', true) .
                                        wf_tag('div', true);
                } else {
                    $QuickAPLinkInput = '';
                }

                $result .= show_window( $refresh_button . '&nbsp&nbsp&nbsp&nbsp' . $APInfoButton . '&nbsp&nbsp&nbsp&nbsp' . $apWebIfaceLink . '&nbsp&nbsp&nbsp&nbsp' . $QuickAPLink . '&nbsp&nbsp' .
                                        __(@$eachMT), $APInfoBlock . wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts) .
                                        $QuickAPLinkInput

                );
            }
        } else {
            $result.= show_window('', $this->messages->getStyledMessage(__('No devices for signal monitoring found'), 'warning'));
        }
        $result.= wf_delimiter();
        return ($result);
    }


    public function renderMTListTabbed() {
        $result = '';
        $loopIndex = 0;     // for dirty-dirty hack

        if (!empty($this->existingMTSwitchGroups)) {
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

            if ($this->WCPEEnabled) {
                $columns[] = __('Actions');
            }

            foreach ($this->existingMTSwitchGroups as $io => $eachGroup) {
                $groupName = (empty($eachGroup)) ? __('Ungrouped') : $eachGroup;
                $groupWindowCaption = wf_tag('span', false, '', 'id="GroupLnk_' . $io . '"') .
                                      $groupName .
                                      wf_tag('span', true);
                $displayGroup = array_filter($this->allMTSwitchGroups, function ($var) use ($eachGroup) { return ($var['groupname'] == $eachGroup); });
                $curGroupMTDevices = array_intersect_key($this->allMTDevices, $displayGroup);

                $tabClickScript = '';
                $tabsList = array();
                $tabsData = array();
                $QuickAPDDLName = 'QuickAPDDL_' . wf_InputId();
                $quickGrpDDLName = 'quickGrpDDLName_' . wf_InputId();

                if ($this->EnableQuickAPLinks) {
                    $QuickGrpLinkInput = wf_tag('span', false, '', 'style="float: right; clear: right;') .
                        wf_tag('font', false, '', 'style="font-weight: 600;"') . __('Go to group') . wf_tag('font', true) .
                        wf_nbsp(2) . wf_Selector($quickGrpDDLName, $this->existingMTSwitchGroups, '', '', true) .
                        wf_tag('script', false, '', 'type="text/javascript"') .
                        '$(\'[name="' . $quickGrpDDLName . '"]\').change(function(evt) {                                            
                                            var LinkIDObjFromVal = $(\'#GroupLnk_\'+$(this).val());
                                            $(\'body,html\').scrollTop( $(LinkIDObjFromVal).offset().top - 25 );
                                        });' .
                        wf_tag('script', true) .
                        wf_tag('span', true);
                } else {
                    $QuickGrpLinkInput = '';
                }

                foreach ($displayGroup as $MTId => $eachMT) {
                    $deviceInfo = $curGroupMTDevices[$MTId];
                    $MTsigmonData = $this->cache->get(self::CACHE_PREFIX . $MTId, $this->cacheTime);

                    if (!empty($MTsigmonData)) {
                        foreach ($MTsigmonData as $eachmac => $eachsig) {
                            if (strpos($eachsig, '/') !== false) {
                                $columns[6] = __('Signal') . ' RX / TX (' . __('dBm') . ')';
                            } else {
                                $columns[6] = __('Signal') . ' (' . __('dBm') . ')';
                            }

                            break;
                        }
                    }

                    $AjaxURLStr = '' . self::URL_ME . '&ajaxmt=true&mtid=' . $MTId . '';
                    $JQDTId = 'jqdt_' . md5($AjaxURLStr);
                    $APIDStr = 'APID_' . $MTId;
                    $InfoButtonID = 'InfID_' . $MTId;
                    $InfoBlockID = 'InfBlck_' . $MTId;
                    $QuickAPLinkID = 'QuickAPLinkID_' . $MTId;
                    $webIfaceLnkId = 'webIfaceLnk_' . $MTId;

                    if (isset($this->allMTSnmp[$MTId]['ip'])) {
                        $apWebIfaceLink = wf_tag('span', false, '', 'id="' . $webIfaceLnkId . '" href="http://' . $this->allMTSnmp[$MTId]['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '" style="cursor: pointer;"');
                        $apWebIfaceLink .= wf_img('skins/ymaps/network.png');
                        $apWebIfaceLink .= wf_tag('span', true);
                        $apWebIfaceLink .= wf_tag('script', false, '', 'type="text/javascript"');
                        $apWebIfaceLink .= '$(\'#' . $webIfaceLnkId . '\').click(function(evt) {
                                                window.open(\'http://' . $this->allMTSnmp[$MTId]['ip'] . '\', \'_blank\');
                                           });
                                          ';
                        $apWebIfaceLink .= wf_tag('script', true);
                    } else {
                        $apWebIfaceLink = '';
                    }

                    $APInfoBlock = wf_tag('div', false, '', 'id="' . $InfoBlockID . '"');
                    $APInfoBlock .= wf_tag('div', true);

                    $APInfoButton = wf_tag('span', false, '', 'href="#" id="' . $InfoButtonID . '" title="' . __('Get system info for this AP') . '" style="cursor: pointer;"');
                    $APInfoButton .= wf_img('skins/icn_alert_info.png');
                    $APInfoButton .= wf_tag('span', true);
                    $APInfoButton .= wf_tag('script', false, '', 'type="text/javascript"');
                    $APInfoButton .= '$(\'#' . $InfoButtonID . '\').click(function(evt) {
                                        $(\'img\', this).toggleClass("image_rotate");
                                        getAPInfo(' . $MTId . ', "#' . $InfoBlockID . '", true, true, ' . $InfoButtonID . ');                                        
                                        evt.preventDefault();
                                        return false;                
                                    });';
                    $APInfoButton .= wf_tag('script', true);

                    if ($this->EnableQuickAPLinks) {
                        $tabClickScript = wf_tag('script', false, '', 'type="text/javascript"');
                        $tabClickScript .= '$(\'a[href="#' . $QuickAPLinkID . '"]\').click(function(evt) {
                                            var tmpID = $(this).attr("href").replace("#QuickAPLinkID_", "");
                                            if ($(\'[name="' . $QuickAPDDLName . '"]\').val() != tmpID) {
                                                $(\'[name="' . $QuickAPDDLName . '"]\').val(tmpID);
                                            }
                                        });
                                        ';
                        $tabClickScript .= wf_tag('script', true);
                    }

                    $refresh_button = wf_tag('span', false, '', 'href="#" id="' . $APIDStr . '" title="' . __('Refresh data for this AP') . '" style="cursor: pointer;"');
                    $refresh_button .= wf_img('skins/refresh.gif');
                    $refresh_button .= wf_tag('span', true);
                    $refresh_button .= wf_tag('script', false, '', 'type="text/javascript"');
                    $refresh_button .= '$(\'#' . $APIDStr . '\').click(function(evt) {
                                        $(\'img\', this).toggleClass("image_rotate");
                                        APIndividualRefresh(' . $MTId . ', ' . $JQDTId . ', ' . $APIDStr . ');                                        
                                        evt.preventDefault();
                                        return false;                
                                    });';
                    $refresh_button .= wf_tag('script', true);

                    $tabsList[$QuickAPLinkID] = array('options' => '',
                                                      'caption' => $refresh_button . wf_nbsp(2) . $APInfoButton . wf_nbsp(2) . $apWebIfaceLink .
                                                            wf_nbsp(2) . wf_img('skins/wifi.png') . wf_nbsp(2) . @$deviceInfo,
                                                      'additional_data' => $tabClickScript
                                                     );

                    $tabsData[$QuickAPLinkID] = array('options' => 'style="padding: 0 0 0 2px;"',
                                                      'body' => $APInfoBlock . wf_JqDtLoader($columns, $AjaxURLStr, false, 'CPE', 100, $opts),
                                                      'additional_data' => ''
                                                     );
                }

                $tabsDivOpts = 'style="border: none; padding: 0; width: 100%;"';
                $tabsLstOpts = 'style="border: none; background: #fff;"';

                if ($this->EnableQuickAPLinks) {
                    $QuickAPLinkInput = wf_tag('div', false, '', 'style="margin-top: 15px; text-align: right;"') .
                                        wf_tag('font', false, '', 'style="font-weight: 600"') . __('Go to AP') . wf_tag('font', true) .
                                        wf_nbsp(2) . wf_Selector($QuickAPDDLName, $curGroupMTDevices, '', '', true) .
                                        wf_tag('script', false, '', 'type="text/javascript"') .
                                        '$(\'[name="' . $QuickAPDDLName . '"]\').change(function(evt) {                                            
                                            $(\'a[href="#QuickAPLinkID_\'+$(this).val()+\'"]\').click();
                                        });' .
                                        wf_tag('script', true) .
                                        wf_tag('div', true);
                } else {
                    $QuickAPLinkInput = '';
                }

                // ditry-dirty hack...khe-khe
                if ($loopIndex < 1) {
                    $TabsCarouselInitLinking = wf_TabsCarouselInitLinking();
                } else {
                    $TabsCarouselInitLinking = '';
                }

                $loopIndex++;

                $tmpTabsDivId = 'ui-tabs_' . wf_InputId();
                $result.= show_window($groupWindowCaption . $QuickGrpLinkInput, $QuickAPLinkInput . wf_delimiter(0) .
                                      $TabsCarouselInitLinking . wf_TabsGen($tmpTabsDivId, $tabsList, $tabsData, $tabsDivOpts, $tabsLstOpts, true) .
                                      $QuickAPLinkInput . wf_delimiter());
            }
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
                $signalArr = explode(' / ', $eachsig);

                // if RX/TX signal presents - lets take the lowest value
                if (isset($signalArr[1])) {
                    $signal = ($signalArr[0] > $signalArr[1]) ? $signalArr[1] : $signalArr[0];
                } else {
                    $signal = $signalArr[0];
                }

                //signal coloring
                if ($signal < -79) {
                    $displaysig = wf_tag('font', false, '', 'color="#ab0000"') . $eachsig . wf_tag('font', true);
                } elseif ($signal > -80 and $signal < -74) {
                    $displaysig = wf_tag('font', false, '', 'color="#FF5500"') . $eachsig . wf_tag('font', true);
                } else {
                    $displaysig = wf_tag('font', false, '', 'color="#005502"') . $eachsig . wf_tag('font', true);
                }

                $allMacs = $this->allUserCpeMacs + $this->allUsermacs;
                $allMacs = array_flip($allMacs);
                //$login = in_array($eachmac, array_map('strtolower', $allMacs)) ? array_search($eachmac, array_map('strtolower', $allMacs)) : '';
                $login = (isset($allMacs[$eachmac])) ? $allMacs[$eachmac] : '';

                //user search highlight
                if ((!empty($this->userLogin)) AND ( $this->userLogin == $login)) {
                    $hlStart = wf_tag('font', false, '', 'color="#0045ac"');
                    $hlEnd = wf_tag('font', true);
                } else {
                    $hlStart = '';
                    $hlEnd = '';
                }
                
                $userLink = $login ? wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . @$this->allUserData[$login]['login'] . '', false) : '';
                $userLogin = $login ? @$this->allUserData[$login]['login'] : '';
                $userRealnames = $login ? @$this->allUserData[$login]['realname'] : '';
                $userTariff = $login ? @$this->allUserData[$login]['Tariff'] : '';
                $userIP = $login ? @$this->allUserData[$login]['ip'] : '';

                if ($this->WCPEEnabled) {
                    $WCPE = new WifiCPE();
                    $ActionLnk = '';

                    // check if CPE with such MAC exists and create appropriate control
                    $WCPEID = $WCPE->getCPEIDByMAC($eachmac);
                    if (!empty($WCPEID)) {
                        $WCPEDATA = $WCPE->getCPEData($WCPEID);

                        if ( !empty($WCPEDATA) && !empty($WCPEDATA['ip']) ) {
                            $cpeWebIfaceLink = wf_tag('a', false, '', 'href="http://' . $WCPEDATA['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                            $cpeWebIfaceLink .= wf_img('skins/ymaps/network.png');
                            $cpeWebIfaceLink .= wf_tag('a', true);
                            $ActionLnk .= $cpeWebIfaceLink . '&nbsp';
                        }

                        $ActionLnk .= wf_link($WCPE::URL_ME . '&editcpeid=' . $WCPEID, web_edit_icon());
                    } else {
                        $LnkID = wf_InputId();
                        $ActionLnk .= wf_tag('a', false, '', 'id="' . $LnkID . '" href="#" title="' . __('Create new CPE') . '"');
                        $ActionLnk .= web_icon_create();
                        $ActionLnk .= wf_tag('a', true);
                        $ActionLnk .= wf_tag('script', false, '', 'type="text/javascript"');
                        $ActionLnk .= '
                                        $(\'#' . $LnkID . '\').click(function(evt) {
                                            $.ajax({
                                                type: "GET",
                                                url: "' . $WCPE::URL_ME . '",
                                                data: { 
                                                        renderCreateForm:true,
                                                        renderDynamically:true, 
                                                        renderedOutside:true,
                                                        reloadPageAfterDone:false,
                                                        userLogin:"' . $userLogin . '", 
                                                        wcpeMAC:"' . $eachmac . '",
                                                        wcpeIP:"' . $userIP . '",
                                                        wcpeAPID:"' . $MTid . '",                                                        
                                                        ModalWID:"dialog-modal_' . $LnkID . '", 
                                                        ModalWBID:"body_dialog-modal_' . $LnkID . '",
                                                        ActionCtrlID:"' . $LnkID . '"
                                                       },
                                                success: function(result) {
                                                            $(document.body).append(result);
                                                            $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                                         }
                                            });
                    
                                            evt.preventDefault();
                                            return false;
                                        });
                                      ';
                        $ActionLnk .= wf_tag('script', true);
                    }
                }

                $data[] = $userLink;
                $data[] = $hlStart . @$this->allUserData[$login]['fulladress'] . $hlEnd;
                $data[] = $hlStart . $userRealnames . $hlEnd;
                $data[] = $hlStart . $userTariff . $hlEnd;
                $data[] = $hlStart . $userIP . $hlEnd;
                $data[] = $hlStart . $eachmac . $hlEnd;
                $data[] = $displaysig;

                if ($this->WCPEEnabled) { $data[] = $ActionLnk; }

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }


    public function useSwtichGroupsAndTabs() {
        return ($this->switchGroupsEnabled and $this->groupAPsBySwitchGroupWithTabs);
    }

    /**
     * Returns array like: $userLogin => $wifiSignal
     *
     * @return array
     */
    public function getAllWiFiSignals() {
        $allSignals = array();

        if (!empty($this->allMTDevices)) {
            foreach ($this->allMTDevices as $MTId => $eachMT) {
                $MTsigmonData[] = $this->cache->get(self::CACHE_PREFIX . $MTId, $this->cacheTime);
            }
        }

        if (!empty($MTsigmonData)) {
            $allMacs = $this->allUserCpeMacs + $this->allUsermacs;
            $allMacs = array_flip($allMacs);

            foreach ($MTsigmonData as $eachMTid ) {
                if (is_array($eachMTid) and !empty($eachMTid)) {
                    foreach ($eachMTid as $eachmac => $eachsig) {
                        //$login = in_array($eachmac, array_map('strtolower', $allMacs)) ? array_search($eachmac, array_map('strtolower', $allMacs)) : '';
                        $login = (isset($allMacs[$eachmac])) ? $allMacs[$eachmac] : '';

                        if (!empty($login)) {
                            //$signal = explode('/', $eachsig);
                            $allSignals[$login] = $eachsig;
                        }
                    }
                }
            }
        }

        return ($allSignals);
    }
}

?>
