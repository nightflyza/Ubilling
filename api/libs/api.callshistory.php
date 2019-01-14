<?php

class CallsHistory {

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
    protected $billingCfg = array();

    /**
     * Default number position offset
     *
     * @var int
     */
    protected $offsetNumber = 3;

    /**
     * Default call status position offset
     *
     * @var int
     */
    protected $offsetStatus = 5;

    /**
     * Default detected login offset
     *
     * @var int
     */
    protected $offsetLogin = 7;

    /**
     * Default log path to parse
     */
    protected $dataSource = 'content/documents/askozianum.log';

    /**
     * Creates new CallsHistory instance
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads required configs and sets some options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billingCfg = $ubillingConfig->getBilling();
    }


}
