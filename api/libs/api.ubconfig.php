<?php

/**
 * Basic Ubilling configs abstraction class
 */
class UbillingConfig {

    //stores system configs
    protected $alterCfg = array();
    protected $billingCfg = array();
    protected $photoCfg = array();
    protected $ymapsCfg = array();

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
        $this->alterCfg = parse_ini_file(CONFIG_PATH . 'alter.ini');
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
     * Parameter getter for alterCfg
     * Returns $parameter from alter.ini or FALSE if parameter not defined
     * May return $retValIfParamEmptyOrNotExists value, instead of searched $parameter
     * if searched $parameter is FALSE (but NOT NULL) or not defined
     *
     * @param mixed $param
     * @param mixed $retValIfParamEmptyOrNotExists
     *
     * @return mixed
     */
    public function getAlterParam($param = false, $retValIfParamEmptyOrNotExists = null) {
        $alterParam = ($param and isset($this->alterCfg[$param])) ? $this->alterCfg[$param] : false;

        if ($alterParam === false and ! is_null($retValIfParamEmptyOrNotExists)) {
            $alterParam = $retValIfParamEmptyOrNotExists;
        }

        return ($alterParam);
    }

    /**
     * loads system wide billing.ini to private alterCfg prop
     * 
     * @return void
     */
    protected function loadBilling() {
        $this->billingCfg = parse_ini_file(CONFIG_PATH . 'billing.ini');
    }

    /**
     * getter of private billingCfg prop
     * 
     * @return array
     */
    public function getBilling() {
        return ($this->billingCfg);
    }

    /**
     * loads system ymaps.ini to private ymapsCfg prop
     * 
     * @return void
     */
    protected function loadYmaps() {
        $this->ymapsCfg = parse_ini_file(CONFIG_PATH . 'ymaps.ini');
    }

    /**
     * getter of private ymapsCfg prop
     * 
     * @return array
     */
    public function getYmaps() {
        if (empty($this->ymapsCfg)) {
            $this->loadYmaps();
        }
        return ($this->ymapsCfg);
    }

    /**
     * loads system photostorage.ini to private photoCfg prop
     * 
     * @return void
     */
    protected function loadPhoto() {
        $this->photoCfg = parse_ini_file(CONFIG_PATH . 'photostorage.ini');
    }

    /**
     * getter of private photoCfg prop
     * 
     * @return array
     */
    public function getPhoto() {
        if (empty($this->photoCfg)) {
            $this->loadPhoto();
        }
        return ($this->photoCfg);
    }

}

?>