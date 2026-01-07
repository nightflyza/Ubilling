<?php

/**
 * This class is responsible for getting Juniper BGP peers stats using SNMP.
 */
class JunBGP {

    /**
     * Contains system SNMP Helper instance
     *
     * @var object
     */
    protected $snmp = '';

    /**
     * Determines whether to use raw SNMP cache
     *
     * @var bool
     */
    protected $rawCache = false;

    /**
     * Mapping of BGP state codes to state names
     *
     * @var array
     */
    protected $statesNames = array(
        1 => 'idle',
        2 => 'connect',
        3 => 'active',
        4 => 'opensent',
        5 => 'openconfirm',
        6 => 'established'
    );

    /**
     * Mapping of BGP status codes to status names
     *
     * @var array
     */
    protected $statusNames = array(
        1 => 'halted',
        2 => 'running'
    );

    /**
     * IP address of the BGP peer
     *
     * @var string
     */
    protected $ip = '';

    /**
     * SNMP community string
     *
     * @var string
     */
    protected $community = '';

    /**
     * Some predefined stuff here
     */
    const OID_PEER_TABLE = '.1.3.6.1.4.1.2636.5.1.1.2';
    const OID_INDEX = '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.14';
    const OID_REMOTEIP = '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.11';
    const OID_AS = '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.13';
    const OID_STATE = '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.2';
    const OID_STATUS = '.1.3.6.1.4.1.2636.5.1.1.2.1.1.1.3';
    const OID_TIMERS = '.1.3.6.1.4.1.2636.5.1.1.2.4.1.1.1';
    const OID_PREF_IN = '.1.3.6.1.4.1.2636.5.1.1.2.6.2.1.7';
    const OID_PREF_OUT = '.1.3.6.1.4.1.2636.5.1.1.2.6.2.1.10';

    /**
     * Quidquid latine dictum sit, altum sonatur
     *
     * @param string $ip IP address of the BGP peer
     * @param string $community SNMP community string
     */
    public function __construct($ip, $community) {
        $this->setIp($ip);
        $this->setCommunity($community);
        $this->initSNMP();
    }

    /**
     * Sets the IP address of the BGP peer
     *
     * @param string $ip IP address
     * 
     * @return void
     */
    protected function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     * Sets the SNMP community string
     *
     * @param string $community SNMP community string
     * 
     * @return void
     */
    protected function setCommunity($community) {
        $this->community = $community;
    }

    /**
     * Initializes the SNMP helper instance
     * 
     * @return void
     */
    protected function initSNMP() {
        $this->snmp = new SNMPHelper();
    }

    /**
     * Polls the full BGP peer table using SNMP
     *
     * @return array Result of the SNMP walk
     */
    protected function pollFullTable() {
        $result = $this->snmp->walk($this->ip, $this->community, self::OID_PEER_TABLE, $this->rawCache);
        return ($result);
    }

    /**
     * Decodes a hexadecimal IP address to a human-readable format
     *
     * @param string $value Hexadecimal IP address
     * 
     * @return string Decoded IP address
     */
    protected function decodeIp($value) {
        $result = '';
        //normal hex value?
        if (preg_match('/^([0-9A-F]{2} ){3}[0-9A-F]{2}$/i', $value)) {
            $hexArray = explode(' ', trim($value));
            $hexArray = array_slice($hexArray, -4); //only latest 4 bytes in HEX
            $result = implode('.', array_map('hexdec', $hexArray));
        } else {
            //some string?
            if (preg_match('/^".*"$/', $value)) {
                $rawString = trim($value, '"');
                $lastBytes = substr($rawString, -4); // only latest 4 bytes of string anyway
                $ipParts = array_map('ord', str_split($lastBytes));
                $result = implode('.', $ipParts);
            }
        }

        return ($result);
    }

    /**
     * Parses raw SNMP data
     *
     * @param string $rawData Raw SNMP data
     * @param string $oid OID to search for
     * @param bool $decodeIp Whether to decode IP addresses
     * 
     * @return array Parsed data
     */
    protected function parseData($rawData, $oid, $decodeIp = false) {
        $result = array();
        if (!empty($rawData)) {
            $rawData = explodeRows($rawData);
            foreach ($rawData as $io => $each) {
                if (!empty($each)) {
                    if (ispos($each, $oid)) {
                        $value = zb_SanitizeSNMPValue($each);
                        if ($decodeIp) {
                            $value = $this->decodeIp($value);
                        }
                        $result[] = $value;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts remote IP addresses from raw SNMP data OID itself
     *
     * @param string $rawData Raw SNMP data
     * @param string $oid OID to search for
     * 
     * @return array
     */
    protected function parseRemoteIp($rawData, $oid) {
        $result=array();
        $idx=0;
        if (!empty($rawData)) {
            $rawData = explodeRows($rawData);
            foreach ($rawData as $io => $each) {
                if (!empty($each)) {
                    if (ispos($each, $oid)) {
                        $index = $idx;
                        $oidPart = trim(explode('=', $each)[0]);
                        $oidSuffix = str_replace($oid, '', $oidPart);
                        $oidParts = explode('.', trim($oidSuffix, '.'));
                        $ipParts = array_slice($oidParts, -4);
                        $remoteIp = implode('.', $ipParts);
                        $result[$index] = $remoteIp;
                        $idx++;
                    } 
                }
            }
        }
        return ($result);
    }

    /**
     * Retrieves BGP peer data
     *
     * @return array BGP peer data
     */
    public function getPeersData() {
        $result = array();
        $rawData = $this->pollFullTable();
        $index = $this->parseData($rawData, self::OID_INDEX);
        
        if (!empty($index)) {
            $as = $this->parseData($rawData, self::OID_AS);
            $remoteIp = $this->parseRemoteIp($rawData, self::OID_INDEX);
            if (empty($remoteIp)) {
                $remoteIp = $this->parseData($rawData, self::OID_REMOTEIP, true);
            }
            $states = $this->parseData($rawData, self::OID_STATE);
            $status = $this->parseData($rawData, self::OID_STATUS);
            $timers = $this->parseData($rawData, self::OID_TIMERS);
            foreach ($index as $io => $eachPeerIdx) {
                $peerIp = $remoteIp[$io];
                $result[$peerIp] = array(
                    'index' => $eachPeerIdx,
                    'ip' => $peerIp,
                    'as' => $as[$io],
                    'state' => $states[$io],
                    'stateName' => $this->statesNames[$states[$io]],
                    'status' => $status[$io],
                    'statusName' => $this->statusNames[$status[$io]],
                    'timer' => $timers[$io]
                );
            }
        }

        return ($result);
    }

    //
    //                             ___          
    //                            /   \\        
    //                       /\\ | . . \\       
    //                     ////\\|     ||       
    //                   ////   \\ ___//\       
    //                  ///      \\      \      
    //                 ///       |\\      |     
    //                //         | \\  \   \    
    //                /          |  \\  \   \   
    //                           |   \\ /   /   
    //                           |    \/   /    
    //                           |     \\/|     
    //                           |      \\|     
    //                           |       \\     
    //                           |        |     
    //                           |_________\    
    //                     
    //               there are no immortal neighbors
    //
}
