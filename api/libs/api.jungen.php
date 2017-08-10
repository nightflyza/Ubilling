<?php

class JunGen {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of all available users as login=>userdata
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains all of available ip=>mac assigns
     *
     * @var array
     */
    protected $allMacs = array();

    /**
     * Contains preprocessed speeds as login=>up/down in bits/s
     *
     * @var array
     */
    protected $allSpeeds = array();

    /**
     * Radius check table
     *
     * @var string
     */
    protected $checkTable = 'jun_check';

    /**
     * Radius attributes reply table
     *
     * @var string
     */
    protected $replyTable = 'jun_reply';

    /**
     * Default NAS password. May be exported from JUNGEN_KEY option
     *
     * @var string
     */
    protected $defaultMxPass = 'mxBras';

    /**
     * Contains default session timeout in seconds
     *
     * @var int
     */
    protected $defaultSessionTimeout = 3600;

    /**
     * Contains default offset for user speed normalisation
     *
     * @var int
     */
    protected $speedOffset = 1024;

    /**
     * Contains all available auth attributes pairs as username=>attr=>data
     *
     * @var array
     */
    protected $allCheck = array();

    /**
     * Juniper NAS users password option name
     */
    const OPTION_PASSWORD = 'JUNGEN_KEY';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadMacs();
        $this->loadSpeeds();
        $this->loadChecks();
    }

    /**
     * Loads system alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some options
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg[self::OPTION_PASSWORD])) {
            if (!empty($this->altCfg[self::OPTION_PASSWORD])) {
                $this->defaultMxPass = mysql_real_escape_string(trim($this->altCfg[self::OPTION_PASSWORD]));
            }
        }
    }

    /**
     * Loads available users from database into protected prof for further usage
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Loads ip->mac pairs from database
     * 
     * @return void
     */
    protected function loadMacs() {
        $this->allMacs = zb_UserGetAllIpMACs();
    }

    /**
     * Loads users overrided/tariffs speeds from database and do some preprocessing
     * 
     * @return void
     */
    protected function loadSpeeds() {
        $tariffSpeeds = zb_TariffGetAllSpeeds();
        $speedOverrides = array();
        $speedOverrides_q = "SELECT * from `userspeeds` WHERE `speed` NOT LIKE '0';";
        $rawOverrides = simple_queryall($speedOverrides_q);
        if (!empty($rawOverrides)) {
            foreach ($rawOverrides as $io => $each) {
                $speedOverrides[$each['login']] = ($each['speed'] / $this->speedOffset);
            }
        }
//Скажем нет обратному захвату серотонина
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $login => $userData) {
                if (isset($tariffSpeeds[$userData['Tariff']])) {
                    if (!isset($speedOverrides[$login])) {
                        $this->allSpeeds[$login]['down'] = round(($tariffSpeeds[$userData['Tariff']]['speeddown'] * $this->speedOffset));
                        $this->allSpeeds[$login]['up'] = round(($tariffSpeeds[$userData['Tariff']]['speedup'] * $this->speedOffset));
                    } else {
                        $userSpeedOverride = round(($speedOverrides[$login] * $this->speedOffset));
                        $this->allSpeeds[$login]['down'] = $userSpeedOverride;
                        $this->allSpeeds[$login]['up'] = $userSpeedOverride;
                    }
                } else {
                    $this->allSpeeds[$login]['down'] = 0;
                    $this->allSpeeds[$login]['up'] = 0;
                }
            }
        }
    }

    /**
     * Loads all available data from auth table
     * 
     * @return void
     */
    protected function loadChecks() {
        $query = "select * from `" . $this->checkTable . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCheck[$each['username']]['attribute'][$each['attribute']] = $each['value'];
            }
        }
    }


    /**
     * Performs full or partial regeneration of all data in radius check table
     * 
     * @return void
     */
    protected function generateCheckAll() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                $regenFlag = false;
                $deleteFlag = false;
                if (isset($this->allMacs[$each['IP']])) {
                    $userMac = $this->allMacs[$each['IP']];

                    if (!isset($this->allCheck[$userMac])) {
                        $regenFlag = true;
                    } else {
                        if (isset($this->allCheck[$userMac]['attribute'])) {
                            if (isset($this->allCheck[$userMac]['attribute']['Cleartext-Password'])) {
                                if ($this->allCheck[$userMac]['attribute']['Cleartext-Password'] == $this->defaultMxPass) {
                                    $regenFlag = false;
                                } else {
                                    $regenFlag = true;
                                    $deleteFlag = true;
                                }
                            } else {
                                $regenFlag = true;
                            }
                        } else {
                            $regenFlag = true;
                        }
                    }

                    if ($deleteFlag) {
                        //password is changed
                        $queryClear = "DELETE from `" . $this->checkTable . "` WHERE `username`='" . $userMac . "' AND `attribute`='Cleartext-Password';";
                        nr_query($queryClear);
                    }

                    if ($regenFlag) {
                        $query = "INSERT INTO `" . $this->checkTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                                "(NULL,'" . $userMac . "','Cleartext-Password',':=','" . $this->defaultMxPass . "');";
                        nr_query($query);
                    }
                }
            }
        }
    }

    /**
     * Checks is user active or not?
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function isUserActive($login) {
        $result = true;
        if (isset($this->allUsers[$login])) {
            $userData = $this->allUsers[$login];
            if (($userData['Down'] == '0') AND ( $userData['Passive'] == '0') AND ( $userData['AlwaysOnline'] == '1') AND ( $userData['Cash'] >= -$userData['Credit'])) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Performs flushing and regeneration of all attributes in radius reply table
     * 
     * @return void
     */
    protected function generateReplyAll() {
        nr_query("TRUNCATE TABLE `" . $this->replyTable . "`;");
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $userLogin => $userData) {
                if (isset($this->allMacs[$userData['IP']])) {
                    $userMac = $this->allMacs[$userData['IP']];
                    $queryIP = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Framed-IP-Address','=','" . $userData['IP'] . "');";
                    nr_query($queryIP);

                    $queryTimeout = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Session-Timeout','=','" . $this->defaultSessionTimeout . "');";
                    nr_query($queryTimeout);

                    $queryActivate1 = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Unisphere-Service-Activate:1','+=','service-shape-in(" . $this->allSpeeds[$userLogin]['up'] . ",shape," . $userData['IP'] . ",,)');";
                    nr_query($queryActivate1);

                    $queryActivate2 = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Unisphere-Service-Activate:2','+=','service-shape-out(" . $this->allSpeeds[$userLogin]['down'] . ",shape," . $userData['IP'] . ",,)');";
                    nr_query($queryActivate2);

                    if (!$this->isUserActive($userLogin)) {
                        $queryDeactivate = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                                "(NULL,'" . $userMac . "','Unisphere-Service-Activate:3','+=','block');";
                        nr_query($queryDeactivate);
                    }
                }
            }
        }
    }

    /**
     * Really stupid method for regeneration of all auth/reply data
     * 
     * @return
     */
    public function totalRegeneration() {
        $this->generateCheckAll();
        //  $this->generateReplyAll();
    }

}

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
     * Contains default path for radclient
     *
     * @var string
     */
    protected $radclienPath = '/usr/local/bin/radclient';

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

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_JuniperListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `jun_clients`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('NAS name'));
        $cells.= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells.= wf_TableCell($each['shortname']);
            $cells.= wf_TableCell($each['secret']);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}
