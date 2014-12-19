<?php

/*
 * Class to speed up loading of base configs
 */

class UbillingConfig {

    //stores system alter.ini & billing configs
    private $alterCfg = array();
    private $billingCfg = array();

    public function __construct() {
        $this->loadAlter();
        $this->loadBilling();
    }

    /**
     * loads system wide alter.ini to private alterCfg prop
     * 
     * @return void
     */
    protected function loadAlter() {
        $this->alterCfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    }

    /**
     * getter of private alterCfg prop
     * 
     * @return array
     */
    public function getAlter() {
        return ($this->alterCfg);
    }

    /**
     * loads system wide billing.ini to private alterCfg prop
     * 
     * @return void
     */
    protected function loadBilling() {
        $this->billingCfg = rcms_parse_ini_file(CONFIG_PATH . 'billing.ini');
    }

    /**
     * getter of private billingCfg prop
     * 
     * @return array
     */
    public function getBilling() {
        return ($this->billingCfg);
    }

}

?>