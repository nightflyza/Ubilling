<?php

/**
 * Dead users burial implementation
 */
class Cemetery {

    /**
     * Dead mark tag id via alter.ini: DEAD_TAGID
     *
     * @var int
     */
    protected $tagId = '';

    /**
     * All dead users log as id=>data
     *
     * @var array
     */
    protected $allDead = array();

    /**
     * All users with associated DEAD_TAGID
     *
     * @var array
     */
    protected $allTagged = array();

    /**
     * System alter.ini config - must be loaded in constructor
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Cemetery database model placeholder
     *
     * @var object
     */
    protected $cemetery = '';

    /**
     * Tags database model placeholder
     *
     * @var object
     */
    protected $tags = '';

    /**
     * Creates new cemetery instance
     * 
     * @param bool $loadDead
     */
    public function __construct($loadDead = true) {
        $this->loadAlter();
        $this->setTagId();
        //initalizing some database models
        $this->initCemetery();
        if ($loadDead) {
            $this->initTags();
            //loading required data
            $this->loadDead();
            $this->loadTagged();
        }
    }

    /**
     * Inits cemetery database model
     * 
     * @return void
     */
    protected function initCemetery() {
        $this->cemetery = new NyanORM('cemetery');
    }

    /**
     * Inits tags database model
     * 
     * @return void
     */
    protected function initTags() {
        $this->tags = new NyanORM('tags');
    }

    /**
     * Loads system alter.ini config into protected property
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
     * Sets dead mark tag id into protected property
     * 
     * @return void
     */
    protected function setTagId() {
        if (isset($this->altCfg['DEAD_TAGID'])) {
            if ($this->altCfg['DEAD_TAGID']) {
                $this->tagId = $this->altCfg['DEAD_TAGID'];
            }
        }
    }

    /**
     * Loads dead users log from database
     * 
     * @return void
     */
    protected function loadDead() {
        $this->allDead = $this->cemetery->getAll('id');
    }

    /**
     * Loads all tagged users with DEAD_TAGID
     * 
     * @return void
     */
    protected function loadTagged() {
        if ($this->tagId) {
            $tagId = ubRouting::filters($this->tagId, 'int');
            $this->tags->where('tagid', '=', $tagId);
            $this->tags->selectable('login');
            $this->allTagged = $this->tags->getAll('login');
            $this->tags->selectable();
        }
    }

    /**
     * Fills cemetary log with some data
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    protected function logFuneral($login, $state) {
        $state = ubRouting::filters($state, 'int');
        $loginF = ubRouting::filters($login, 'mres');
        $date = curdatetime();

        $this->cemetery->data('login', $loginF);
        $this->cemetery->data('state', $state);
        $this->cemetery->data('date', $date);
        $this->cemetery->create();
        log_register('CEMETERY (' . $login . ') SET `' . $state . '`');
    }

    /**
     * Sets user as dead
     * 
     * @param string $login
     * 
     * @return void
     */
    public function setDead($login) {
        global $billing;
        $billing->setpassive($login, 1);
        log_register('CHANGE Passive (' . $login . ') ON 1');
        if ($this->tagId) {
            stg_add_user_tag($login, $this->tagId);
        }

        //set cash to zero flag (3)
        if (@$this->altCfg['CEMETERY_ENABLED'] == '3') {
            zb_CashAdd($login, 0, 'set', 1, 'CEMETERY');
        }
        $this->logFuneral($login, 1);
    }

    /**
     * Sets user as undead
     * 
     * @param string $login
     * 
     * @return void
     */
    public function setUndead($login) {
        global $billing;
        $billing->setpassive($login, 0);
        log_register('CHANGE Passive (' . $login . ') ON 0');
        if ($this->tagId) {
            stg_del_user_tagid($login, $this->tagId);
        }
        $this->logFuneral($login, 0);
    }

    /**
     * Checks is user currently mark as dead?
     * 
     * @param string $login
     * 
     * @return bool
     */
    public function isUserDead($login) {
        $result = false;
        if (isset($this->allTagged[$login])) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Renders full cemetary log for some user
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderCemeteryLog($login) {
        $result = '';
        if (!empty($this->allDead)) {
            $cells = wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Status'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDead as $io => $each) {
                if ($each['login'] == $login) {
                    $led = ($each['state']) ? web_bool_led(0) : web_bool_led(1);
                    $cells = wf_TableCell($each['date']);
                    $cells .= wf_TableCell($led);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        }

        /**
         * Ich trink Dutzende von Dosenbier und schalte meinen Fernseher an
         * Todesmöpse ohne Gnade, Todesmöpse greifen an
         * Super dicke titten, ey die wabbeln und die schwabbeln
         * Sowie affengeil Teil drei, die auf'm Affenfelsen rammeln
         * Ich komm auf deine Party und ich kotze auf's Buffet
         */
        if (cfr('NECROMANCY')) {
            if ($this->isUserDead($login)) {
                $inputs = wf_HiddenInput('cemeterysetasundead', $login);
                $inputs .= wf_Submit(__('Set user connected'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $inputs = wf_HiddenInput('cemeterysetasdead', $login);
                $inputs .= wf_Submit(__('Set user disconnected'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }

        return ($result);
    }

    /**
     * Returns array of all users with dead tag assigned
     * 
     * @return array
     */
    public function getAllTagged() {
        return ($this->allTagged);
    }

    /**
     * Renders all-time funeral charts
     * 
     * @return string
     */
    public function renderChart() {
        $result = '';
        $data = __('Month') . ',' . __('Subscriber is connected') . ',' . __('Subscriber is not connected') . "\n";
        $tmpArr = array();
        $totalCount = 0;

        $chartData = array();
        $chartData[] = array(__('Month'), __('Subscriber is connected'), __('Subscriber is not connected'));

        if (!empty($this->allDead)) {
            foreach ($this->allDead as $io => $each) {
                $time = strtotime($each['date']);
                $month = date("Y-m-d", $time);

                if (isset($tmpArr[$month])) {
                    if ($each['state']) {
                        $tmpArr[$month]['inactive'] ++;
                    } else {
                        $tmpArr[$month]['active'] ++;
                    }
                    $totalCount++;
                } else {
                    if ($each['state']) {
                        $tmpArr[$month]['inactive'] = 1;
                        $tmpArr[$month]['active'] = 0;
                    } else {
                        $tmpArr[$month]['active'] = 1;
                        $tmpArr[$month]['inactive'] = 0;
                    }
                    $totalCount++;
                }
            }
        }

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $ia => $each) {
                $chartData[] = array($ia, ($totalCount - $each['active']), ($totalCount - $each['inactive']));
            }


            $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

            $result .= wf_gchartsLine($chartData, '', '100%', '300px', $chartsOptions);
        }
        return ($result);
    }

    /**
     * Returns count of dead users by some date with non strict search
     * 
     * @param string $date
     * 
     * @return int
     */
    public function getDeadDateCount($date) {
        $result = 0;
        if (!empty($this->allDead)) {
            foreach ($this->allDead as $io => $each) {
                if (ispos($each['date'], $date)) {
                    if ($each['state']) {
                        $result++;
                    } else {
                        $result--;
                    }
                }
            }
        }
        return ($result);
    }

}

?>