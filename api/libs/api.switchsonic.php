<?php

/**
 * Live switch/routers SNMP bandwidth monitoring implementation
 */
class SwitchSonic {

    /**
     * Contains remote device IP address
     *
     * @var string
     */
    protected $ip = '';

    /**
     * Contains remote device SNMP read community
     *
     * @var string
     */
    protected $community = '';

    /**
     * SNMP helper object placeholder
     *
     * @var object
     */
    protected $snmp = '';

    /**
     * Caching engine object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default caching timeout to store switch/auth data in seconds
     *
     * @var int
     */
    protected $cachingTimeout = 120;

    /**
     * Contains default kilo-multiplier to convert bits in Kilo
     *
     * @var int
     */
    protected $offsetKilo = 1024;

    /**
     * Contains default kilo-multiplier to convert bits in Mega
     *
     * @var int
     */
    protected $offsetMega = 1024000;

    /**
     * Contains default kilo-multiplier to convert bits in Giga
     *
     * @var int
     */
    protected $offsetGiga = 1024000000;

    //some predefined stuff
    const CACHE_KEY = 'SWITCHSONICDATA';
    const AUTH_KEY = 'SWITCHSONICAUTH';
    const OID_CHECK = '.1.3.6.1.2.1.1.1.0';
    const OID_IFINDEX = '.1.3.6.1.2.1.2.2.1.1';
    const OID_IFDESCR = '.1.3.6.1.2.1.31.1.1.1.18';
    const OID_OCTIN = '.1.3.6.1.2.1.31.1.1.1.6';
    const OID_OCTOUT = '.1.3.6.1.2.1.31.1.1.1.10';
    const OID_STATE = '.1.3.6.1.2.1.2.2.1.8';

    /**
     * Cretes new Sonic instance
     * 
     * @param string $ip
     * @param string $community
     */
    public function __construct($ip, $community) {
        $this->setOptions($ip, $community);
        $this->initCache();
        $this->initSnmp();

        //       ___------__
        // |\__-- /\       _-
        // |/    __      -
        // //\  /  \    /__
        // |  o|  0|__     --_
        // \\____-- __ \   ___-
        // (@@    __/  / /_
        //    -_____---   --_
        //     //  \ \\   ___-
        //   //|\__/  \\  \
        //   \_-\_____/  \-\
        //        // \\--\|  
        //   ____//  ||_
        //  /_____\ /___\
    }

