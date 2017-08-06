<?php

class JunGen {

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

    public function __construct() {
        $this->loadUsers();
        $this->loadMacs();
        $this->loadSpeeds();
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
     * Performs flushing and regeneration of all data in radius check table
     * 
     * @return void
     */
    protected function generateCheckAll() {
        nr_query("TRUNCATE TABLE `" . $this->checkTable . "`;");
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if (isset($this->allMacs[$each['IP']])) {
                    $userMac = $this->allMacs[$each['IP']];
                    $query = "INSERT INTO `" . $this->checkTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Cleartext-Password',':=','" . $this->defaultMxPass . "');";
                    nr_query($query);
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
        $this->generateReplyAll();
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
