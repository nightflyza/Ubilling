<?php

/**
 * returns list of available free Juniper NASes
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

class JunGen {

    protected $allUsers = array();
    protected $allMacs = array();
    protected $allSpeeds = array();
    protected $checkTable = 'jun_check';
    protected $replyTable = 'jun_reply';
    protected $defaultMxPass = 'mxBras';
    protected $defaultSessionTimeout = 3600;
    protected $speedOffset = 1024;

    public function __construct() {
        $this->loadUsers();
        $this->loadMacs();
        $this->loadSpeeds();
    }

    protected function loadUsers() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
    }

    protected function loadMacs() {
        $this->allMacs = zb_UserGetAllIpMACs();
    }

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

    protected function generateReplyAll() {
        nr_query("TRUNCATE TABLE `" . $this->replyTable . "`;");
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if (isset($this->allMacs[$each['IP']])) {
                    $userMac = $this->allMacs[$each['IP']];
                    $queryIP = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Framed-IP-Address','=','" . $each['IP'] . "');";
                    nr_query($queryIP);

                    $queryTimeout = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Session-Timeout','=','" . $this->defaultSessionTimeout . "');";
                    nr_query($queryTimeout);

                    $queryActivate1 = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Unisphere-Service-Activate:1','+=','service-shape-in(" . $this->allSpeeds[$io]['up'] . ",shape," . $each['IP'] . ",,)');";
                    nr_query($queryActivate1);

                    $queryActivate2 = "INSERT INTO `" . $this->replyTable . "` (`id`,`username`,`attribute`,`op`,`value`) VALUES " .
                            "(NULL,'" . $userMac . "','Unisphere-Service-Activate:2','+=','service-shape-out(" . $this->allSpeeds[$io]['down'] . ",shape," . $each['IP'] . ",,)');";
                    nr_query($queryActivate2);
                }
            }
        }
    }

    public function totalRegeneration() {
        $this->generateCheckAll();
        $this->generateReplyAll();
    }

}
