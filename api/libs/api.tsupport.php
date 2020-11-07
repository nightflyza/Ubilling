<?php

/**
 * Looks like forgotten external applications interraction interface
 */
class TSupportApi {

    /**
     * Stores system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all of available tariffs data as tariffname=>data
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all of available tariff speeds as tariffname=>data (speeddown/speedup keys)
     *
     * @var array
     */
    protected $allTariffSpeeds = array();

    /**
     * Contains all tariffs periods as tariffname=>period (month/day)
     *
     * @var array
     */
    protected $allTariffPeriods = array();

    /**
     * Contains available cities as cityid=>data
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains available streets array as streetid=>data
     *
     * @var array
     */
    protected $allStreets = array();

    /**
     * Contains all available builds array as buildid=>builddata
     *
     * @var array
     */
    protected $allBuilds = array();

    /**
     * Contains available custom fields types as id=>name
     *
     * @var array
     */
    protected $allCfTypes = array();

    /**
     * Contains available custom fields data as login+cftypeid=>data
     *
     * @var array
     */
    protected $allCfData = array();

    /**
     * Contains data of all available Internet users as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains available tag types as id=>name
     *
     * @var array
     */
    protected $allTagTypes = array();

    /**
     * Contains supported methods list
     *
     * @var array
     */
    protected $supportedMethods = array();

    /**
     * Default streets type. May be configurable in future
     *
     * @var string
     */
    protected $defaultStreetType = '';

    /**
     * Default city type. May be configurable in future
     *
     * @var string
     */
    protected $defaultCityType = '';
    protected $allRealNames = array();
    protected $userInfo = array();
    protected $debugMode = false;

    public function __construct() {
        $this->setOptions();
        $this->loadAlter();
    }

    /**
     * Loads system alter config into private property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets object default properties
     * 
     * @return void
     */
    protected function setOptions() {
        $this->defaultStreetType = __('st.');
        $this->defaultCityType = __('ct.');
        $this->supportedMethods = array(
            'get_supported_method_list' => __('Returns supported methods list'),
            'get_api_information' => __('Returns UserSide API version'),
            'get_tariff_list' => __('Returns available tariffs'),
            'get_city_list' => __('Returns available cities data'),
            'get_street_list' => __('Returns available streets data'),
            'get_house_list' => __('Returns available builds data'),
            'get_user_additional_data_type_list' => __('Returns user profile custom fields data'),
            'get_user_state_list' => __('Returns users state data'),
            'get_user_group_list' => __('Returns user tags list'),
            'get_system_information' => __('Returns system information'),
            'get_user_list' => __('Returns available users data'),
            'get_realnames' => '',
            'get_user_info' => ''
        );
    }

    protected function loadRealNames($like) {
        $like_esc = mysql_real_escape_string($like);
        $query = 'SELECT * FROM `realname` WHERE `realname` LIKE "%' . $like_esc . '%"';
        $all = simple_queryall($query);
        if (!empty($all)) {
            $i = 0;
            foreach ($all as $each) {
                $this->allRealNames[] = $each['login'] . '|' . $each['realname'];
            }
        }
    }

    protected function loadUserInfo($login) {
        $login_esc = mysql_real_escape_string($login);
        $users_qry = 'SELECT * FROM `users` WHERE `login` = "' . $login_esc . '"';
        $users = simple_query($users_qry);
        if (!empty($users)) {
            $names_qry = 'SELECT * FROM `realname` WHERE `login` ="' . $login_esc . '"';
            $tariff_speed_qry = 'SELECT * FROM `speeds` WHERE `tariff` = "' . $users['Tariff'] . '"';
            $tariffs_qry = 'SELECT * FROM `tariffs` WHERE `name`="' . $users['Tariff'] . '"';
            $tariffs = simple_query($tariffs_qry);
            $names = simple_query($names_qry);
            $tariffSpeed = simple_query($tariff_speed_qry);

            $this->userInfo['Cash'] = $users['Cash'];
            if ($users['Down']) {
                $this->userInfo['Down'] = __('Yes');
            } else {
                $this->userInfo['Down'] = __('No');
            }
            if ($users['AlwaysOnline']) {
                $this->userInfo['AlwaysOnline'] = __('Yes');
            } else {
                $this->userInfo['AlwaysOnline'] = __('No');
            }
            $this->userInfo['IP'] = $users['IP'];
            if (!empty($names['realname'])) {
                $this->userInfo['Name'] = $names['realname'];
            }
            if ($users['Passive']) {
                $this->userInfo['Passive'] = __('Yes');
            } else {
                $this->userInfo['Passive'] = __('No');
            }
            $this->userInfo['Tariff'] = $users['Tariff'];
            $this->userInfo['Credit'] = $users['Credit'];
            $this->userInfo['TariffCost'] = $tariffs['Fee'];
            $this->userInfo['TariffPeriod'] = __($tariffs['period']);
            $this->userInfo['SpeedDown'] = $tariffSpeed['speeddown'] . 'Kbit/s';
            $this->userInfo['SpeedUp'] = $tariffSpeed['speedup'] . 'Kbit/s';
        }
    }

    /**
     * Renders API reply as JSON string
     * 
     * @param array $data
     * 
     * @rerutn void
     */
    protected function renderReply($data) {
        $result = 'undefined';
        if (!$this->debugMode) {
            header('Content-Type: application/json');
            if (!empty($data)) {
                $result = json_encode($data);
            }
            die($result);
        } else {
            debarr($data);
        }
    }

    /**
     * Returns users states data
     * 
     * @return array
     */
    protected function getUsersStateList() {
        $result = array();

        $result[5]['id'] = 5;
        $result[5]['name'] = __('Active');
        $result[5]['functional'] = 'work';

        $result[1]['id'] = 1;
        $result[1]['name'] = __('Debt');
        $result[1]['functional'] = 'nomoney';

        $result[2]['id'] = 2;
        $result[2]['name'] = __('User passive');
        $result[2]['functional'] = 'pause';

        $result[3]['id'] = 3;
        $result[3]['name'] = __('User down');
        $result[3]['functional'] = 'disable';

        $result[4]['id'] = 4;
        $result[4]['name'] = __('No tariff');
        $result[4]['functional'] = 'new';

        return ($result);
    }

    /**
     * Returns available methods array
     * 
     * @return array
     */
    protected function getMethodsList() {
        $result = array();
        if (!empty($this->supportedMethods)) {
            foreach ($this->supportedMethods as $io => $each) {
                $result[$io]['comment'] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns Userside API information
     * 
     * @return array
     */
    protected function getApiInformation() {
        $result = array();
        $result['version'] = self::API_VER;
        $result['date'] = self::API_DATE;
        return ($result);
    }

    /**
     * Listens API requests and renders replies for it
     * 
     * @return void
     */
    public function catchRequest() {
        if (wf_CheckGet(array('request'))) {
            $request = $_GET['request'];
            if (isset($this->supportedMethods[$request])) {
                switch ($request) {
                    case 'get_user_info':
                        if (isset($_GET['username'])) {
                            $this->loadUserInfo($_GET['username']);
                            $this->renderReply($this->userInfo);
                        }
                        break;
                    case 'get_realnames':
                        if (isset($_GET['like'])) {
                            $this->loadRealNames($_GET['like']);
                            $this->renderReply($this->allRealNames);
                        }
                        break;
                }
            } else {
                header('HTTP/1.1 400 Unknown Action"', true, 400);
                die('Unknown Action');
            }
        } else {
            header('HTTP/1.1 400 Undefined request', true, 400);
            die('Undefined request');
        }
    }

}

?>
