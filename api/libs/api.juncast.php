<?php

/**
 * JuniperMX NAS COA/POD casting implementation
 */
class JunCast {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Contains default path and options for radclient
     *
     * @var string
     */
    protected $radclienPath = '/usr/local/bin/radclient -r 3 -t 1';

    /**
     * Contains path to printf
     *
     * @var string
     */
    protected $printfPath = '/usr/bin/printf';

    /**
     * Contains path to system sudo command
     *
     * @var string
     */
    protected $sudoPath = '/usr/local/bin/sudo';

    /**
     * Default remote radclient port
     *
     * @var int
     */
    protected $remotePort = 3799;

    /**
     * Debug mode
     */
    const DEBUG = false;

    public function __construct() {
        $this->loadSystemConfigs();
        $this->setOptions();
    }

    /**
     * Loads system alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadSystemConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Sets some options here
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->billCfg['SUDO'])) {
            $this->sudoPath = $this->billCfg['SUDO'];
        }
        if (isset($this->altCfg['JUNGEN_RADCLIENT'])) {
            $this->radclienPath = $this->altCfg['JUNGEN_RADCLIENT'];
        }
    }

    /**
     * Transforms mac from xx:xx:xx:xx:xx:xx format to xxxx.xxxx.xxxx
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMac($mac) {
        $result = implode(".", str_split(str_replace(":", "", $mac), 4));
        return ($result);
    }

    /**
     * Terminates user session on associated NAS
     * 
     * @param string $login
     * 
     * @return string
     */
    public function terminateUser($login) {
        $result = '';
        $login = trim($login);
        $userIp = zb_UserGetIP($login);
        if (!empty($userIp)) {
            $userMac = zb_MultinetGetMAC($userIp);
            if (!empty($userMac)) {
                $query_nas = "SELECT `nasip` FROM `nas` WHERE `netid` IN (SELECT `netid` FROM `nethosts` WHERE `ip` = '" . $userIp . "')";
                $nasIp = simple_query($query_nas);
                if (!empty($nasIp)) {
                    $nasIp = $nasIp['nasip'];
                    $query_nas_key = "SELECT `secret` from `jun_clients` WHERE `nasname`='" . $nasIp . "';";
                    $nasSecret = simple_query($query_nas_key);
                    if (!empty($nasSecret)) {
                        $nasSecret = $nasSecret['secret'];
                        $userNameAsMac = $this->transformMac($userMac);
                        $command = $this->printfPath . ' "User-Name = ' . $userNameAsMac . '" | ' . $this->sudoPath . ' ' . $this->radclienPath . ' ' . $nasIp . ':' . $this->remotePort . ' disconnect ' . $nasSecret;
                        if (self::DEBUG) {
                            deb($command);
                        }
                        $result = shell_exec($command);
                    }
                }
            }
        }
    }

    /**
     * Sets user as unblocked at NAS
     * 
     * @param string $login
     * 
     * @return string
     */
    public function unblockUser($login) {
        $result = '';
        $login = trim($login);
        $userIp = zb_UserGetIP($login);
        if (!empty($userIp)) {
            $userMac = zb_MultinetGetMAC($userIp);
            if (!empty($userMac)) {
                $query_nas = "SELECT `nasip` FROM `nas` WHERE `netid` IN (SELECT `netid` FROM `nethosts` WHERE `ip` = '" . $userIp . "')";
                $nasIp = simple_query($query_nas);
                if (!empty($nasIp)) {
                    $nasIp = $nasIp['nasip'];
                    $query_nas_key = "SELECT `secret` from `jun_clients` WHERE `nasname`='" . $nasIp . "';";
                    $nasSecret = simple_query($query_nas_key);
                    if (!empty($nasSecret)) {
                        $nasSecret = $nasSecret['secret'];
                        $userNameAsMac = $this->transformMac($userMac);
                        $command = $this->printfPath . ' "User-Name = ' . $userNameAsMac . '\nUnisphere-Service-Deactivate:3 -= block" | ' . $this->sudoPath . ' ' . $this->radclienPath . ' ' . $nasIp . ':' . $this->remotePort . ' coa ' . $nasSecret;
                        if (self::DEBUG) {
                            deb($command);
                        }
                        $result = shell_exec($command);
                    }
                }
            }
        }
    }

    /**
     * Sets user blocked at NAS
     * 
     * @param string $login
     * 
     * @return string
     */
    public function blockUser($login) {
        $result = '';
        $login = trim($login);
        $userIp = zb_UserGetIP($login);
        if (!empty($userIp)) {
            $userMac = zb_MultinetGetMAC($userIp);
            if (!empty($userMac)) {
                $query_nas = "SELECT `nasip` FROM `nas` WHERE `netid` IN (SELECT `netid` FROM `nethosts` WHERE `ip` = '" . $userIp . "')";
                $nasIp = simple_query($query_nas);
                if (!empty($nasIp)) {
                    $nasIp = $nasIp['nasip'];
                    $query_nas_key = "SELECT `secret` from `jun_clients` WHERE `nasname`='" . $nasIp . "';";
                    $nasSecret = simple_query($query_nas_key);
                    if (!empty($nasSecret)) {
                        $nasSecret = $nasSecret['secret'];
                        $userNameAsMac = $this->transformMac($userMac);
                        $command = $this->printfPath . ' "User-Name = ' . $userNameAsMac . '\nUnisphere-Service-Activate:3 += block" | ' . $this->sudoPath . ' ' . $this->radclienPath . ' ' . $nasIp . ':' . $this->remotePort . ' coa ' . $nasSecret;
                        if (self::DEBUG) {
                            deb($command);
                        }
                        $result = shell_exec($command);
                    }
                }
            }
        }
    }

}
