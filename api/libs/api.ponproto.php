<?php

/**
 * Basic PON OLTs devices collectors hardware abstraction layer prototype
 */
class PONProto {

    /**
     * Contains current HAL instance OLT parameters
     *
     * @var array
     */
    protected $oltParameters = array();

    /**
     * Contains available SNMP templates for OLT modelids
     *
     * @var array
     */
    protected $snmpTemplates = array();

    /**
     * Default ONU offline signal level
     *
     * @var int
     */
    protected $onuOfflineSignalLevel = '-9000';

    /**
     * SNMPHelper object instance
     *
     * @var object
     */
    protected $snmp = '';

    /**
     * Contains OLTData
     *
     * @var object
     */
    protected $olt = '';

    /**
     * Replicated paths from primary PONizer class. 
     * This is here only for legacy of manual data manipulations wit self::
     * instead of usage $this->olt abstraction in HAL libs.
     */
    const SIGCACHE_PATH = OLTAttractor::SIGCACHE_PATH;
    const SIGCACHE_EXT = OLTAttractor::SIGCACHE_EXT;
    const DISTCACHE_PATH = OLTAttractor::DISTCACHE_PATH;
    const DISTCACHE_EXT = OLTAttractor::DISTCACHE_EXT;
    const ONUCACHE_PATH = OLTAttractor::ONUCACHE_PATH;
    const ONUCACHE_EXT = OLTAttractor::ONUCACHE_EXT;
    const INTCACHE_PATH = OLTAttractor::INTCACHE_PATH;
    const INTCACHE_EXT = OLTAttractor::INTCACHE_EXT;
    const INTDESCRCACHE_EXT = OLTAttractor::INTDESCRCACHE_EXT;
    const FDBCACHE_PATH = OLTAttractor::FDBCACHE_PATH;
    const FDBCACHE_EXT = OLTAttractor::FDBCACHE_EXT;
    const DEREGCACHE_PATH = OLTAttractor::DEREGCACHE_PATH;
    const DEREGCACHE_EXT = OLTAttractor::DEREGCACHE_EXT;
    const UPTIME_PATH = OLTAttractor::UPTIME_PATH;
    const UPTIME_EXT = OLTAttractor::UPTIME_EXT;
    const TEMPERATURE_PATH = OLTAttractor::TEMPERATURE_PATH;
    const TEMPERATURE_EXT = OLTAttractor::TEMPERATURE_EXT;
    const MACDEVIDCACHE_PATH = OLTAttractor::MACDEVIDCACHE_PATH;
    const MACDEVIDCACHE_EXT = OLTAttractor::MACDEVIDCACHE_EXT;
    const ONUSIG_PATH = OLTAttractor::ONUSIG_PATH;

    /**
     * Other instance parameters
     */
    const SNMPCACHE = PONizer::SNMPCACHE;
    const SNMPPORT = PONizer::SNMPPORT;

    /**
     * Creates new PON poller/parser proto
     * 
     * @param array $oltParameters
     * @param array $snmpTemplates
     */
    public function __construct($oltParameters, $snmpTemplates) {
        $this->oltParameters = $oltParameters;
        $this->snmpTemplates = $snmpTemplates;
        $this->initSNMP();
        $this->initOltAttractor();
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
     * Inits current OLT data abstraction layer for further usage
     */
    protected function initOltAttractor() {
        $this->olt = new OLTAttractor($this->oltParameters['ID']);
    }

    /**
     * Sets current instance ONU offline signal level
     * 
     * @param int $level
     * 
     * @return void
     */
    public function setOfflineSignal($level) {
        $this->onuOfflineSignalLevel = $level;
    }

    /**
     * Main data collector method placeholder
     * 
     * @return void
     */
    public function collect() {
        /**
         * Ab esse ad posse valet, a posse ad esse non valet consequentia
         */
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     *
     * @return void
     */
    protected function signalParse($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $devIndex = trim($line[0]); // device index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'div') {
                            if ($snmpTemplate['OFFSET']) {
                                if (is_numeric($signalRaw)) {
                                    $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                                } else {
                                    $signalRaw = 'Fail';
                                }
                            }
                        }
                    }
                    $sigTmp[$devIndex] = $signalRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
                        //signal history preprocessing
                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                        }

                        //saving each ONU signal history
                        $this->olt->writeSignalHistory($eachMac, $signal);
                    }
                }

                //writing signals cache
                $this->olt->writeSignals($result);

                // saving macindex as MAC => devID
                $macTmp = array_flip($macTmp);
                $this->olt->writeMacIndex($macTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function distanceParse($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

//mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }

                //saving distance cache
                $this->olt->writeDistances($result);

                //saving ONU cache
                $this->olt->writeOnuCache($onuTmp);
            }
        }
    }

    /**
     * Parses BDCom uptime data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $uptimeRaw
     *
     * @return void
     */
    protected function uptimeParse($oltid, $uptimeRaw) {
        if (!empty($uptimeRaw)) {
            $uptimeRaw = explode(')', $uptimeRaw);
            $uptimeRaw = $uptimeRaw[1];
            $this->olt->writeUptime($uptimeRaw);
        }
    }

    /**
     * Parses BDCom temperature data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $tempRaw
     *
     * @return void
     */
    protected function temperatureParse($oltid, $tempRaw) {
        if (!empty($tempRaw)) {
            $tempRaw = explode(':', $tempRaw);
            $tempRaw = $tempRaw[1];
            $this->olt->writeTemperature($tempRaw);
        }
    }

    /**
     * Replaces standard 4-line routine with snmpwalking and removing OID and VALUE portions and returns an array of cleared values
     *
     * @param string $snmpIPPORT
     * @param string $snmpCommunity
     * @param string $snmpOID
     * @param string $removeVALUE
     * @param bool   $snmpCacheON
     *
     * @return array
     */
    protected function walkCleared($snmpIPPORT, $snmpCommunity, $snmpOID, $removeVALUE = '', $snmpCacheON = false) {
        $oidIndex = $this->snmp->walk($snmpIPPORT, $snmpCommunity, $snmpOID, $snmpCacheON);
        $oidIndex = trimSNMPOutput($oidIndex, $snmpOID . '.', $removeVALUE, true);

        return ($oidIndex);
    }
}
