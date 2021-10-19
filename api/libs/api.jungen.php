<?php

/**
 * Old-school Juniper MX NAS support
 */
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
     * Logs data if logging is enabled
     * 
     * @param string $data
     * @param int $logLevel
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
