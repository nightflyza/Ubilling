<?php

class FriendshipIsMagic {

    /**
     * All of available user relations as friend=>parent
     * 
     * @var array
     */
    protected $allFriends = array();

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available users as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains raw payments array
     * 
     * @var array
     */
    protected $rawPayments = array();

    /**
     * Friendship payment percent
     *
     * @var int
     */
    protected $percent = 0;

    /**
     * Payment type for friendship payments
     *
     * @var int
     */
    protected $payid = 1;

    public function __construct() {
        $this->loadAltCfg();
        $this->setPercent();
        $this->setPayid();
        $this->loadUsers();
        $this->loadFriends();
    }

    /**
     * Loads system alter config
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAltCfg() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets payments percent for further usage
     * 
     * @return void
     */
    protected function setPercent() {
        $this->percent = $this->altCfg['FRIENDSHIP_PERCENT'];
    }

    /**
     * Sets payments cashtype
     * 
     * @return void
     */
    protected function setPayid() {
        $this->payid = $this->altCfg['FRIENDSHIP_CASHTYPEID'];
    }

    /**
     * Loads all of existing friends relations from database to protected property
     * 
     * @return void
     */
    protected function loadFriends() {
        $query = "SELECT * from `friendship`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allFriends[$each['friend']] = $each['parent'];
            }
        }
    }

    /**
     * Loads all of existing users from database to protected property
     * 
     * @return void
     */
    protected function loadUsers() {
        $all = zb_UserGetAllStargazerData();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Checks is user allowed to set as someone friend
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function isFriendable($login) {
        $result = true;
        if (isset($this->allFriends[$login])) {
            $result = false;
        }
        return ($result);
    }

    /**
     * Creates friend-parent relationship as database record
     * 
     * @param string $login
     * @param string $parentLogin
     * 
     * @return void
     */
    public function createFriend($login, $parentLogin) {
        $loginF = mysql_real_escape_string($login);
        $parentLoginF = mysql_real_escape_string($parentLogin);
        if ($this->isFriendable($login)) {
            if (isset($this->allUsers[$login])) {
                if (isset($this->allUsers[$parentLogin])) {
                    $query = "INSERT INTO `friendship` (`id`, `friend`, `parent`) VALUES (NULL, '" . $loginF . "', '" . $parentLoginF . "'); ";
                    nr_query($query);
                    log_register('FRIENDSHIP CREATE `' . $login . '` PARENT `' . $parentLogin . '`');
                } else {
                    log_register('FRIENDSHIP CREATE FAIL `' . $login . '` NOT_EXISTS_PARENT');
                }
            } else {
                log_register('FRIENDSHIP CREATE FAIL `' . $login . '` NOT_EXISTS');
            }
        } else {
            log_register('FRIENDSHIP CREATE FAIL `' . $login . '` BUSY');
        }
    }

    /**
     * Removes login from friendship relations
     * 
     * @param string $login
     * 
     * @return void
     */
    public function deleteFriend($login) {
        $loginF = mysql_real_escape_string($login);
        if (isset($this->allFriends[$login])) {
            $query = "DELETE from `friendship` WHERE `friend`='" . $loginF . "';";
            nr_query($query);
            log_register('FRIENDSHIP DELETE `' . $login . '`');
        } else {
            log_register('FRIENDSHIP DELETE FAIL `' . $login . '` NOT_EXISTS');
        }
    }

    /**
     * Renders friendship creation form
     * 
     * @param string $parentLogin
     * @return string
     */
    public function renderCreateForm($parentLogin) {
        $inputs = wf_HiddenInput('newparentlogin', $parentLogin);
        $inputs.= wf_TextInput('newfriendlogin', __('Login'), '', false, '15');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders list of associated friend users for some parent login
     * 
     * @param string $parentLogin
     * 
     * @return string
     */
    public function renderFriendsList($parentLogin) {
        $result = '';
        $allRealnames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $messages = new UbillingMessageHelper();
        $friendCount = 0;

        if (!empty($this->allFriends)) {
            $cells = wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Full address'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allFriends as $friendLogin => $parent) {
                if ($parent == $parentLogin) {
                    $cells = wf_TableCell(wf_Link('?module=userprofile&username=' . $friendLogin, web_profile_icon() . ' ' . $friendLogin));
                    $cells.= wf_TableCell(@$allRealnames[$friendLogin]);
                    $cells.= wf_TableCell(@$allAddress[$friendLogin]);
                    $actLinks = wf_JSAlert('?module=pl_friendship&username=' . $parentLogin . '&deletefriend=' . $friendLogin, web_delete_icon(), $messages->getDeleteAlert());
                    $cells.= wf_TableCell($actLinks);
                    $rows.= wf_TableRow($cells, 'row2');
                    $friendCount++;
                }
            }

            if ($friendCount > 0) {
                $result = wf_TableBody($rows, '100%', 0, 'sortable');
                $result.= __('Total').': '.$friendCount;
            } else {
                $result = $messages->getStyledMessage(__('Nothing found'), 'info');
            }
        } else {
            $result = $messages->getStyledMessage(__('Nothing found'), 'info');
        }



        return ($result);
    }

    /**
      Подумай кто твои друзья в этом мире
      С кем судьба свела тебя не зря, и мир стал шире
      С кем за горизонт готов идти ради света
      Кто поможет вовремя найти все ответы
     */

    /**
     * Loads yesterday payments
     * 
     * @return void
     */
    protected function loadDailyPayments() {
        $curdate = curdate();
        $query = "SELECT * from `payments` WHERE DATE(`date`) = (CURDATE() - INTERVAL 1 DAY)";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->rawPayments[$each['id']] = $each;
            }
        }
    }

    /**
     * Performs friends yesterday payments processing
     * 
     * @return void
     */
    public function friendsDailyProcessing() {
        $this->loadDailyPayments();
        if (!empty($this->rawPayments)) {
            foreach ($this->rawPayments as $paymentId => $eachPayment) {
                if (isset($this->allFriends[$eachPayment['login']])) {
                    $friendLogin = $eachPayment['login'];
                    $parentLogin = $this->allFriends[$eachPayment['login']];
                    $originalSum = $eachPayment['summ'];
                    $percent = zb_Percent($originalSum, $this->percent);
                    zb_CashAdd($parentLogin, $percent, 'add', $this->payid, 'FRIENDSHIP:' . $eachPayment['id']);
                }
            }
        }
    }

}

?>