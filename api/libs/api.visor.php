<?php

class UbillingVisor {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();
    protected $allUserData = array();
    protected $allUsers = array();
    protected $allCams = array();
    protected $allDvrs = array();
    protected $messages = '';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadUserData();
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

    /**
     * Inits system message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all existing users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    protected function loadUsers() {
        $query = "SELECT * from `visor_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                
            }
        }
    }

}
