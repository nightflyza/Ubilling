<?php

class SwitchPortAssign {

    protected $data = array();
    protected $allusers = array();
    protected $diff = array();
    protected $altCfg = array();

    public function __construct() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();

        //load actual data by switch port assing
        $this->loadData();
        //loads full user list
        $this->loadAllUsers();
    }

    /**
     * get all users with switch port assing and push it into data prop
     * 
     * @return void
     */
    protected function loadData() {

        $query = "SELECT `login` FROM `switchportassign`;";
        $alldata = simple_queryall($query);

        if (!empty($alldata)) {
            foreach ($alldata as $io => $each) {
                $this->data[$each['login']] = $each['login'];
            }
        }
    }

    /**
     * get all users logins and push it into allusers prop
     * 
     * @return void
     */
    protected function loadAllUsers() {
        $query = "SELECT `login` FROM `users`;";
        if (isset($this->altCfg['SWITCHPORT_REPORT_IGNORE_PON'])) {
            if ($this->altCfg['SWITCHPORT_REPORT_IGNORE_PON']) {
                $query = 'SELECT `users`.`login` FROM `users` LEFT JOIN `pononu` ON (`users`.`login` = `pononu`.`login`) WHERE `pononu`.`login` IS NULL';
            }
        }

        $alldata = simple_queryall($query);

        if (!empty($alldata)) {
            foreach ($alldata as $io => $each) {
                $this->allusers[$each['login']] = $each['login'];
            }
        }
    }

    /**
     * returns protected propert data
     * 
     * @return array
     */
    public function getData() {
        $result = $this->data;
        return ($result);
    }

    /**
     * renders report by existing protected data prop
     * 
     * @return string
     */
    public function renderNoSwitchPort() {
        if (!empty($this->allusers)) {
            foreach ($this->allusers as $io => $each) {
                if (!isset($this->data[$each])) {
                    $this->diff[$each] = $each;
                }
            }
        }
        $result = web_UserArrayShower($this->diff);
        return ($result);
    }

}

?>