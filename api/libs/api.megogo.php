<?php

class MegogoApi {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();
    protected $partnerId = '';
    protected $prefix = '';
    protected $salt = '';
    protected $urlApi = '';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets basic configurable options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        $this->partnerId = $this->altCfg['MG_PARTNERID'];
        $this->prefix = $this->altCfg['MG_PREFIX'];
        $this->salt = $this->altCfg['MG_SALT'];
        $this->urlApi = 'http://billing.megogo.net/partners/';
    }

    /**
     * Subscribes user to some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function subscribe($login, $service) {
        $result=true;
        
        return ($result);
    }

}

?>