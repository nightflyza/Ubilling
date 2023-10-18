<?php

/**
 * Penalty aka Crime and punishment implementation
 */
class CrimeAndPunishment {

    protected $altCfg = array();
    protected $allUsers = array();
    protected $capData = array();
    protected $login = '';
    protected $logPath = '';
    protected $curdate = '';
    protected $dayLimit = 0; // via CAP_DAYLIMIT
    protected $percentpenalty = false; // via CAP_PENALTY_PERCENT
    protected $penalty = 0; // via CAP_PENALTY
    protected $payId = 1; // via CAP_PAYID
    protected $ignoreFrozen = true; // via CAP_IGNOREFROZEN

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUsers();
        $this->loadCapData();
    }

    /**
     * Loads system alter config into private data prop
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
     * Sets default options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->curdate = curdatetime();
        $this->dayLimit = vf($this->altCfg['CAP_DAYLIMIT'], 3);
        $this->percentpenalty = vf($this->altCfg['CAP_PENALTY_PERCENT'], 3);
        $this->penalty = ($this->percentpenalty) ? vf($this->altCfg['CAP_PENALTY_PERCENT'], 3) / 100 : vf($this->altCfg['CAP_PENALTY'], 3);
        $this->payId = vf($this->altCfg['CAP_PAYID'], 3);
        $this->ignoreFrozen = ($this->altCfg['CAP_IGNOREFROZEN']) ? true : false;
        $this->logPath = DATA_PATH . 'documents/crimeandpunishment.log';
    }

    /**
     * Pushes log data if debugging mode is enabled
     * 
     * @param string $data
     */
    protected function debugLog($data) {
        file_put_contents($this->logPath, $this->curdate . ' ' . $data . "\n", FILE_APPEND); //append data to log
    }

    /**
     * Loads all users for processing into private data property
     * 
     * @return void
     */
    protected function loadUsers() {
        if ($this->ignoreFrozen) {
            //$query = "SELECT * from `users` WHERE `Passive`='0';";
            $query = "SELECT `users`.*, `tariffs`.`fee` from `users` left join `tariffs` on `users`.`Tariff` = `tariffs`.`name` WHERE `Passive`='0';";
        } else {
            //$query = "SELECT * from `users`";
            $query = "SELECT `users`.*, `tariffs`.`fee` from `users` left join `tariffs` on `users`.`Tariff` = `tariffs`.`name`;";
        }
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Loads CAP data with counters from database
     * 
     * @return void
     */
    protected function loadCapData() {
        $query = "SELECT * from `capdata`";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->capData[$each['login']] = $each;
            }
        }
    }

    /**
     * Creates new CAP data entry for newly appeared user
     * 
     * @param string $login
     * @param int $days
     */
    protected function createCap($login, $days) {
        $login = mysql_real_escape_string($login);
        $days = vf($days, 3);
        $query = "INSERT INTO `capdata` (`id`,`login`,`date`,`days`) VALUES"
                . "(NULL,'" . $login . "','" . $this->curdate . "','" . $days . "');";
        nr_query($query);
        $this->debugLog("CAP CREATE (" . $login . ")");
    }

    /**
     * Changes CAP entry days counter
     * 
     * @param string $login
     * @param int $days
     * 
     * @return void
     */
    protected function setCap($login, $days) {
        if (isset($this->capData[$login])) {
            $days = vf($days, 3);
            $login = mysql_real_escape_string($login);
            simple_update_field('capdata', 'days', $days, "WHERE `login`='" . $login . "'");
            $this->debugLog("CAP UPDATE (" . $login . ") DAYS:" . $days);
        }
    }

    /**
     * Performs an punishment
     * 
     * @param string $login
     */
    protected function punish($login) {
        if (isset($this->capData[$login])) {
            $userTariff = $this->allUsers[$login]['Tariff'];
            $tariffFee = $this->allUsers[$login]['fee'];
            //optional power tariff price override?
            if ($tariffFee == 0) {
                if ($this->altCfg['PT_ENABLED']) {
                    $pt = new PowerTariffs();
                    if ($pt->isPowerTariff($userTariff)) {
                        $tariffFee = $pt->getPowerTariffPrice($userTariff);
                    }
                }
            }

            $penalty = '-' . ( ($this->percentpenalty) ? $this->penalty * $tariffFee : $this->penalty );
            zb_CashAdd($login, $penalty, 'add', $this->payId, 'PENALTY:' . $this->capData[$login]['days']);
            $this->debugLog("CAP PENALTY (" . $login . ") DAYS:" . $this->capData[$login]['days'] . " PENALTY:" . $penalty);
        }
    }

    /**
     * Run users processing
     * 
     * @return void
     */
    public function processing() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $login => $each) {
                //is user an debtor?
                if ($each['Cash'] < '-' . $each['Credit']) {
                    //normal debtors processing
                    if (isset($this->capData[$login])) {
                        //just counter increment
                        if ($this->capData[$login]['days'] != $this->dayLimit) {
                            $this->setCap($login, $this->capData[$login]['days'] + 1);
                        } else {
                            //doing punishment
                            $this->punish($login, $this->capData[$login]['days'] + 1);
                            $this->setCap($login, $this->capData[$login]['days'] + 1);
                        }
                    } else {
                        //newly down user
                        $this->createCap($login, 1);
                    }
                } else {
                    //again not debtor - dropping down counter
                    if (isset($this->capData[$login])) {
                        //trying to save SQL query count
                        if ($this->capData[$login]['days'] > 0) {
                            $this->setCap($login, 0);
                            $this->debugLog("CAP RESURRECTED (" . $login . ") DAYS:" . $this->capData[$login]['days']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets filtering login private property
     * 
     * @param string $login
     * 
     * @return void
     */
    public function setLogin($login) {
        $this->login = $login;
    }

    /**
     * Parses log data for some user login
     * 
     * @return array
     */
    protected function getLogData() {
        $result = array();
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $cat = $billCfg['CAT'];
        $grep = $billCfg['GREP'];
        $i = 0;

        if (!empty($this->login)) {
            if (file_exists($this->logPath)) {
                $command = $cat . ' ' . $this->logPath . ' | grep "(' . $this->login . ')"';
                $raw = shell_exec($command);
                if (!empty($raw)) {
                    $raw = explodeRows($raw);
                    if (!empty($raw)) {
                        foreach ($raw as $io => $each) {
                            if (!empty($each)) {
                                $line = explode(' ', $each);
                                $date = $line[0] . ' ' . $line[1];
                                $event = $line[3];
                                $params = explode(')', $each);
                                $params = $params[1];
                                $result[$i]['date'] = $date;
                                $result[$i]['event'] = $event;
                                $result[$i]['params'] = $params;
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns CAP data for some login
     * 
     * @param string $login
     * @return array
     */
    protected function getCapData($login) {
        $result = array();
        if (isset($this->capData[$login])) {
            $result = $this->capData[$login];
        }
        return ($result);
    }

    /**
     * Renders Crime and Punishment report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';

        $currentData = $this->getCapData($this->login);

        if (!empty($currentData)) {
            $result .= wf_tag('div', false, 'glamour') . __('Inactive days') . ': ' . $currentData['days'] . wf_tag('div', true);
            $result .= wf_CleanDiv();
        }

        $logData = $this->getLogData();
        if (!empty($logData)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Event'));
            $cells .= wf_TableCell(__('Details'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($logData as $io => $each) {
                $fc = wf_tag('font', false);
                $efc = wf_tag('font', true);

                if ($each['event'] == 'CREATE') {
                    $fc = wf_tag('font', false, '', 'color="#ffac1b"');
                }

                if ($each['event'] == 'UPDATE') {
                    $fc = wf_tag('font', false, '', 'color="#6396ff"');
                }

                if ($each['event'] == 'RESURRECTED') {
                    $fc = wf_tag('font', false, '', 'color="#1c7700"');
                }


                if ($each['event'] == 'PENALTY') {
                    $fc = wf_tag('font', false, '', 'color="#a90000"');
                }

                $params = $each['params'];
                $params = str_replace('DAYS', __('Day'), $params);
                $params = str_replace('PENALTY', __('Penalty'), $params);

                $cells = wf_TableCell($fc . $each['date'] . $efc);
                $cells .= wf_TableCell($fc . $each['event'] . $efc);
                $cells .= wf_TableCell($params);

                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= wf_tag('span', false, 'alert_warning') . __('Nothing found') . wf_tag('span', true);
        }
        return ($result);
    }
}

?>