    /**
     * Sets IP/community to current instance
     * 
     * @param string $ip
     * @param string $community
     * 
     * @return void
     */
    protected function setOptions($ip, $community) {
        if (!empty($ip)) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                if (!empty($community)) {
                    $this->ip = $ip;
                    $this->community = $community;
                } else {
                    throw new Exception('EX_EMPTY_SNMPCOMMUNITY');
                }
            } else {
                throw new Exception('EX_WRONG_IP');
            }
        } else {
            throw new Exception('EX_EMPTY_IP');
        }
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
     * Inits SNMP helper instance for further usage
     * 
     * @return void
     */
    protected function initSnmp() {
        $this->snmp = new SNMPHelper();
        $this->snmp->setMode('system');
    }

    /**
     * Returns array of available interfaces on device as port=>iface
     * 
     * @return array
     */
    protected function getIfaces() {
        return($this->receiveOidData(self::OID_IFINDEX));
    }

    /**
     * Returns available ports description as port=>desc
     * 
     * @return array
     */
    protected function getIfDescr() {
        return($this->receiveOidData(self::OID_IFDESCR));
    }

    /**
     * Returns array of interfaces octets in as port=>octets
     * 
     * @return array
     */
    protected function getOctIn() {
        return($this->receiveOidData(self::OID_OCTIN));
    }

    /**
     * Returns array of interfaces octets out as port=>octets
     * 
     * @return array
     */
    protected function getOctOut() {
        return($this->receiveOidData(self::OID_OCTOUT));
    }

    /**
     * Returns array of interface activity states as port=>state up/down
     * 
     * @return array
     */
    protected function getLinks() {
        return($this->receiveOidData(self::OID_STATE));
    }

    /**
     * Checks can we auth on device and receive some data or not?
     * 
     * @return bool
     */
    public function checkAuth() {
        $result = false;
        $rawCache = $this->cache->get(self::AUTH_KEY, $this->cachingTimeout);
        if (empty($rawCache)) {
            $rawCache = array();
        }

        if (isset($rawCache[$this->ip])) {
            $result = true;
        } else {
            $checkResult = $this->snmp->walk($this->ip, $this->community, self::OID_CHECK, false);
            if (!empty($checkResult)) {
                $rawCache[$this->ip] = 1;
                $this->cache->set(self::AUTH_KEY, $rawCache, $this->cachingTimeout);
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Returns port/interface ID extracted from left part of OID
     * 
     * @param string $rawSnmpData
     * @param string $oid
     * 
     * @return string
     */
    protected function extractPortNum($rawSnmpData, $oid) {
        $result = '';
        $removeMask = $oid . '.';
        if (!empty($rawSnmpData)) {
            $result = explode('=', $rawSnmpData);
            $result = trim($result[0]);
            $result = str_replace($removeMask, '', $result);
        }
        return($result);
    }

    /**
     * Returns preprocessed data extracted from OID as port=>value
     * 
     * @param string $oid
     * 
     * @return array
     */
    protected function receiveOidData($oid) {
        $result = array();
        $raw = $this->snmp->walk($this->ip, $this->community, $oid, false);
        if (!empty($raw)) {
            $raw = explodeRows($raw);
            if (!empty($raw)) {
                foreach ($raw as $io => $each) {
                    if (!empty($each)) {
                        $portNum = $this->extractPortNum($each, $oid);
                        if (!empty($portNum)) {
                            $result[$portNum] = zb_SanitizeSNMPValue($each);
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Rturns array of preprocessed device stats 
     * 
     * @return array
     */
    public function getStats() {
        $devStats = array();
        $cachedStats = $this->cache->get(self::CACHE_KEY, $this->cachingTimeout);

        if (empty($cachedStats)) {
            $cachedStats = array();
        }

        //initial filling
        if (!isset($cachedStats[$this->ip])) {
            $devIfindex = $this->getIfaces();
            //interfaces index received?
            if (!empty($devIfindex)) {
                $devPortDesc = $this->getIfDescr();
                $devInOcts = $this->getOctIn();
                $devOutOcts = $this->getOctOut();
                $devLinks = $this->getLinks();

                $pollTime = time();
                $devStats['lasttime'] = $pollTime;
                $devStats['ifaces'] = $devIfindex;
                $devStats['portdescr'] = $devPortDesc;
                $devStats['previn'] = $devInOcts;
                $devStats['prevout'] = $devOutOcts;
                $devStats['links'] = $devLinks;
                foreach ($devOutOcts as $eachOutPort => $eachOutOct) {
                    $devStats['speedin'][$eachOutPort] = 0;
                    $devStats['speedout'][$eachOutPort] = 0;
                }

                $devStats['speedline'] = array();
            }
        } else {
            $devTmp = $cachedStats[$this->ip];
            $newOctIn = $this->getOctIn();
            $newOctOut = $this->getOctOut();
            $newLinks = $this->getLinks();
            $pollTime = time();
            $timePast = $pollTime - $devTmp['lasttime'];
            $timePast = ($timePast != 0) ? $timePast : 1; //prevent div by zero
            foreach ($newOctIn as $io => $eachIn) {
                $speedIn = ($eachIn - $devTmp['previn'][$io]) / $timePast;
                $rawSpeed = round($speedIn);
                $devTmp['speedin'][$io] = $rawSpeed;
                $devTmp['speedline'][$io]['in'][$pollTime] = $rawSpeed;
            }
            foreach ($newOctOut as $io => $eachOut) {
                $speedOut = ($eachOut - $devTmp['prevout'][$io]) / $timePast;
                $rawSpeed = round($speedOut);
                $devTmp['speedout'][$io] = $rawSpeed;
                $devTmp['speedline'][$io]['out'][$pollTime] = $rawSpeed;
            }

            $devStats['lasttime'] = $pollTime;
            $devStats['ifaces'] = $devTmp['ifaces'];
            $devStats['portdescr'] = $devTmp['portdescr'];
            $devStats['previn'] = $newOctIn;
            $devStats['prevout'] = $newOctOut;
            $devStats['speedin'] = $devTmp['speedin'];
            $devStats['speedout'] = $devTmp['speedout'];
            $devStats['links'] = $newLinks;
            $devStats['speedline'] = $devTmp['speedline'];
        }

        //cache update
        $cachedStats[$this->ip] = $devStats;
        $this->cache->set(self::CACHE_KEY, $cachedStats, $this->cachingTimeout);

        return($devStats);
    }

    /**
     * Converts actual octet counters into human-readable speed value
     * 
     * @param int $octets
     * 
     * @return string
     */
    protected function convertSpeed($octets) {
        $result = '';
        $bits = $octets * 8;

        $result = $bits;

        if ($bits > $this->offsetKilo) {
            $result = round($bits / $this->offsetKilo) . ' ' . __('Kbit/s');
        }

        if ($bits > $this->offsetMega) {
            $result = round($bits / $this->offsetMega, 1) . ' ' . __('Mbit/s');
        }

        if ($bits > $this->offsetGiga) {
            $result = round($bits / $this->offsetGiga, 1) . ' ' . __('Gbit/s');
        }
        return($result);
    }

    /**
     * Converts octet speed values into Mbit/s
     * 
     * @param int $octetSpeed
     * 
     * @return float
     */
    protected function speedForCharts($octetSpeed) {
        $result = 0;
        $bits = $octetSpeed * 8;
        $result = $bits / $this->offsetMega;
        $result = round($result, 1);
        return($result);
    }

    /**
     * Converts basic port infor into link state led
     * 
     * @param string $linkState
     * @param int $speedIn
     * @param int $speedOut
     * 
     * @return string
     */
    protected function convertLinkState($linkState, $speedIn = 0, $speedOut = 0) {
        $result = '';
        $result = (ispos($linkState, 'up')) ? web_green_led() : web_red_led();
        if (ispos($linkState, 'up')) {
            //ok, interface is up
            if ($speedIn OR $speedOut) {
                $result = web_green_led('Active');
            } else {
                $result = web_yellow_led('Link up');
            }
        } else {
            //port is offline at all
            $result = web_red_led('Offline');
        }
        return($result);
    }

    /**
     * Renders device stats
     * 
     * @return string
     */
    public function renderSpeeds() {
        $result = '';
        $devStats = $this->getStats();
        if (!empty($devStats)) {
            $ifaces = $devStats['ifaces'];
            $ifdescr = $devStats['portdescr'];
            $prevOut = $devStats['prevout'];


            $cells = wf_tableCell(__('Interface'), '5%');
            $cells .= wf_tableCell(__('Status'), '5%');
            $cells .= wf_tableCell(__('Description'));
            $cells .= wf_tableCell(__('Speed') . ' ' . __('TX'));
            $cells .= wf_tableCell(__('Speed') . ' ' . __('RX'));
            $cells .= wf_tableCell(__('Bytes TX'));
            $cells .= wf_tableCell(__('Bytes RX'));
            $rows = wf_tableRow($cells, 'row1');
            foreach ($prevOut as $portNum => $eachPrevOut) {
                $speedIn = $devStats['speedin'][$portNum];
                $speedOut = $devStats['speedout'][$portNum];
                $cells = wf_tableCell($ifaces[$portNum]);
                $cells .= wf_tableCell($this->convertLinkState($devStats['links'][$portNum], $speedIn, $speedOut));
                $descrLabel = (isset($ifdescr[$portNum])) ? $ifdescr[$portNum] : '';
                $cells .= wf_tableCell($descrLabel);
                $cells .= wf_tableCell($this->convertSpeed($speedIn));
                $cells .= wf_tableCell($this->convertSpeed($speedOut));
                $cells .= wf_tableCell(stg_convert_size($devStats['previn'][$portNum]));
                $cells .= wf_tableCell(stg_convert_size($devStats['prevout'][$portNum]));
                $rows .= wf_tableRow($cells, 'row5');
            }

            $result .= wf_tableBody($rows, '100%');
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders charts based on speedline of some ports
     * 
     * @return string
     */
    public function renderCharts() {
        $result = '';
        $devStats = $this->getStats();

        if (!empty($devStats)) {
            $ifaces = $devStats['ifaces'];
            $ifdescr = $devStats['portdescr'];
            $prevOut = $devStats['prevout'];
            $speedLine = $devStats['speedline'];
            $options = '';
            if (!empty($speedLine)) {
                foreach ($speedLine as $portNum => $speedData) {
                    //some filters here?
                    if (true) {
                        $csvData = '';
                        $portLabel = __('Interface') . ' ' . $portNum;
                        if (isset($ifdescr[$portNum])) {
                            $portDescr = $ifdescr[$portNum];
                            if (!empty($portDescr)) {
                                $portLabel .= ' - ' . $portDescr;
                            }


                            foreach ($speedData['in'] as $timeIn => $speedIn) {
                                $timeLabel = date("Y-m-d H:i:s", $timeIn);
                                $speedOut = $speedData['out'][$timeIn];
                                if ($speedIn OR $speedOut) {
                                    $inLabel = $this->speedForCharts($speedIn);
                                    $outLabel = $this->speedForCharts($speedOut);
                                    $csvData .= $timeLabel . ',' . $inLabel . ',' . $outLabel . PHP_EOL;
                                }
                            }
                        }

                        if (!empty($csvData)) {
                            $result .= wf_Graph($csvData, '100%', '200px;', false, $portLabel, __('Time'), __('Mbit/s'), false);
                        }
                    }
                }
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Nothing to show') . '. ' . __('Collecting data') . '...', 'warning');
            }
        }
        return($result);
    }

}
