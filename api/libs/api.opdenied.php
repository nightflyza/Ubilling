<?php

class OpDenied {

    /**
     * Contains all of preloaded users which will be denied for OpenPayz transactions as login=>id
     *
     * @var array
     */
    protected $allDenied = array();

    /**
     * Database layer placeholder
     *
     * @var object
     */
    protected $deniedDb = '';

    /**
     * Contains default data table
     */
    const DATA_TABLE = 'op_denied';

    /**
     * Predefined routing etc..
     */
    const PROUTE_DENY_LOGIN = 'opdenieduserlogin';
    const PROUTE_DENY_FLAG = 'opdeniedflag';

    public function __construct() {
        $this->initDb();
        $this->loadDenied();
    }

    /**
     * Inits database abstraction layer instance
     * 
     * @return void
     */
    protected function initDb() {
        $this->deniedDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all available denied users from database in local prop
     * 
     * @return void
     */
    protected function loadDenied() {
        $this->allDenied = $this->deniedDb->getAll('login');
    }

    /**
     * Renders deny-state modification form for some user login
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderModifyForm($login) {
        $result = '';
        if (!empty($login)) {
            $isNowDenied = (isset($this->allDenied[$login])) ? true : false;
            $inputs = wf_HiddenInput(self::PROUTE_DENY_LOGIN, $login);
            $inputs .= wf_CheckInput(self::PROUTE_DENY_FLAG, __('Deny online payments for this user'), true, $isNowDenied);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Switches user denied state if required
     * 
     * @param string $login
     * @param bool $newDenyState
     * 
     * @return void
     */
    public function setUserDenyState($login, $newDenyState) {
        $loginF = ubRouting::filters($login, 'mres');
        //user already denied
        if (isset($this->allDenied[$login])) {
            if (!$newDenyState) {
                //deleting deny record
                $this->deniedDb->where('login', '=', $loginF);
                $this->deniedDb->delete();
                log_register('ZBSMAN OPALLOWED (' . $login . ')');
            }
        } else {
            //user is not denied now
            if ($newDenyState) {
                //creating new deny record
                $this->deniedDb->data('login', $loginF);
                $this->deniedDb->create();
                log_register('ZBSMAN OPDENIED (' . $login . ')');
            }
        }
    }

    /**
     * Returns array of currently denied users as login=>login
     * 
     * @return array
     */
    public function getAllDenied() {
        $result = array();
        if (!empty($this->allDenied)) {
            foreach ($this->allDenied as $io => $each) {
                $result[$io] = $io;
            }
        }
        return($result);
    }

}
