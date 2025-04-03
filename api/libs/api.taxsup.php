<?php

/**
 * Taxa suplimentara implementation
 */
class TaxSup {

    /**
     * Database abstraction layer
     * 
     * @var object
     */
    protected $usersDb = '';

    /**
     * Array of all user fees available in database
     * 
     * @var array
     */
    protected $allUserFees = array();

    //some predefined stuff
    const USERS_TABLE = 'taxsup';
    const URL_ME = '?module=taxsupedit';
    const ROUTE_USERNAME = 'username';
    const PROUTE_FEE = 'settaxsupfee';
    const PROUTE_LOGIN = 'settaxsuplogin';
    const PID_PROCESSING = 'TAXSUP_PROCESSING';

    public function __construct() {
        $this->initDb();
        $this->loadUserFees();
    }

    /**
     * Loads all user fees from database
     * 
     * @return void
     */
    protected function loadUserFees() {
        $this->allUserFees = $this->usersDb->getAll('login');
    }

    /**
     * Inits database abstraction layer for following struct
     * 
     * @return void
     */
    protected function initDb() {
        $this->usersDb = new NyanORM(self::USERS_TABLE);
    }

    /**
     * Creates new user fee record
     * 
     * @param string $login
     * @param float $fee
     * 
     * @return void
     */
    protected function createUserFee($login, $fee) {
        $login = ubRouting::filters($login, 'login');
        $fee = ubRouting::filters($fee, 'float');
        $this->usersDb->data('login', $login);
        $this->usersDb->data('fee', $fee);
        $this->usersDb->create();
        log_register('TAXSUP CREATE USER FEE (' . $login . ') `' . $fee . '`');
    }

    /**
     * Updates existing user fee or creates new one if not exists
     * 
     * @param string $login
     * @param float $fee
     * 
     * @return void
     */
    public function changeUserFee($login, $fee) {
        $login = ubRouting::filters($login, 'login');
        $fee = ubRouting::filters($fee, 'float');

        if (!isset($this->allUserFees[$login])) {
            $this->createUserFee($login, $fee);
        } else {
            $this->usersDb->where('login', '=', $login);
            $this->usersDb->data('fee', $fee);
            $this->usersDb->save();
            log_register('TAXSUP CHANGE USER FEE (' . $login . ') `' . $fee . '`');
        }
    }

    /**
     * Deletes user fee record
     * 
     * @param string $login
     * 
     * @return void
     */
    public function deleteUserFee($login) {
        $login = ubRouting::filters($login, 'login');
        $this->usersDb->where('login', '=', $login);
        $this->usersDb->delete();
        log_register('TAXSUP DELETE USER FEE (' . $login . ')');
    }

    /**
     * Gets user fee by login
     * 
     * @param string $login
     * 
     * @return float|0
     */
    public function getUserFee($login) {
        $result = 0;
        if (isset($this->allUserFees[$login])) {
            $result = $this->allUserFees[$login]['fee'];
        }
        return ($result);
    }

    /**
     * Gets all user fees as array
     * 
     * @return array
     */
    public function getAllUserFees() {
        return ($this->allUserFees);
    }

    /**
     * Do the processing of additional fees
     * 
     * @return void
     */
    public function processingFees() {
        global $ubillingConfig;
        $processingProcess = new StarDust(self::PID_PROCESSING);

        if ($processingProcess->notRunning()) {
            $processingProcess->start();
            $cashtypeId = ($ubillingConfig->getAlterParam('TAXSUP_CASHTYPEID')) ? $ubillingConfig->getAlterParam('TAXSUP_CASHTYPEID') : 1;
            $operation = 'correct';
            $allUserFees = $this->getAllUserFees();

            if (!empty($allUserFees)) {
                foreach ($allUserFees as $login => $userData) {
                    if ($userData['fee'] > 0) {
                        $fee = '-' . abs($userData['fee']); //always negative
                        zb_CashAdd($login, $fee, $operation, $cashtypeId, 'TAXSUP:' . $fee);
                    }
                }
            }
            $processingProcess->stop();
        }
    }
}
