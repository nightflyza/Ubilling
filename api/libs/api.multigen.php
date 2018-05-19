<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    public function __construct() {
        $this->loadConfigs();
    }

    /**
     * Loads reqired configss
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

}

?>