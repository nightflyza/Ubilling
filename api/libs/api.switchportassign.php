<?php

class SwitchPortAssign {

    /**
     * Contains full array data for port switch assign as key=>value
     *
     * @var array
     */
    protected $allPortSwitchData = array();

    /**
     * Contains array with login to port switch assign as key=>value
     *
     * @var array [login]=> 'data'
     */
    protected $data = array();

    /**
     * Contains available user address data as login=>address
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * Contains available user FIO
     *
     * @var array
     */
    protected $allrealnames = array();

    protected $allusers = array();
    protected $diff = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    const USERS_TABLE = 'users';
    const PONONU_TABLE = 'pononu';
    const SWITCHPORTASSIGN_TABLE = 'switchportassign';
    const URL_ME_SWA = '?module=report_switchportassign';
    const URL_ME_NOSWA = '?module=report_switchportassign';

    /**
     * Contains user navigation URL
     */
    const URL_USERPROFILE = '?module=userprofile&username=';

    /**
     * SwitchId
     *
     * @var int
     */
    protected $switchID = '';

    public function __construct() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();

        //set switchId
        $this->setSwitchId();
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
    protected function setSwitchId() {
        if (ubRouting::checkGet('switchid')) {
            $switchId = ubRouting::get('switchid');
            $this->switchID = ubRouting::filters($switchId, 'int');
        }
    }

    /**
     * get all users with switch port assing and push it into data prop
     * 
     * @return void
     */
    protected function loadData() {
        $switchPortAssign = new NyanORM(self::SWITCHPORTASSIGN_TABLE);
        $switchPortAssign->selectable('`switchportassign`.`id`,`port`,`login`,`ip`,`location`,`switchid`,`sw`.`id` swid');
        $switchPortAssign->joinOn('LEFT', '(SELECT * FROM `switches`) as sw', '`switchportassign`.`switchid` = `sw`.`id`', true);
        if (!empty($this->switchID)) {
            $switchPortAssign->where('switchid', '=', $this->switchID);
        }

        $this->allPortSwitchData = $switchPortAssign->getAll();
        if (!empty($this->allPortSwitchData)) {
            foreach ($this->allPortSwitchData as $io => $rawData) {
                $this->data[$rawData['login']] = $rawData;
            }
        }
    }

    /**
     * get all users logins and push it into allusers prop
     * 
     * @return void
     */
    protected function loadAllUsers() {
        $users = new NyanORM(self::USERS_TABLE);
        $users->selectable('login');
        if (isset($this->altCfg['SWITCHPORT_REPORT_IGNORE_PON'])) {
            if ($this->altCfg['SWITCHPORT_REPORT_IGNORE_PON']) {
                $users->join('LEFT', self::PONONU_TABLE, 'login');
                $users->where('pononu.login', 'IS', 'NULL');
            }
        }

        $allDataUsers = $users->getAll();

        if (!empty($allDataUsers)) {
            foreach ($allDataUsers as $io => $each) {
                $this->allusers[$each['login']] = $each['login'];
            }
        }
    }

    /**
     * Loads users data
     * 
     * @return void
     */
    public function loadUsersData() {
        $this->loadAllRealnames();
        $this->loadAddressData();
    }
    
    /**
     * Loads address data required for user telepathy into protected property
     * 
     * @return void
     */
    protected function loadAllRealnames() {
        $this->allrealnames = zb_UserGetAllRealnames();
    }

    /**
     * Loads address data required for user telepathy into protected property
     * 
     * @return void
     */
    protected function loadAddressData() {
        $this->allAddress = zb_AddressGetFulladdresslistCached();
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
                if (!isset($this->data[$each]) OR empty($this->data[$each]['ip'])) {
                    $this->diff[$each] = $each;
                }
            }
        }
        $result = web_UserArrayShower($this->diff);
        return ($result);
    }

    /**
     * renders report by existing protected data prop
     * 
     * @return string
     */
    public function renderSwitchPortAssign() {
        if (empty($this->switchID)) {
            $result = '';
            $dopUrl = '';
        } else {
            $result = show_window('', wf_BackLink("?module=switches&edit=" . $this->switchID));
            $dopUrl = '&switchid=' . $this->switchID;
        }

        $columns = array('ID', 'IP', 'Port', 'Location', 'Switch', 'User', 'Full address');
        $opts = '"order": [[ 0, "desc" ]], "dom": \'<"F"lfB>rti<"F"ps>\', buttons: [\'csv\', \'excel\', \'pdf\']';
        $result.= wf_JqDtLoader($columns, self::URL_ME_SWA . '&ajaxswitchassign=true' . $dopUrl, false, 'Switch port assign', 100, $opts);
        return ($result);
    }

    /**
     * Renders json formatted data about switch ports assign
     * 
     * @return void
     */
    public function ajaxAvaibleSwitchPortAssign() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allPortSwitchData)) {
            foreach ($this->allPortSwitchData as $io => $raw) {

                $data[] = $raw['id'];
                $data[] = $raw['ip'];
                $data[] = $raw['port'];
                $data[] = $raw['location'];
                $data[] = $raw['ip'] . ' - ' . $raw['location'] . ' ' . __('Port') . ' ' . $raw['port'];
                $data[] = wf_Link(self::URL_USERPROFILE . $raw['login'], web_profile_icon() . ' ' . @$this->allrealnames[$raw['login']]) . '(' . $raw['login'] . ')';
                $data[] = @$this->allAddress[$raw['login']];

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

}
?>