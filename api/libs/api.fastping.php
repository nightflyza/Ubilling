<?php

class FastPing {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * StarDust process manager instance placeholder
     *
     * @var object
     */
    protected $pid = '';

    /**
     * Contains system sudo full path
     *
     * @var string
     */
    protected $sudoPath = '/usr/local/bin/sudo';

    /**
     * Contains default fping path
     *
     * @var string
     */
    protected $fpingPath = '/usr/local/sbin/fping -r 1 -t 10';

    /**
     * Contains some predefined stuff
     */
    const PID_NAME = 'FASTPING';

    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
        $this->initStarDust();
    }

    /**
     * Loads all required configs in protected propeties for futher usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billCfg = $ubillingConfig->getBilling();
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required system options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->fpingPath = $this->altCfg['FPING_PATH'];
        $this->sudoPath = $this->billCfg['SUDO'];
    }

    protected function initStarDust() {
        $this->pid = new StarDust(self::PID_NAME);
    }

}
