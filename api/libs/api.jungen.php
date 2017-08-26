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
     * Contains all available auth attributes pairs as username=>attribute=>data
     *
     * @var array
     */
    protected $allCheck = array();

    /**
     * Contains all available reply attributes pairs as username=>...
     *
     * @var array
     */
    protected $allReply = array();

    /**
     * Is logging enabled may be exported from JUNGEN_LOGGING option
     *
     * @var bool
     */
    protected $logging = false;

    /**
     * Juniper NAS users password option name
     */
    const OPTION_PASSWORD = 'JUNGEN_KEY';

    /**
     * Attributes generation logging option name
     */
    const OPTION_LOGGING = 'JUNGEN_LOGGING';

    /**
     * log path
     */
    const LOG_PATH = 'exports/jungen.log';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadMacs();
        $this->loadSpeeds();
        $this->loadChecks();
        $this->loadReplies();
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

        if (isset($this->altCfg[self::OPTION_LOGGING])) {
            if (!empty($this->altCfg[self::OPTION_LOGGING])) {
                $this->logging = $this->altCfg[self::OPTION_LOGGING];
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
     * Loads all available data from reply table
     *
     * @return void 
     */
    protected function loadReplies() {
        $query = "SELECT * from `" . $this->replyTable . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allReply[$each['username']][$each['attribute']] = $each['value'];
            }
        }
    }

    /**
     * 
     * Logs data if logging is enabled
     * 
     * @param string $data
     * 
     * @return void
     */
    protected function logEvent($data, $logLevel = 1) {
        if ($this->logging) {
            if ($this->logging >= $logLevel) {
                $curDate = curdatetime();
                $logData = $curDate . ' ' . $data . "\n";
                file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
            }
        }
    }

    /**
     * Flushes all check/reply entries for some user
     * 
     * @param string $userMac
     * 
     * @return void
     */
    public function destroyAllUserAttributes($userMac) {
        $queryCheck = "DELETE FROM `" . $this->checkTable . "` WHERE `username`='" . $userMac . "';";
        nr_query($queryCheck);

        $queryReply = "DELETE FROM `" . $this->replyTable . "` WHERE `username`='" . $userMac . "';";
        nr_query($queryReply);
        $this->logEvent($userMac . ' FLUSH ALL ATTRIBUTES', 1);
    }

    /**
     * Checks database for deleted/unknown users and drops their all attributes
     * 
     * @return void
     */
    protected function flushBuriedUsers() {
        /**
         * Come on, come on, turn the radio on
         * It's Friday night and it won't be long
         * Gotta do my hair, put my make-up on
         * It's Friday night and it won't be long 
         */
        $macTmp = array();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $userLogin => $userData) {
                if (isset($this->allMacs[$userData['IP']])) {
                    $userMac = $this->allMacs[$userData['IP']];
                    $macTmp[$userMac] = $userData['IP'];
                }
            }
        }

        if (!empty($this->allCheck)) {
            foreach ($this->allCheck as $targetMac => $eachAttr) {
                if (!isset($macTmp[$targetMac])) {
                    $this->destroyAllUserAttributes($targetMac);
                }
            }
        }

        /**
         * Baby I don't need dollar bills to have fun tonight
         * I love cheap thrills!
         * I don't need no money
         * As long as I can feel the beat
         * I don't need no money
         * As long as I keep dancing
         */
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
                        $this->logEvent($userMac . ' CHECK DELETE Cleartext-Password', 1);
                    }

                    if ($regenFlag) {
                        $query = "INSERT INTO `" . $this->checkTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                                "(NULL,'" . $userMac . "','Cleartext-Password',':=','" . $this->defaultMxPass . "');";
                        nr_query($query);
                        $this->logEvent($userMac . ' CHECK CREATE Cleartext-Password := ' . $this->defaultMxPass, 1);
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
     * Pushes some reply attribute to database
     * 
     * @param string $userMac
     * @param string $attribute
     * @param string $op
     * @param string $value
     * 
     * @return void
     */
    protected function createReplyAttribute($userMac, $attribute, $op, $value) {
        $query = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                "(NULL,'" . $userMac . "','" . $attribute . "','" . $op . "','" . $value . "');";
        nr_query($query);
        $this->logEvent($userMac . ' REPLY CREATE ' . $attribute . ' ' . $op . ' ' . $value, 1);
    }

    /**
     * Checks is some user attribute available and is not changed
     * 
     * @param string $userMac
     * @param string $attribute
     * @param string $value
     * 
     * @return int 0 - not exist / 1 - exists and not changed / -2 - changed
     */
    protected function checkReplyAttribute($userMac, $attribute, $value) {
        $result = 0;
        if (isset($this->allReply[$userMac])) {
            if (isset($this->allReply[$userMac][$attribute])) {
                if ($this->allReply[$userMac][$attribute] == $value) {
                    $result = 1;
                } else {
                    $result = -2;
                }
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        $this->logEvent($userMac . ' REPLY TEST ' . $attribute . ' ' . $value . ' RESULT ' . $result, 2);
        return ($result);
    }

    /**
     * Drops some reply attribute from database (required on value changes)
     * 
     * @param string $userMac
     * @param string $attribute
     * 
     * @return void
     */
    protected function deleteReplyAttribute($userMac, $attribute) {
        $query = "DELETE FROM `" . $this->replyTable . "` WHERE `username`='" . $userMac . "' AND `attribute`='" . $attribute . "';";
        nr_query($query);
        $this->logEvent($userMac . ' REPLY DELETE ' . $attribute, 1);
    }

    /**
     * Performs flushing and regeneration of all attributes in radius reply table
     * 
     * @return void
     */
    protected function generateReplyAll() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $userLogin => $userData) {
                if (isset($this->allMacs[$userData['IP']])) {
                    $userMac = $this->allMacs[$userData['IP']];
                    // user ip address
                    $userIp = $userData['IP'];
                    $ipCheck = $this->checkReplyAttribute($userMac, 'Framed-IP-Address', $userIp);
                    if ($ipCheck == -2) {
                        $this->deleteReplyAttribute($userMac, 'Framed-IP-Address');
                    }
                    if (($ipCheck == 0) OR ( $ipCheck == -2)) {
                        $this->createReplyAttribute($userMac, 'Framed-IP-Address', '=', $userIp);
                    }

                    //user session timeout
                    $sessionTimeout = $this->defaultSessionTimeout;
                    $timeoutCheck = $this->checkReplyAttribute($userMac, 'Session-Timeout', $sessionTimeout);
                    if ($timeoutCheck == -2) {
                        $this->deleteReplyAttribute($userMac, 'Session-Timeout');
                    }
                    if (($timeoutCheck == 0) OR ( $timeoutCheck == -2)) {
                        $this->createReplyAttribute($userMac, 'Session-Timeout', '=', $sessionTimeout);
                    }

                    //user shaper up
                    $serviceActivate1 = "service-shape-in(" . $this->allSpeeds[$userLogin]['up'] . ",shape," . $userData['IP'] . ",,)";
                    $serviceCheck1 = $this->checkReplyAttribute($userMac, 'Unisphere-Service-Activate:1', $serviceActivate1);
                    if ($serviceCheck1 == -2) {
                        $this->deleteReplyAttribute($userMac, 'Unisphere-Service-Activate:1');
                    }
                    if (($serviceCheck1 == 0) OR ( $serviceCheck1 == -2)) {
                        $this->createReplyAttribute($userMac, 'Unisphere-Service-Activate:1', '+=', $serviceActivate1);
                    }

                    //user shaper down
                    $serviceActivate2 = "service-shape-out(" . $this->allSpeeds[$userLogin]['down'] . ",shape," . $userData['IP'] . ",,)";
                    $serviceCheck2 = $this->checkReplyAttribute($userMac, 'Unisphere-Service-Activate:2', $serviceActivate2);
                    if ($serviceCheck2 == -2) {
                        $this->deleteReplyAttribute($userMac, 'Unisphere-Service-Activate:2');
                    }
                    if (($serviceCheck2 == 0) OR ( $serviceCheck2 == -2)) {
                        $this->createReplyAttribute($userMac, 'Unisphere-Service-Activate:2', '+=', $serviceActivate2);
                    }

                    //debtor blocking service
                    $serviceBlocking = 'block';
                    $blockingCheck = $this->checkReplyAttribute($userMac, 'Unisphere-Service-Activate:3', $serviceBlocking);
                    if (!$this->isUserActive($userLogin)) {
                        if ($blockingCheck == 0) {
                            $this->createReplyAttribute($userMac, 'Unisphere-Service-Activate:3', '+=', $serviceBlocking);
                        }
                    } else {
                        if ($blockingCheck == 1) {
                            $this->deleteReplyAttribute($userMac, 'Unisphere-Service-Activate:3');
                        }
                    }
                }
            }
        }
    }

    /**
     * Regeneration of all auth/reply data if its not exists or changed
     * 
     * @return
     */
    public function totalRegeneration() {
        $this->flushBuriedUsers();
        $this->generateCheckAll();
        $this->generateReplyAll();
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

class JunAcct {

    /**
     * Contains preloaded user accounting data
     *
     * @var array
     */
    protected $userAcctData = array();

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains interesting fields from database acct table
     *
     * @var array
     */
    protected $fieldsRequired = array();

    /**
     * Contains name of accounting table
     *
     * @var string
     */
    protected $tableName = 'jun_acct';

    /**
     * Messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * log path
     */
    const LOG_PATH = 'exports/jungen.log';

    public function __construct($login = '') {
        $this->setLogin($login);
        $this->setFields();
        $this->loadAcctData();
        $this->initMessages();
    }

    /**
     * Inits system messages object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets current user login
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login) {
        $this->userLogin = mysql_real_escape_string($login);
    }

    /**
     * Sets interesting fields from accounting table for selecting data
     * 
     * @return void
     */
    protected function setFields() {
        $this->fieldsRequired = array(
            'acctsessionid',
            'username',
            'nasipaddress',
            'nasportid',
            'acctstarttime',
            'acctstoptime',
            'acctinputoctets',
            'acctoutputoctets',
            'framedipaddress',
            'acctterminatecause'
        );
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
     * Transforms mac from xxxx.xxxx.xxxx format to xx:xx:xx:xx:xx:xx
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function transformMacNormal($mac) {
        $result = implode(":", str_split(str_replace(".", "", $mac), 2));
        return ($result);
    }

    /**
     * Renders controls for acct date search
     * 
     * @return string
     */
    public function renderDateSerachControls() {
        $result = '';
        $curTime = time();
        $dayAgo = $curTime - 86400;
        $dayAgo = date("Y-m-d", $dayAgo);
        $dayTomorrow = $curTime + 86400;
        $dayTomorrow = date("Y-m-d", $dayTomorrow);
        $preDateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : $dayAgo;
        $preDateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : $dayTomorrow;
        $unfinishedFlag = (wf_CheckPost(array('showunfinished'))) ? true : false;

        $inputs = wf_DatePickerPreset('datefrom', $preDateFrom, false);
        $inputs.= wf_DatePickerPreset('dateto', $preDateTo, false);
        $inputs.= wf_CheckInput('showunfinished', __('Show unfinished'), false, $unfinishedFlag);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Loading some data from database
     * 
     * @return void
     */
    protected function loadAcctData() {
        $fieldsList = implode(', ', $this->fieldsRequired);
        if (!empty($this->userLogin)) {
            $userIp = zb_UserGetIP($this->userLogin);
            $userMac = zb_MultinetGetMAC($userIp);
            $userMacJ = $this->transformMac($userMac);
            if (!empty($userIp)) {
                $query = "SELECT " . $fieldsList . " FROM `" . $this->tableName . "` WHERE `username`='" . $userMacJ . "' ORDER BY `radacctid` DESC;";
                $this->userAcctData = simple_queryall($query);
            }
        } else {
            if (wf_CheckPost(array('datefrom', 'dateto'))) {
                $searchDateFrom = mysql_real_escape_string($_POST['datefrom']);
                $searchDateTo = mysql_real_escape_string($_POST['dateto']);
            } else {
                $curTime = time();
                $dayAgo = $curTime - 86400;
                $dayAgo = date("Y-m-d", $dayAgo);
                $dayTomorrow = $curTime + 86400;
                $dayTomorrow = date("Y-m-d", $dayTomorrow);
                $searchDateFrom = $dayAgo;
                $searchDateTo = $dayTomorrow;
            }

            if (wf_CheckPost(array('showunfinished'))) {
                $unfQueryfilter = "OR `acctstoptime` IS NULL ";
            } else {
                $unfQueryfilter = '';
            }

            $query = "SELECT " . $fieldsList . " FROM `" . $this->tableName . "` WHERE `acctstarttime` BETWEEN '" . $searchDateFrom . "' AND '" . $searchDateTo . "'"
                    . " " . $unfQueryfilter . "  ORDER BY `radacctid` DESC ;";
            $this->userAcctData = simple_queryall($query);
        }
    }

    /**
     * Renders preloaded accounting data in human-readable view
     * 
     * @return string
     */
    public function renderAcctStats() {
        $result = '';
        $totalCount = 0;

        if (!empty($this->userAcctData)) {
            $allUserMacs = zb_UserGetAllMACs();
            $allUserMacs = array_flip($allUserMacs);
            $allUserAddress = zb_AddressGetFulladdresslistCached();

            $cells = wf_TableCell('acctsessionid');
            $cells.= wf_TableCell('username');
            $cells.= wf_TableCell('nasipaddress');
            $cells.= wf_TableCell('nasportid');
            $cells.= wf_TableCell('acctstarttime');
            $cells.= wf_TableCell('acctstoptime');
            $cells.= wf_TableCell('acctinputoctets');
            $cells.= wf_TableCell('acctoutputoctets');
            $cells.= wf_TableCell('framedipaddress');
            $cells.= wf_TableCell('acctterminatecause');
            $cells.= wf_TableCell(__('Time'));
            $cells.= wf_TableCell(__('User'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->userAcctData as $io => $each) {
                $fc = '';
                $efc = wf_tag('font', true);
                if (!empty($each['acctstoptime'])) {
                    $startTime = strtotime($each['acctstarttime']);
                    $endTime = strtotime($each['acctstoptime']);
                    $timeOffsetRaw = $endTime - $startTime;
                    $timeOffset = zb_formatTime($timeOffsetRaw);
                } else {
                    $timeOffset = '';
                    $timeOffsetRaw = '';
                }

                //some coloring
                if (empty($each['acctstoptime'])) {
                    $fc = wf_tag('font', false, '', 'color="#ff6600"');
                } else {
                    $fc = wf_tag('font', false, '', 'color="#005304"');
                }

                //user detection
                $normalMac = $this->transformMacNormal($each['username']);
                $loginDetect = (isset($allUserMacs[$normalMac])) ? $allUserMacs[$normalMac] : '';
                $userAddress = (!empty($loginDetect)) ? @$allUserAddress[$loginDetect] : '';
                $profileLink = (!empty($loginDetect)) ? wf_Link('?module=userprofile&username=' . $loginDetect, web_profile_icon() . ' ' . $userAddress, false) : '';


                $cells = wf_TableCell($fc . $each['acctsessionid'] . $efc);
                $cells.= wf_TableCell($each['username']);
                $cells.= wf_TableCell($each['nasipaddress']);
                $cells.= wf_TableCell($each['nasportid']);
                $cells.= wf_TableCell($each['acctstarttime']);
                $cells.= wf_TableCell($each['acctstoptime']);
                $cells.= wf_TableCell(stg_convert_size($each['acctinputoctets']), '', '', 'sorttable_customkey="' . $each['acctinputoctets'] . '"');
                $cells.= wf_TableCell(stg_convert_size($each['acctoutputoctets']), '', '', 'sorttable_customkey="' . $each['acctoutputoctets'] . '"');
                $cells.= wf_TableCell($each['framedipaddress']);
                $cells.= wf_TableCell($each['acctterminatecause']);
                $cells.= wf_TableCell($timeOffset, '', '', 'sorttable_customkey="' . $timeOffsetRaw . '"');
                $cells.= wf_TableCell($profileLink);
                $rows.= wf_TableRow($cells, 'row3');
                $totalCount++;
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
            $result.=__('Total') . ': ' . $totalCount;
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders jungen logs control
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    function renderLogControl() {
        global $ubillingConfig;
        $result = '';
        $logData = array();
        $renderData = '';
        $rows = '';
        $recordsLimit = 200;
        $prevTime = '';
        $curTimeTime = '';
        $diffTime = '';

        if (file_exists(self::LOG_PATH)) {
            $billCfg = $ubillingConfig->getBilling();
            $tailCmd = $billCfg['TAIL'];
            $runCmd = $tailCmd . ' -n ' . $recordsLimit . ' ' . self::LOG_PATH;
            $rawResult = shell_exec($runCmd);
            $renderData.= __('Showing') . ' ' . $recordsLimit . ' ' . __('last events') . wf_tag('br');
            $renderData.= wf_Link('?module=report_jungen&dljungenlog=true', wf_img('skins/icon_download.png', __('Download')) . ' ' . __('Download full log'), true);

            if (!empty($rawResult)) {
                $logData = explodeRows($rawResult);
                $logData = array_reverse($logData); //from new to old list
                if (!empty($logData)) {


                    $cells = wf_TableCell(__('Date'));
                    $cells.= wf_TableCell(__('Event'));
                    $rows.=wf_TableRow($cells, 'row1');

                    foreach ($logData as $io => $each) {
                        if (!empty($each)) {

                            $eachEntry = explode(' ', $each);
                            $cells = wf_TableCell($eachEntry[0] . ' ' . $eachEntry[1]);
                            $cells.= wf_TableCell(str_replace(($eachEntry[0] . ' ' . $eachEntry[1]), '', $each));
                            $rows.=wf_TableRow($cells, 'row3');
                        }
                    }
                    $renderData.= wf_TableBody($rows, '100%', 0, 'sortable');
                }
            } else {
                $renderData.= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            }

            $result = wf_modal(wf_img('skins/log_icon_small.png', __('Attributes regeneration log')), __('Attributes regeneration log'), $renderData, '', '1280', '600');
        }
        return ($result);
    }

    /**
     * Performs downloading of log
     * 
     * @return void
     */
    public function logDownload() {
        if (file_exists(self::LOG_PATH)) {
            zb_DownloadFile(self::LOG_PATH);
        } else {
            show_error(__('Something went wrong') . ': EX_FILE_NOT_FOUND ' . self::LOG_PATH);
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
