<?php

error_reporting(E_ALL);

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
     * Available users and therir data as id=>data
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

    public function __construct() {
        $this->loadConfigs();
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
     * loads all tariffs into private tariffs prop
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `ukv_tariffs` ORDER by `tariffname` ASC;";
        $alltariffs = simple_queryall($query);
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $each) {
                $this->tariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * loads all users from database to private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `ukv_users`";
        $allusers = simple_queryall($query);
        if (!empty($allusers)) {
            foreach ($allusers as $io => $each) {
                $this->users[$each['id']] = $each;
                $this->contracts[$each['contract']] = $each['id'];
            }
        }
    }

    /**
     * 
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

            $cells = la_TableCell(__('Contract'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['contract']);
            $rows = la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Real name'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['realname']);
            $rows.= la_TableRow($cells, 'row3');

            $cells = la_TableCell(__('Address'), '', 'row1');
            $cells.=la_TableCell($this->users[$userid]['city'] . ' ' . $this->users[$userid]['street'] . ' ' . $this->users[$userid]['build'] . $apt);
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
        $userid = vf($userid, 3);
        $result = '';
        $query = "SELECT * from `ukv_payments` WHERE `visible`='1' AND `userid`='" . $userid . "' ORDER BY `id` DESC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            $cells = la_TableCell(__('ID'));
            $cells.= la_TableCell(__('Date'));
            $cells.= la_TableCell(__('Payment'));
            $cells.= la_TableCell(__('Balance'));
            $rows = la_TableRow($cells, 'row1');

            foreach ($all as $io => $each) {
                $cells = la_TableCell($each['id']);
                $cells.= la_TableCell($each['date']);
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

}

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

// if UKV enabled
if ($us_config['UKV_ENABLED']) {
    $usUkv = new UserstatsUkv();
    $ukvUserId = $usUkv->detectUserByLogin($user_login);
    if ($ukvUserId) {
        show_window(__('CaTV user profile'), $usUkv->renderUserProfile($ukvUserId));
        show_window(__('CaTV payments'), $usUkv->renderUserPayments($ukvUserId));
    } else {
        show_window(__('Sorry'), __('No CaTV account associated with your Internet service'));
    }
} else {
    show_window(__('Sorry'), __('Unfortunately CaTV is disabled'));
}
?>