<?php

/*
 * Class to speed up loading of base configs
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

    /**
     * loads system ymaps.ini to private ymapsCfg prop
     * 
     * @return void
     */
    protected function loadYmaps() {
        $this->ymapsCfg = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
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
        $this->photoCfg = rcms_parse_ini_file(CONFIG_PATH . "photostorage.ini");
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

/**
 * Draft message helper
 */
class UbillingMessageHelper {

    protected $deleteAlert = '';
    protected $editAlert = '';

    public function __construct() {
        $this->setDeleteAlert();
        $this->setEditAlert();
    }

    /**
     * Sets localized string as default deletion warning
     */
    protected function setDeleteAlert() {
        $this->deleteAlert = __('Removing this may lead to irreparable results');
    }

    /**
     * Sets localized string as default edit warning
     */
    protected function setEditAlert() {
        $this->editAlert = __('Are you serious');
    }

    /**
     * Returns localized deletion warning message
     * 
     * @return string
     */
    public function getDeleteAlert() {
        return ($this->deleteAlert);
    }

    /**
     * Returns localized editing warning message
     * 
     * @return string
     */
    public function getEditAlert() {
        return ($this->editAlert);
    }

    /**
     * Returns styled message
     * 
     * @param string $data text message for styling
     * @param string $style error, warning, info, success
     * 
     * @return string
     */
    public function getStyledMessage($data, $style) {
        $class = 'alert_' . $style;
        $result = wf_tag('span', false, $class) . $data . wf_tag('span', true);
        return ($result);
    }

}

?>