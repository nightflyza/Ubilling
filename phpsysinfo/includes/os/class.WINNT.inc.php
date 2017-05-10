<?php
/**
 * WINNT System Class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI WINNT OS class
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.WINNT.inc.php 699 2012-09-15 11:57:13Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * WINNT sysinfo class
 * get all the required information from WINNT systems
 * information are retrieved through the WMI interface
 *
 * @category  PHP
 * @package   PSI WINNT OS class
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class WINNT extends OS
{
    /**
     * holds the data from WMI Win32_OperatingSystem
     *
     * @var array
     */
    private $_Win32_OperatingSystem = null;

    /**
     * holds the data from WMI Win32_ComputerSystem
     *
     * @var array
     */
    private $_Win32_ComputerSystem = null;

    /**
     * holds the data from WMI Win32_Processor
     *
     * @var array
     */
    private $_Win32_Processor = null;

    /**
     * holds the data from systeminfo command
     *
     * @var string
     */
    private $_systeminfo = null;

    /**
     * holds the COM object that we pull all the WMI data from
     *
     * @var Object
     */
    private $_wmi = null;

    /**
     * holds all devices, which are in the system
     *
     * @var array
     */
    private $_wmidevices;

    /**
     * store language encoding of the system to convert some output to utf-8
     *
     * @var string
     */
    private $_codepage = null;

    /**
     * store language of the system
     *
     * @var string
     */
    private $_syslang = null;

    /**
     * reads the data from WMI Win32_OperatingSystem
     *
     * @var array
     */
    private function _get_Win32_OperatingSystem()
    {
        if ($this->_Win32_OperatingSystem === null) $this->_Win32_OperatingSystem = CommonFunctions::getWMI($this->_wmi, 'Win32_OperatingSystem', array('CodeSet', 'OSLanguage', 'LastBootUpTime', 'LocalDateTime', 'Version', 'ServicePackMajorVersion', 'Caption', 'OSArchitecture', 'TotalVisibleMemorySize', 'FreePhysicalMemory'));
        return $this->_Win32_OperatingSystem;
    }

    /**
     * reads the data from WMI Win32_ComputerSystem
     *
     * @var array
     */
    private function _get_Win32_ComputerSystem()
    {
        if ($this->_Win32_ComputerSystem === null) $this->_Win32_ComputerSystem = CommonFunctions::getWMI($this->_wmi, 'Win32_ComputerSystem', array('Name', 'Manufacturer', 'Model'));
        return $this->_Win32_ComputerSystem;
    }

    /**
     * reads the data from WMI Win32_Processor
     *
     * @var array
     */
    private function _get_Win32_Processor()
    {
        if ($this->_Win32_Processor === null) $this->_Win32_Processor = CommonFunctions::getWMI($this->_wmi, 'Win32_Processor', array('LoadPercentage', 'AddressWidth', 'Name', 'L2CacheSize', 'CurrentClockSpeed', 'ExtClock', 'NumberOfCores', 'MaxClockSpeed'));
        return $this->_Win32_Processor;
    }

    /**
     * reads the data from systeminfo
     *
     * @var string
     */
    private function _get_systeminfo()
    {
        if ($this->_systeminfo === null) CommonFunctions::executeProgram('systeminfo', '', $this->_systeminfo, false);
        return $this->_systeminfo;
    }

    /**
     * build the global Error object and create the WMI connection
     */
    public function __construct()
    {
        parent::__construct();
        try {
            // initialize the wmi object
            $objLocator = new COM('WbemScripting.SWbemLocator');
            $this->_wmi = $objLocator->ConnectServer('', 'root\CIMv2');
        } catch (Exception $e) {
            $this->error->addError("WMI connect error", "PhpSysInfo can not connect to the WMI interface for security reasons.\nCheck an authentication mechanism for the directory where phpSysInfo is installed.");
        }
        $this->_getCodeSet();
    }

    /**
     * store the codepage of the os for converting some strings to utf-8
     *
     * @return void
     */
    private function _getCodeSet()
    {
        $buffer = $this->_get_Win32_OperatingSystem();
        if (!$buffer) {
            if (CommonFunctions::executeProgram('reg', 'query HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Nls\\CodePage /v ACP', $strBuf, false) && (strlen($strBuf) > 0) && preg_match("/^\s*ACP\s+REG_SZ\s+(\S+)\s*$/mi", $strBuf, $buffer2)) {
                $buffer[0]['CodeSet'] = $buffer2[1];
            }
            if (CommonFunctions::executeProgram('reg', 'query HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Nls\\Language /v Default', $strBuf, false) && (strlen($strBuf) > 0) && preg_match("/^\s*Default\s+REG_SZ\s+(\S+)\s*$/mi", $strBuf, $buffer2)) {
                $buffer[0]['OSLanguage'] = hexdec($buffer2[1]);
            }
        }
        if ($buffer && isset($buffer[0])) {
            if (isset($buffer[0]['CodeSet'])) {
                $this->_codepage = 'windows-'.$buffer[0]['CodeSet'];
            }
            if (isset($buffer[0]['OSLanguage'])) {
                $lang = "";
                if (is_readable(APP_ROOT.'/data/languages.ini') && ($langdata = @parse_ini_file(APP_ROOT.'/data/languages.ini', true))) {
                    if (isset($langdata['WINNT'][$buffer[0]['OSLanguage']])) {
                        $lang = $langdata['WINNT'][$buffer[0]['OSLanguage']];
                    }
                }
                if ($lang == "") {
                    $lang = 'Unknown';
                }
                $this->_syslang = $lang.' ('.$buffer[0]['OSLanguage'].')';
            }
        }
    }

    /**
     * retrieve different device types from the system based on selector
     *
     * @param string $strType type of the devices that should be returned
     *
     * @return array list of devices of the specified type
     */
    private function _devicelist($strType)
    {
        if (empty($this->_wmidevices)) {
            $this->_wmidevices = CommonFunctions::getWMI($this->_wmi, 'Win32_PnPEntity', array('Name', 'PNPDeviceID'));
        }
        $list = array();
        foreach ($this->_wmidevices as $device) {
            if (substr($device['PNPDeviceID'], 0, strpos($device['PNPDeviceID'], "\\") + 1) == ($strType."\\")) {
                $list[] = $device['Name'];
            }
        }

        return $list;
    }

    /**
     * Host Name
     *
     * @return void
     */
    private function _hostname()
    {
        if (PSI_USE_VHOST === true) {
            if ($hnm = getenv('SERVER_NAME')) $this->sys->setHostname($hnm);
        } else {
            $buffer = $this->_get_Win32_ComputerSystem();
            if (!$buffer && CommonFunctions::executeProgram('reg', 'query HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\ComputerName\\ActiveComputerName /v ComputerName', $strBuf, false) && (strlen($strBuf) > 0) && preg_match("/^\s*ComputerName\s+REG_SZ\s+(\S+)\s*$/mi", $strBuf, $buffer2)) {
                    $buffer[0]['Name'] = $buffer2[1];
            }
            if ($buffer) {
                $result = $buffer[0]['Name'];
                $ip = gethostbyname($result);
                if ($ip != $result) {
                    $long = ip2long($ip);
                    if (($long >= 167772160 && $long <= 184549375) ||
                        ($long >= -1408237568 && $long <= -1407188993) ||
                        ($long >= -1062731776 && $long <= -1062666241) ||
                        ($long >= 2130706432 && $long <= 2147483647) || $long == -1) {
                        $this->sys->setHostname($result); //internal ip
                    } else {
                        $this->sys->setHostname(gethostbyaddr($ip));
                    }
                }
            } else {
                if ($hnm = getenv('COMPUTERNAME')) $this->sys->setHostname($hnm);
            }
        }
    }

    /**
     * UpTime
     * time the system is running
     *
     * @return void
     */
    private function _uptime()
    {
        $result = 0;
        date_default_timezone_set('UTC');
        $buffer = $this->_get_Win32_OperatingSystem();
        if ($buffer) {
            $byear = intval(substr($buffer[0]['LastBootUpTime'], 0, 4));
            $bmonth = intval(substr($buffer[0]['LastBootUpTime'], 4, 2));
            $bday = intval(substr($buffer[0]['LastBootUpTime'], 6, 2));
            $bhour = intval(substr($buffer[0]['LastBootUpTime'], 8, 2));
            $bminute = intval(substr($buffer[0]['LastBootUpTime'], 10, 2));
            $bseconds = intval(substr($buffer[0]['LastBootUpTime'], 12, 2));
            $lyear = intval(substr($buffer[0]['LocalDateTime'], 0, 4));
            $lmonth = intval(substr($buffer[0]['LocalDateTime'], 4, 2));
            $lday = intval(substr($buffer[0]['LocalDateTime'], 6, 2));
            $lhour = intval(substr($buffer[0]['LocalDateTime'], 8, 2));
            $lminute = intval(substr($buffer[0]['LocalDateTime'], 10, 2));
            $lseconds = intval(substr($buffer[0]['LocalDateTime'], 12, 2));
            $boottime = mktime($bhour, $bminute, $bseconds, $bmonth, $bday, $byear);
            $localtime = mktime($lhour, $lminute, $lseconds, $lmonth, $lday, $lyear);
            $result = $localtime - $boottime;
            $this->sys->setUptime($result);
        }
    }

    /**
     * Number of Users
     *
     * @return void
     */
    protected function _users()
    {
        if (CommonFunctions::executeProgram('quser', '', $strBuf, false) && (strlen($strBuf) > 0)) {
                $lines = preg_split('/\n/', $strBuf);
                $users = count($lines)-1;
        } else {
            $users = 0;
            $buffer = CommonFunctions::getWMI($this->_wmi, 'Win32_Process', array('Caption'));
            foreach ($buffer as $process) {
                if (strtoupper($process['Caption']) == strtoupper('explorer.exe')) {
                    $users++;
                }
            }
        }
        $this->sys->setUsers($users);
    }

    /**
     * Distribution
     *
     * @return void
     */
    private function _distro()
    {
        $buffer = $this->_get_Win32_OperatingSystem();
        if ($buffer) {
            $kernel = $buffer[0]['Version'];
            if ($buffer[0]['ServicePackMajorVersion'] > 0) {
                $kernel .= ' SP'.$buffer[0]['ServicePackMajorVersion'];
            }
            if (isset($buffer[0]['OSArchitecture']) && preg_match("/^(\d+)/", $buffer[0]['OSArchitecture'], $bits)) {
                $this->sys->setKernel($kernel.' ('.$bits[1].'-bit)');
            } elseif (($allCpus = $this->_get_Win32_Processor()) && isset($allCpus[0]['AddressWidth'])) {
                $this->sys->setKernel($kernel.' ('.$allCpus[0]['AddressWidth'].'-bit)');
            } else {
                $this->sys->setKernel($kernel);
            }
            $this->sys->setDistribution($buffer[0]['Caption']);

            if ((($kernel[1] == '.') && ($kernel[0] <5)) || (substr($kernel, 0, 4) == '5.0.'))
                $icon = 'Win2000.png';
            elseif ((substr($kernel, 0, 4) == '6.0.') || (substr($kernel, 0, 4) == '6.1.'))
                $icon = 'WinVista.png';
            elseif ((substr($kernel, 0, 4) == '6.2.') || (substr($kernel, 0, 4) == '6.3.') || (substr($kernel, 0, 4) == '6.4.') || (substr($kernel, 0, 5) == '10.0.'))
                $icon = 'Win8.png';
            else
                $icon = 'WinXP.png';
            $this->sys->setDistributionIcon($icon);
        } elseif (CommonFunctions::executeProgram('cmd', '/c ver 2>nul', $ver_value, false)) {
                if (preg_match("/ReactOS\r?\nVersion\s+(.+)/", $ver_value, $ar_temp)) {
                    $this->sys->setDistribution("ReactOS");
                    $this->sys->setKernel($ar_temp[1]);
                    $this->sys->setDistributionIcon('ReactOS.png');
                    $this->_wmi = false; // No WMI info on ReactOS yet
                } elseif (preg_match("/^(Microsoft [^\[]*)\s*\[\D*\s*(.+)\]/", $ver_value, $ar_temp)) {
                    if (CommonFunctions::executeProgram('reg', 'query "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion" /v ProductName 2>&1', $strBuf, false) && (strlen($strBuf) > 0) && preg_match("/^\s*ProductName\s+REG_SZ\s+(.+)\s*$/mi", $strBuf, $buffer2)) {
                        if (preg_match("/^Microsoft /", $buffer2[1])) {
                            $this->sys->setDistribution($buffer2[1]);
                        } else {
                            $this->sys->setDistribution("Microsoft ".$buffer2[1]);
                        }
                    } else {
                        $this->sys->setDistribution($ar_temp[1]);
                    }
                    $kernel = $ar_temp[2];
                    $this->sys->setKernel($kernel);
                    if ((($kernel[1] == '.') && ($kernel[0] <5)) || (substr($kernel, 0, 4) == '5.0.'))
                        $icon = 'Win2000.png';
                    elseif ((substr($kernel, 0, 4) == '6.0.') || (substr($kernel, 0, 4) == '6.1.'))
                        $icon = 'WinVista.png';
                    elseif ((substr($kernel, 0, 4) == '6.2.') || (substr($kernel, 0, 4) == '6.3.') || (substr($kernel, 0, 4) == '6.4.') || (substr($kernel, 0, 5) == '10.0.'))
                        $icon = 'Win8.png';
                    else
                        $icon = 'WinXP.png';
                    $this->sys->setDistributionIcon($icon);
                } else {
                    $this->sys->setDistribution("WinNT");
                    $this->sys->setDistributionIcon('Win2000.png');
                }
        } else {
            $this->sys->setDistribution("WinNT");
            $this->sys->setDistributionIcon('Win2000.png');
        }
    }

    /**
     * Processor Load
     * optionally create a loadbar
     *
     * @return void
     */
    private function _loadavg()
    {
        $loadavg = "";
        $sum = 0;
        $buffer = $this->_get_Win32_Processor();
        if ($buffer) {
            foreach ($buffer as $load) {
                $value = $load['LoadPercentage'];
                $loadavg .= $value.' ';
                $sum += $value;
            }
            $this->sys->setLoad(trim($loadavg));
            if (PSI_LOAD_BAR) {
                $this->sys->setLoadPercent($sum / count($buffer));
            }
        }
    }

    /**
     * CPU information
     *
     * @return void
     */
    private function _cpuinfo()
    {
        $allCpus = $this->_get_Win32_Processor();
        if (!$allCpus) {
            if (CommonFunctions::executeProgram('reg', 'query HKEY_LOCAL_MACHINE\\HARDWARE\\DESCRIPTION\\System\\CentralProcessor /s', $strBuf, false) && (strlen($strBuf) > 0)) {
                $lines = preg_split('/\n/', $strBuf);
                $coreCount = -1;
                foreach ($lines as $line) {
                    if (preg_match('/^HKEY_LOCAL_MACHINE\\\\HARDWARE\\\\DESCRIPTION\\\\System\\\\CentralProcessor\\\\\d+/i', $line)) {
                        $coreCount++;
                    } elseif ($coreCount >= 0) {
                        if (preg_match("/^\s*ProcessorNameString\s+REG_SZ\s+(.+)\s*$/i", $line, $buffer2)) {
                            $allCpus[$coreCount]['Name'] = $buffer2[1];
                        } elseif (preg_match("/^\s*~MHz\s+REG_DWORD\s+(0x.+)\s*$/i", $line, $buffer2)) {
                            $allCpus[$coreCount]['CurrentClockSpeed'] = hexdec($buffer2[1]);
                        } 
                    }
                }
            }
        }
        foreach ($allCpus as $oneCpu) {
            $coreCount = 1;
            if (isset($oneCpu['NumberOfCores'])) {
                $coreCount = $oneCpu['NumberOfCores'];
            }
            for ($i = 0; $i < $coreCount; $i++) {
                $cpu = new CpuDevice();
                if (isset($oneCpu['Name'])) $cpu->setModel($oneCpu['Name']);
                if (isset($oneCpu['L2CacheSize'])) $cpu->setCache($oneCpu['L2CacheSize'] * 1024);
                if (isset($oneCpu['CurrentClockSpeed'])) {
                    $cpu->setCpuSpeed($oneCpu['CurrentClockSpeed']);
                    if (isset($oneCpu['MaxClockSpeed']) && ($oneCpu['CurrentClockSpeed'] < $oneCpu['MaxClockSpeed'])) $cpu->setCpuSpeedMax($oneCpu['MaxClockSpeed']);
                }
                if (isset($oneCpu['ExtClock'])) $cpu->setBusSpeed($oneCpu['ExtClock']);
                $this->sys->setCpus($cpu);
            }
        }
    }

    /**
     * Machine information
     *
     * @return void
     */
    private function _machine()
    {
        $buffer = $this->_get_Win32_ComputerSystem();
        if ($buffer) {
            $buf = "";
            if (isset($buffer[0]['Manufacturer'])) {
                $buf .= ' '.$buffer[0]['Manufacturer'];
            }
            if (isset($buffer[0]['Model'])) {
                $buf .= ' '.$buffer[0]['Model'];
            }
            if (trim($buf) != "") {
                $this->sys->setMachine(trim($buf));
            }
        }
    }

    /**
     * Hardwaredevices
     *
     * @return void
     */
    private function _hardware()
    {
        foreach ($this->_devicelist('PCI') as $pciDev) {
            $dev = new HWDevice();
            $dev->setName($pciDev);
            $this->sys->setPciDevices($dev);
        }

        foreach ($this->_devicelist('IDE') as $ideDev) {
            $dev = new HWDevice();
            $dev->setName($ideDev);
            $this->sys->setIdeDevices($dev);
        }

        foreach ($this->_devicelist('SCSI') as $scsiDev) {
            $dev = new HWDevice();
            $dev->setName($scsiDev);
            $this->sys->setScsiDevices($dev);
        }

        foreach ($this->_devicelist('USB') as $usbDev) {
            $dev = new HWDevice();
            $dev->setName($usbDev);
            $this->sys->setUsbDevices($dev);
        }
    }

    /**
     * Network devices
     *
     * @return void
     */
    private function _network()
    {
        if ($this->_wmi) {
            $buffer = $this->_get_Win32_OperatingSystem();
            if ($buffer && isset($buffer[0]) && isset($buffer[0]['Version']) && preg_match('/^(\d+)\.(\d+)/', $buffer[0]['Version'], $version)
                &&(($version[1] == 6) && ($version[2] >= 2)) || ($version[1] > 6)) { // minimal windows 2012 or windows 8
                $allDevices = CommonFunctions::getWMI($this->_wmi, 'Win32_PerfRawData_Tcpip_NetworkAdapter', array('Name', 'BytesSentPersec', 'BytesTotalPersec', 'BytesReceivedPersec', 'PacketsReceivedErrors', 'PacketsReceivedDiscarded', 'CurrentBandwidth'));
            } else {
                $allDevices = CommonFunctions::getWMI($this->_wmi, 'Win32_PerfRawData_Tcpip_NetworkInterface', array('Name', 'BytesSentPersec', 'BytesTotalPersec', 'BytesReceivedPersec', 'PacketsReceivedErrors', 'PacketsReceivedDiscarded', 'CurrentBandwidth'));
            }
            /*if (!$allDevices && CommonFunctions::executeProgram('ipconfig', '/all', $devicesbuf, false) && (trim($devicesbuf) !== "")) {
                $netdevices = preg_split('/^(Ethernet)|(Wireless)|(Tunnel) [^\n]+\n\r\n/m', $devicesbuf, -1, PREG_SPLIT_NO_EMPTY);
                if (sizeof($netdevices)>1)  foreach ($netdevices as $devnr=>$netdevice) if ($devnr > 0) {
                    $bufe = preg_split("/\r\n   /", trim($netdevice), -1, PREG_SPLIT_NO_EMPTY);
                    $notdiss = true;
                    foreach ($bufe as $buf) {
                        list($key, $value) = preg_split('/[\s\.]+: /', $buf, 2);
                        if (($key == "Media State") && (trim($value) == "Media disconnected")) {
                            $notdiss = false;
                        } elseif ($notdiss && ($key == "Description") && (trim($value) !== "")) {
                            $allDevices[] = array('Name'=>trim($value), 'BytesSentPersec'=>0, 'BytesTotalPersec'=>0, 'BytesReceivedPersec'=>0, 'PacketsReceivedErrors'=>0, 'PacketsReceivedDiscarded'=>0, 'CurrentBandwidth'=>0); 
                        }
                    }
                }
            }*/
            if ($allDevices) {
                $aliases = array();
                if (preg_match('/^windows-(\d+)$/', $this->_codepage, $codepage) 
                    && CommonFunctions::executeProgram('cmd', '/c chcp '.$codepage[1].' && reg query HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Network /v Name /s', $strBuf, false) 
                    && (strlen($strBuf) > 0) 
                    && preg_match_all('/^HKEY_LOCAL_MACHINE\\\\SYSTEM\\\\CurrentControlSet\\\\Control\\\\Network\\\\{4D36E972-E325-11CE-BFC1-08002BE10318}\\\\({[^{]+})\\\\Connection\r\n\s+Name\s+REG_SZ\s+([^\r\n]+)/mi', $strBuf, $buffer)) {
                    for ($i = 0; $i < sizeof($buffer[0]); $i++) {
                        if (!isset($aliases[$buffer[2][$i]])) { // duplicate checking
                            $aliases[$buffer[2][$i]] = $buffer[1][$i];
                        } else {
                            $aliases[$buffer[2][$i]] = "";
                        }
                    }
                }
                $allNetworkAdapterConfigurations = CommonFunctions::getWMI($this->_wmi, 'Win32_NetworkAdapterConfiguration', array('Description', 'MACAddress', 'IPAddress', 'SettingID'));
                foreach ($allDevices as $device) {
                    $dev = new NetDevice();
                    $name = $device['Name'];
                    $macexist = false;
                    if (($aliases) && isset($aliases[$name]) && ($aliases[$name] !== "")) {
                        foreach ($allNetworkAdapterConfigurations as $NetworkAdapterConfiguration) {
                            if ($aliases[$name]==$NetworkAdapterConfiguration['SettingID']) {
                                $dev->setName($NetworkAdapterConfiguration['Description']);
                                if (trim($NetworkAdapterConfiguration['MACAddress']) !== "") $macexist = true;
                                if (defined('PSI_SHOW_NETWORK_INFOS') && PSI_SHOW_NETWORK_INFOS) {
                                    if ((!defined('PSI_HIDE_NETWORK_MACADDR') || !PSI_HIDE_NETWORK_MACADDR)
                                       && (trim($NetworkAdapterConfiguration['MACAddress']) !== "")) $dev->setInfo(preg_replace('/:/', '-', strtoupper($NetworkAdapterConfiguration['MACAddress'])));
                                    if (isset($NetworkAdapterConfiguration['IPAddress']))
                                        foreach($NetworkAdapterConfiguration['IPAddress'] as $ipaddres)
                                            if (($ipaddres != "0.0.0.0") && ($ipaddres != "::") && !preg_match('/^fe80::/i', $ipaddres))
                                                $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').strtolower($ipaddres));
                                }

                                break;
                            }
                        }
                    }
                    if ($dev->getName() == "") { //no alias or no alias description
                        $cname=preg_replace('/[^A-Za-z0-9]/', '_', $name); //convert to canonical
                        if (preg_match('/^isatap\.({[A-Fa-f0-9\-]*})/', $name))
                            $name="Microsoft ISATAP Adapter";
                        elseif (preg_match('/\s-\s([^-]*)$/', $name, $ar_name))
                            $name=substr($name, 0, strlen($name)-strlen($ar_name[0]));
                        $dev->setName($name);

                        foreach ($allNetworkAdapterConfigurations as $NetworkAdapterConfiguration) {
                            if (preg_replace('/[^A-Za-z0-9]/', '_', $NetworkAdapterConfiguration['Description']) === $cname) {
                                $macexist = $macexist || (trim($NetworkAdapterConfiguration['MACAddress']) !== "");

                                if (defined('PSI_SHOW_NETWORK_INFOS') && PSI_SHOW_NETWORK_INFOS) {
                                    if ($dev->getInfo() !== null) {
                                        $dev->setInfo(''); //multiple with the same name
                                    } else {
                                        if ((!defined('PSI_HIDE_NETWORK_MACADDR') || !PSI_HIDE_NETWORK_MACADDR) 
                                           && (trim($NetworkAdapterConfiguration['MACAddress']) !== "")) $dev->setInfo(preg_replace('/:/', '-', strtoupper($NetworkAdapterConfiguration['MACAddress'])));
                                        if (isset($NetworkAdapterConfiguration['IPAddress']))
                                            foreach($NetworkAdapterConfiguration['IPAddress'] as $ipaddres)
                                                if (($ipaddres != "0.0.0.0") && !preg_match('/^fe80::/i', $ipaddres))
                                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').strtolower($ipaddres));
                                    }
                                }
                            }
                        }
                    }
                    if ($macexist
                        || ($device['CurrentBandwidth'] > 0)
                        || ($device['BytesTotalPersec'] != 0)
                        || ($device['BytesSentPersec'] != 0)
                        || ($device['BytesReceivedPersec'] != 0)
                        || ($device['PacketsReceivedErrors'] != 0)
                        || ($device['PacketsReceivedDiscarded'] != 0)) { // hide unused
                        if (defined('PSI_SHOW_NETWORK_INFOS') && PSI_SHOW_NETWORK_INFOS) {
                            if (($speedinfo = $device['CurrentBandwidth']) >= 1000000) {
                                if ($speedinfo > 1000000000) {
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').($speedinfo/1000000000)."Gb/s");
                                } else {
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').($speedinfo/1000000)."Mb/s");
                                }
                            }
                        }

                        // http://msdn.microsoft.com/library/default.asp?url=/library/en-us/wmisdk/wmi/win32_perfrawdata_tcpip_networkinterface.asp
                        // there is a possible bug in the wmi interfaceabout uint32 and uint64: http://www.ureader.com/message/1244948.aspx, so that
                        // magative numbers would occour, try to calculate the nagative value from total - positive number
                        $txbytes = $device['BytesSentPersec'];
                        $rxbytes = $device['BytesReceivedPersec'];
                        if (($txbytes < 0) && ($rxbytes < 0)) {
                            $txbytes += 4294967296;
                            $rxbytes += 4294967296;
                        } elseif ($txbytes < 0) {
                            if ($device['BytesTotalPersec'] > $rxbytes)
                               $txbytes = $device['BytesTotalPersec'] - $rxbytes;
                            else
                               $txbytes += 4294967296;
                        } elseif ($rxbytes < 0) {
                            if ($device['BytesTotalPersec'] > $txbytes)
                               $rxbytes = $device['BytesTotalPersec'] - $txbytes;
                            else
                               $rxbytes += 4294967296;
                        }
                        $dev->setTxBytes($txbytes);
                        $dev->setRxBytes($rxbytes);
                        $dev->setErrors($device['PacketsReceivedErrors']);
                        $dev->setDrops($device['PacketsReceivedDiscarded']);

                        $this->sys->setNetDevices($dev);
                    }
                }
            } 
        } elseif (($buffer = $this->_get_systeminfo()) && preg_match('/^(\s+)\[\d+\]:.*\r\n\s+[^\s\[]/m', $buffer, $matches, PREG_OFFSET_CAPTURE)) {
            $netbuf = substr($buffer, $matches[0][1]);
            if (preg_match('/^[^\s]/m', $netbuf, $matches2, PREG_OFFSET_CAPTURE)) {
                $netbuf = substr($netbuf, 0, $matches2[0][1]);
            }
            $netstrs = preg_split('/^'.$matches[1][0].'\[\d+\]:/m', $netbuf, -1, PREG_SPLIT_NO_EMPTY);
            $devnr = 0;
            foreach ($netstrs as $netstr) {
                $netstrls = preg_split('/\r\n/', $netstr, -1, PREG_SPLIT_NO_EMPTY);
                if (sizeof($netstrls)>1) {
                    $dev = new NetDevice();
                    foreach ($netstrls as $nr=>$netstrl) {
                        if ($nr === 0) {
                            $name = trim($netstrl);
                            if ($name !== "") {
                                $dev->setName($name);
                            } else {
                                $dev->setName('dev'.$devnr);
                                $devnr++;
                            }
                        } elseif (preg_match('/\[\d+\]:\s+(.+)/', $netstrl, $netinfo)) {
                            $ipaddres = trim($netinfo[1]);
                            if (($ipaddres!="0.0.0.0") && !preg_match('/^fe80::/i', $ipaddres))
                                $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').strtolower($ipaddres));
                        }
                    }
                    $this->sys->setNetDevices($dev);
                }
            }
        }
    }

    /**
     * Physical memory information and Swap Space information
     *
     * @link http://msdn2.microsoft.com/En-US/library/aa394239.aspx
     * @link http://msdn2.microsoft.com/en-us/library/aa394246.aspx
     * @return void
     */
    private function _memory()
    {
        if ($this->_wmi) {
            $buffer = $this->_get_Win32_OperatingSystem();
            if ($buffer) {
                $this->sys->setMemTotal($buffer[0]['TotalVisibleMemorySize'] * 1024);
                $this->sys->setMemFree($buffer[0]['FreePhysicalMemory'] * 1024);
                $this->sys->setMemUsed($this->sys->getMemTotal() - $this->sys->getMemFree());
            }
            $buffer = CommonFunctions::getWMI($this->_wmi, 'Win32_PageFileUsage');
            foreach ($buffer as $swapdevice) {
                $dev = new DiskDevice();
                $dev->setName("SWAP");
                $dev->setMountPoint($swapdevice['Name']);
                $dev->setTotal($swapdevice['AllocatedBaseSize'] * 1024 * 1024);
                $dev->setUsed($swapdevice['CurrentUsage'] * 1024 * 1024);
                $dev->setFree($dev->getTotal() - $dev->getUsed());
                $dev->setFsType('swap');
                $this->sys->setSwapDevices($dev);
            }
        } elseif (($buffer = $this->_get_systeminfo()) && preg_match("/:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+\s([\d \xFF]+)\sMB\r\n/m", $buffer, $buffer2)) {
//           && (preg_match("/:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+:\s([\d \xFF]+)\sMB\r\n.+\s([\d \xFF]+)\sMB\r\n.*:\s+(\S+)\r\n/m", $buffer, $buffer2)) {
            $this->sys->setMemTotal(preg_replace('/(\s)|(\xFF)/', '', $buffer2[1]) * 1024 * 1024);
            $this->sys->setMemFree(preg_replace('/(\s)|(\xFF)/', '', $buffer2[2]) * 1024 * 1024);
            $this->sys->setMemUsed($this->sys->getMemTotal() - $this->sys->getMemFree());
        }
    }

    /**
     * filesystem information
     *
     * @return void
     */
    private function _filesystems()
    {
        $typearray = array('Unknown', 'No Root Directory', 'Removable Disk', 'Local Disk', 'Network Drive', 'Compact Disc', 'RAM Disk');
        $floppyarray = array('Unknown', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', '5 1/4 in.', 'Other', 'HD', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '5 1/4 in.', '3 1/2 in.', '3 1/2 in.', '8 in.');
        $buffer = CommonFunctions::getWMI($this->_wmi, 'Win32_LogicalDisk', array('Name', 'Size', 'FreeSpace', 'FileSystem', 'DriveType', 'MediaType'));
        foreach ($buffer as $filesystem) {
            $dev = new DiskDevice();
            $dev->setMountPoint($filesystem['Name']);
            $dev->setFsType($filesystem['FileSystem']);
            if ($filesystem['Size'] > 0) {
                $dev->setTotal($filesystem['Size']);
                $dev->setFree($filesystem['FreeSpace']);
                $dev->setUsed($filesystem['Size'] - $filesystem['FreeSpace']);
            }
            if ($filesystem['MediaType'] != "" && $filesystem['DriveType'] == 2) {
                $dev->setName($typearray[$filesystem['DriveType']]." (".$floppyarray[$filesystem['MediaType']].")");
            } else {
                $dev->setName($typearray[$filesystem['DriveType']]);
            }
            $this->sys->setDiskDevices($dev);
        }
        if (!$buffer && ($this->sys->getDistribution()=="ReactOS")) {
            // test for command 'free' on current disk
            if (CommonFunctions::executeProgram('cmd', '/c free 2>nul', $out_value, true)) {
                for ($letter='A'; $letter!='AA'; $letter++) if (CommonFunctions::executeProgram('cmd', '/c free '.$letter.': 2>nul', $out_value, false)) {
                    if (preg_match('/\n\s*([\d\.,\xFF]+).*\n\s*([\d\.,\xFF]+).*\n\s*([\d\.\,\xFF]+).*$/', $out_value, $out_dig)) {
                        $size = preg_replace('/(\.)|(,)|(\xFF)/', '', $out_dig[1]);
                        $used = preg_replace('/(\.)|(,)|(\xFF)/', '', $out_dig[2]);
                        $free = preg_replace('/(\.)|(,)|(\xFF)/', '', $out_dig[3]);
                        if ($used + $free == $size) {
                            $dev = new DiskDevice();
                            $dev->setMountPoint($letter.":");
                            $dev->setFsType('Unknown');
                            $dev->setName('Unknown');
                            $dev->setTotal($size);
                            $dev->setFree($free);
                            $dev->setUsed($used);
                            $this->sys->setDiskDevices($dev);
                        }
                    }
                }
            }
        }
    }

    /**
     * get os specific encoding
     *
     * @see OS::getEncoding()
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_codepage;
    }

    /**
     * get os specific language
     *
     * @see OS::getLanguage()
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->_syslang;
    }

    public function _processes()
    {
        $processes['*'] = 0;
        if (CommonFunctions::executeProgram('qprocess', '*', $strBuf, false) && (strlen($strBuf) > 0)) {
            $lines = preg_split('/\n/', $strBuf);
            $processes['*'] = (count($lines)-1) - 3 ; //correction for process "qprocess *"
        }
        if ($processes['*'] <= 0) {
            $buffer = CommonFunctions::getWMI($this->_wmi, 'Win32_Process', array('Caption'));
            $processes['*'] = count($buffer);
        }
        $processes[' '] = $processes['*'];
        $this->sys->setProcesses($processes);
    }


    /**
     * get the information
     *
     * @see PSI_Interface_OS::build()
     *
     * @return Void
     */
    public function build()
    {
        $this->_distro();
        if ($this->sys->getDistribution()=="ReactOS") {
            $this->error->addError("WARN", "The ReactOS version of phpSysInfo is a work in progress, some things currently don't work");
        }
        $this->_hostname();
        $this->_users();
        $this->_machine();
        $this->_uptime();
        $this->_cpuinfo();
        $this->_network();
        $this->_hardware();
        $this->_filesystems();
        $this->_memory();
        $this->_loadavg();
        $this->_processes();
    }
}
