<?php
class UserstatsUkv {

    /**
     * Current userstats config stored as key=>value
     *
     * @var array
     */
    protected $usCfg = array();

    /**
     * Available tariffs as id=>data
     *
     * @var array
     */
    protected $tariffs = array();

    /**
     * Available all users data as id=>data
     *
     * @var array
     */
    protected $users = array();

    /**
     * Currently assigned users contracts as contract=>userid
     *
     * @var array
     */
    protected $contracts = array();

    /**
     * Preloaded user payments as userid=>data
     *
     * @var array
     */
    protected $payments = array();

    /**
     * UKV users database abstraction layer
     *
     * @var object
     */
    protected $usersDb = '';

    /**
     * UKV tariffs database abstraction layer
     *
     * @var object
     */
    protected $tariffsDb = '';
    
    /**
     * UKV payments database abstraction layer
     *
     * @var object
     */
    protected $paymentsDb = '';


    /**
     * Some predefined stuff here
     */
    const TABLE_USERS = 'ukv_users';
    const TABLE_TARIFFS = 'ukv_tariffs';
    const TABLE_PAYMENTS = 'ukv_payments';

    public function __construct() {
        $this->loadConfigs();
        $this->initDb();
        $this->loadTariffs();
        $this->loadUsers();
    }

    /**
     * Loads reqiored system configs into private data property
     * 
     * @return void
     */
    protected function loadConfigs() {
        $this->usCfg = zbs_LoadConfig();
    }

    /**
     * Inits database abstraction layers
     * 
     * @return void
     */
    protected function initDb() {
        $this->usersDb = new NyanORM(self::TABLE_USERS);
        $this->tariffsDb = new NyanORM(self::TABLE_TARIFFS);
        $this->paymentsDb = new NyanORM(self::TABLE_PAYMENTS);
    }

    /**
     * loads all tariffs into private tariffs prop
     * 
     * @return void
     */
    protected function loadTariffs() {
     $this->tariffsDb->orderBy('tariffname', 'ASC');
     $this->tariffs = $this->tariffsDb->getAll('id');
    }

    /**
     * loads all users from database to private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->users = $this->usersDb->getAll('id');
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                $this->contracts[$each['contract']] = $each['id'];
            }
        }
    }

    /**
     * Loads all user payments into private prop payments
     * 
     * @param int $userId
     * 
     * @return void
     */
    protected function loadPayments($userId) {
        $userId=ubRouting::filters($userId, 'int');
        $this->paymentsDb->where('visible', '=', '1');
        $this->paymentsDb->where('userid', '=', $userId);
        $this->paymentsDb->orderBy('id', 'DESC');
        $this->payments = $this->paymentsDb->getAll('id');
    }

    /**
     * Detects associated UKV user ID by login
     * 
     * @param string $login
     * 
     * @return int/void
     */
    public function detectUserByLogin($login) {
        $result = '';
        if (!empty($this->users)) {
            foreach ($this->users as $io => $each) {
                if ($each['inetlogin'] == $login) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders UKV user profile by its ID
     * 
     * @param int $userid
     * 
     * @return string
     */
    public function renderUserProfile($userid) {
        $result = '';

        if (isset($this->users[$userid])) {
            if (($this->users[$userid]['apt'] != '') OR ( $this->users[$userid]['apt'] != '0')) {
                $apt = '/' . $this->users[$userid]['apt'];
            } else {
                $apt = '';
            }

            $cells = la_TableCell(__('Address'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['city'] . ' ' . $this->users[$userid]['street'] . ' ' . $this->users[$userid]['build'] . $apt);
            $rows = la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Real name'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['realname']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Contract'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['contract']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Phone'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['phone']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Mobile'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['mobile']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Tariff'), '', 'row1');
            $cells.=la_TableCell(@$this->tariffs[$this->users[$userid]['tariffid']]['tariffname']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Tariff change'), '', 'row1');
            $cells.=la_TableCell(@$this->tariffs[$this->users[$userid]['tariffnmid']]['tariffname']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Tariff price'), '', 'row1');
            $cells.=la_TableCell($this->tariffs[$this->users[$userid]['tariffid']]['price'] . ' ' . $this->usCfg['currency']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Balance'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['cash'] . ' ' . $this->usCfg['currency']);
            $rows.= la_TableRow($cells, 'row3');


            $result = la_TableBody($rows, '100%', '0', '');
        }

        return ($result);
    }

    /**
     * Renders list of previous user payments
     * 
     * @param int $userid
     * 
     * @return string
     */
    public function renderUserPayments($userid) {
        $userid = ubRouting::filters($userid, 'int');
        $result = '';
        $this->loadPayments($userid);
        if (!empty($this->payments)) {
            $cells = la_TableCell(__('Date'));
            $cells.= la_TableCell(__('Payment'));
            $cells.= la_TableCell(__('Balance'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($this->payments as $io => $each) {
                $cells = la_TableCell($each['date']);
                $cells.= la_TableCell($each['summ']);
                $cells.= la_TableCell($each['balance']);
                $rows.= la_TableRow($cells, 'row3');
            }

            $result = la_TableBody($rows, '100%', '0');
        } else {
            $result = __('No payments to display');
        }

        return ($result);
    }

    public function getTariffName($tariffId) {
        $tariffId=ubRouting::filters($tariffId, 'int');
        $result='';
        if (isset($this->tariffs[$tariffId])) {
            $result= $this->tariffs[$tariffId]['tariffname'];
        }
        return ($result);
    }

    /**
     * Public getter for user data by login short version
     * 
     * @param string $userLogin
     * 
     * @return array
     */
    public function getUserDataShort($userLogin) {
        $userLogin = ubRouting::filters($userLogin, 'login');
        $result=array();
        $userId = $this->detectUserByLogin($userLogin);
        if (!empty($userId)) {
            if (isset($this->users[$userId])) {
                $userData= $this->users[$userId];
                $aptString=(!empty($userData['apt'])) ? '/' . $userData['apt'] : '';
                $addressString=$userData['city'] . ' ' . $userData['street'] . ' ' . $userData['build'] . $aptString;
                $tariffName=$this->getTariffName($userData['tariffid']);
                $tariffnmName=$this->getTariffName($userData['tariffnmid']);
                $tariffnmDate=$userData['tariffnmdate'];
                $cash=$userData['cash'];
                $result=array(
                    'address' => $addressString,
                    'realname' => $userData['realname'],
                    'contract' => $userData['contract'],
                    'phone' => $userData['phone'],
                    'mobile' => $userData['mobile'],
                    'tariff' => $tariffName,
                    'tariffnm' => $tariffnmName,
                    'tariffnmdate' => $tariffnmDate,
                    'cash' => $cash,
                );
            }
        }

        return ($result);
    }

}