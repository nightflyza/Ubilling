<?php

class UserSideApi {

    const API_VER = '1.2';
    const API_DATE = '26.10.2015';

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
    protected $debugMode = false;

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadTariffs();
        $this->loadTariffSpeeds();
        $this->loadTariffPeriods();
        $this->loadCities();
        $this->loadStreets();
        $this->loadBuilds();
        $this->loadCF();
        $this->loadUsers();
        $this->loadTagTypes();
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
            'get_user_list' => __('Returns available users data')
        );
    }

    /**
     * Loads all existing Internet users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $all = zb_UserGetAllStargazerData();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUserData[$each['login']] = $each;
            }
        }
    }

    /**
     * Loads existing tariffs from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['name']] = $each;
            }
        }
    }

    /**
     * Loads existing tariff speeds from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffSpeeds() {
        $query = "SELECT * from `speeds`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffSpeeds[$each['tariff']] = $each;
            }
        }
    }

    /**
     * Loads existing tariff periods from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffPeriods() {
        $this->allTariffPeriods = zb_TariffGetPeriodsAll();
    }

    /**
     * Loads existing cities from database
     * 
     * @return void
     */
    protected function loadCities() {
        $query = "SELECT * from `city`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCities[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing builds from database
     * 
     * @return void
     */
    protected function loadBuilds() {
        $query = "SELECT * from `build`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allBuilds[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing streets from database
     * 
     * @return void
     */
    protected function loadStreets() {
        $query = "SELECT * from `street`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allStreets[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing custom fields data from database
     * 
     * @return void
     */
    protected function loadCF() {
        //getting CF types
        $query = "SELECT * from `cftypes`";
        $allCfTypes = simple_queryall($query);
        if (!empty($allCfTypes)) {
            foreach ($allCfTypes as $io => $eachTypeData) {
                $this->allCfTypes[$eachTypeData['id']] = $eachTypeData['name'];
            }

            //getting users assigned CF content
            $query = "SELECT * from `cfitems`";
            $allCfData = simple_queryall($query);
            $i = 0;
            if (!empty($allCfData)) {
                foreach ($allCfData as $io => $each) {
                    if (!empty($each['content'])) {
                        $this->allCfData[$each['login']][$i]['id'] = $each['typeid'];
                        $this->allCfData[$each['login']][$i]['value'] = $each['content'];
                    }
                    $i++;
                }
            }
        }
    }

    /**
     * Returns  all users registration dates as login=>date
     * 
     * @return array
     */
    protected function getUserRegData() {
        $result = array();
        $query = "SELECT * from `userreg`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['login']] = $each['date'];
            }
        }
        return ($result);
    }

    /**
     * Loads existing tag types from database
     * 
     * @return void
     */
    protected function loadTagTypes() {
        $query = "SELECT * from `tagtypes`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTagTypes[$each['id']] = $each['tagname'];
            }
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
     * Returns array of all of existing tariffs data
     * 
     * @return array
     */
    protected function getTariffsData() {
        $result = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                $result[$tariffName]['id'] = $tariffName;
                $result[$tariffName]['name'] = $tariffName;
                $result[$tariffName]['payment'] = $tariffData['Fee'];
                $result[$tariffName]['payment_interval'] = ($this->allTariffPeriods[$tariffName] == 'month') ? 30 : 1;
                $downspeed = (isset($this->allTariffSpeeds[$tariffName]['speeddown'])) ? $this->allTariffSpeeds[$tariffName]['speeddown'] : 0;
                $upspeed = (isset($this->allTariffSpeeds[$tariffName]['speedup'])) ? $this->allTariffSpeeds[$tariffName]['speedup'] : 0;
                $result[$tariffName]['speed'] = array(
                    'up' => $upspeed,
                    'down' => $downspeed,
                );
                $result[$tariffName]['traffic'] = ($tariffData['Free']) ? $tariffData['Free'] : -1;
            }
        }
        return ($result);
    }

    /**
     * Returns city data array
     * 
     * @return array
     */
    protected function getCitiesData() {
        $result = array();
        if (!empty($this->allCities)) {
            foreach ($this->allCities as $cityId => $cityData) {
                $result[$cityId]['id'] = $cityId;
                $result[$cityId]['name'] = $cityData['cityname'];
                $result[$cityId]['type_name'] = $this->defaultCityType;
            }
        }
        return ($result);
    }

    /**
     * Returns streets data array
     * 
     * @return array
     */
    protected function getStreetsData() {
        $result = array();
        if (!empty($this->allStreets)) {
            foreach ($this->allStreets as $streetId => $streetData) {
                $result[$streetId]['id'] = $streetId;
                $result[$streetId]['city_id'] = $streetData['cityid'];
                $result[$streetId]['name'] = $streetData['streetname'];
                $result[$streetId]['type_name'] = $this->defaultStreetType;
                $result[$streetId]['full_name'] = $this->defaultStreetType . ' ' . $streetData['streetname'];
            }
        }
        return ($result);
    }

    /**
     * Returns streets data array
     * 
     * @return array
     */
    protected function getBuildsData() {
        $result = array();
        if (!empty($this->allBuilds)) {
            foreach ($this->allBuilds as $buildId => $buildData) {
                $result[$buildId]['id'] = $buildId;
                $streetId = $buildData['streetid'];
                $streetName = @$this->allStreets[$streetId]['streetname'];
                $result[$buildId]['city_district_id'] = '';
                $result[$buildId]['street_id'] = $buildData['streetid'];
                $result[$buildId]['full_name'] = $streetName . ' ' . $buildData['buildnum'];
                $result[$buildId]['postcode'] = '';
                if (ispos($buildData['buildnum'], '/')) {
                    $buildExpl = explode('/', $buildData['buildnum']);
                    @$buildNumber = vf($buildExpl[0], 3);
                    @$blockLetter = $buildExpl[1];
                } else {
                    $buildNumber = vf($buildData['buildnum'], 3);
                    $blockLetter = preg_replace('/\P{L}+/u', '', $buildData['buildnum']);
                    $blockLetter = trim($blockLetter);
                }

                $result[$buildId]['number'] = $buildNumber;
                $result[$buildId]['block'] = $blockLetter;
            }
        }
        return ($result);
    }

    /**
     * Returns customfield types data
     * 
     * @return array
     */
    protected function getCFTypesData() {
        $result = array();
        if (!empty($this->allCfTypes)) {
            foreach ($this->allCfTypes as $cftypeId => $eachCftypeName) {
                $result[$cftypeId]['id'] = $cftypeId;
                $result[$cftypeId]['name'] = $eachCftypeName;
            }
        }
        return ($result);
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
     * Returns available tag types data
     * 
     * @return array
     */
    protected function getTagTypesList() {
        $result = array();
        /* Правда твоя - теплый асфальт */
        if (!empty($this->allTagTypes)) {
            foreach ($this->allTagTypes as $tagId => $eachTagName) {
                $result[$tagId]['id'] = $tagId;
                $result[$tagId]['name'] = $eachTagName;
            }
        }
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
     * Returns server system information
     * 
     * @return array
     */
    protected function getSystemInformation() {
        $result = array();
        $curdate = curdate();
        $operatingSystem = shell_exec('uname');
        $billingVersion = file_get_contents('RELEASE');

        $result['date'] = $curdate;
        $result['os'] = trim($operatingSystem);
        $result['billing']['name'] = 'Ubilling';
        $result['billing']['version'] = trim($billingVersion);
        return ($result);
    }

    /**
     * Returns array of all tagtypes set to users
     * 
     * @return array
     */
    protected function getAllUsersTags() {
        $result = array();
        $query = "SELECT * from `tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['login']][] = $each['tagid'];
            }
        }

        return ($result);
    }

    /**
     * Returns array of all available PaymentIDs
     * 
     * @return array
     */
    protected function getAllUserPaymentIds() {
        $result = array();
        if ($this->altCfg['OPENPAYZ_REALID']) {
            $query = "SELECT * from `op_customers`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $result[$each['realid']] = $each['virtualid'];
                }
            }
        } else {
            if (!empty($this->allUserData)) {
                foreach (@$this->allUserData as $io => $each) {
                    $result[$each['login']] = ip2int($each['IP']);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of all available login=>apt bindings
     * 
     * @return array
     */
    protected function getAllAddressList() {
        $result = array();
        $query = "SELECT * from `address`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['login']] = $each['aptid'];
            }
        }
        return ($result);
    }

    /**
     * Returns array of all available apartments data as id=>data
     * 
     * @return array
     */
    protected function getAllAptList() {
        $result = array();
        $query = "SELECT * from `apt`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['id']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns array of all nethosts data as ip=>data
     * 
     * @return array
     */
    protected function getNethostsData() {
        $result = array();
        $query = "SELECT * from `nethosts`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['ip']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns data of available multinet networks as netid=>data
     * 
     * @return array
     */
    protected function getNetworksData() {
        $result = array();
        $query = "SELECT * from `networks`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['id']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns existing users full info
     * 
     * @return array
     */
    protected function getUsersList() {
        $result = array();
        $allRealNames = zb_UserGetAllRealnames();
        $allContracts = zb_UserGetAllContracts();
        $allUserTags = $this->getAllUsersTags();
        $allUserNotes = zb_UserGetAllNotes();
        $allPaymentIds = $this->getAllUserPaymentIds();
        $allAddresBindings = $this->getAllAddressList();
        $allAptData = $this->getAllAptList();
        $allPhones = zb_UserGetAllPhoneData();
        $allEmails = zb_UserGetAllEmails();
        $allNethosts = $this->getNethostsData();
        $allNetworks = $this->getNetworksData();
        $allRegData = $this->getUserRegData();

        if (!empty($allContracts)) {
            $allContracts = array_flip($allContracts);
        }
        $allContractDates = zb_UserContractDatesGetAll();

        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $userLogin => $userData) {
                $result[$userLogin]['id'] = $userLogin;
                $result[$userLogin]['login'] = $userLogin;
                $result[$userLogin]['full_name'] = @$allRealNames[$userLogin];
                $result[$userLogin]['flag_corporate'] = 0;

                if ($userData['TariffChange']) {
                    $curMonth = date('n');
                    $curYear = date('Y');
                    $firstDayNextMonth = ($curMonth == 12) ? mktime(0, 0, 0, 0, 0, $curYear + 1) : mktime(0, 0, 0, $curMonth + 1, 1);
                    $firstDayNextMonth = date("Y-m-d", $firstDayNextMonth);
                } else {
                    $firstDayNextMonth = '';
                }
                $result[$userLogin]['tariff']['current'][0]['id'] = $userData['Tariff'];
                if ($firstDayNextMonth) {
                    $result[$userLogin]['tariff']['current'][0]['date_finish'] = $firstDayNextMonth;
                }

                if ($userData['TariffChange']) {
                    $result[$userLogin]['tariff']['new'][0]['id'] = $userData['TariffChange'];
                    $result[$userLogin]['tariff']['new'][0]['date_start'] = $firstDayNextMonth;
                }

                $userContract = @$allContracts[$userLogin];
                if ($userContract) {
                    $result[$userLogin]['agreement'][0]['number'] = $userContract;
                    $contractDate = @$allContractDates[$userContract];
                    if ($contractDate) {
                        $result[$userLogin]['agreement'][0]['date'] = $contractDate;
                    }
                }

                $result[$userLogin]['account_number'] = @$allPaymentIds[$userLogin]; // yep, this is something like Payment ID

                if (isset($allUserTags[$userLogin])) {
                    foreach ($allUserTags[$userLogin] as $tagIo => $eachTagid) {
                        $result[$userLogin]['group'][$tagIo] = $eachTagid;
                    }
                }

                $userNotes = @$allUserNotes[$userLogin];
                if ($userNotes) {
                    $result[$userLogin]['comment'] = $userNotes;
                }
                $result[$userLogin]['balance'] = $userData['Cash'];
                $result[$userLogin]['credit'] = $userData['Credit'];

                $userState = 5; // work
                if ($userData['Cash'] < '-' . $userData['Credit']) {
                    $userState = 1; //nomoney
                }
                if ($userData['Passive'] == 1) {
                    $userState = 2; // pause
                }
                if ($userData['Down'] == 1) {
                    $userState = 3; // disable
                }
                if ($userData['Tariff'] == '*_NO_TARIFF_*') {
                    $userState = 4; // new
                }
                $result[$userLogin]['state_id'] = $userState;

                if (isset($allRegData[$userLogin])) {
                    $result[$userLogin]['date_create'] = $allRegData[$userLogin];
                    $result[$userLogin]['date_connect'] = $allRegData[$userLogin];
                } else {
                    $result[$userLogin]['date_create'] = '';
                    $result[$userLogin]['date_connect'] = '';
                }

                if ($this->altCfg['DN_FULLHOSTSCAN']) {
                    $dnFilePath = DATA_PATH . 'dn/' . $userLogin;
                    if (file_exists($dnFilePath)) {
                        $actTimestamp = filemtime($dnFilePath);
                        $result[$userLogin]['date_activity'] = date("Y-m-d H:i:s", $actTimestamp);
                    } else {
                        $result[$userLogin]['date_activity'] = '';
                    }
                } else {
                    $result[$userLogin]['date_activity'] = date("Y-m-d H:i:s", $userData['LastActivityTime']);
                }


                $result[$userLogin]['traffic']['month']['up'] = $userData['U0'];
                $result[$userLogin]['traffic']['month']['down'] = $userData['D0'];
                $result[$userLogin]['discount'] = 0; // TODO: to many discount models at this time


                $userApartmentId = @$allAddresBindings[$userLogin];
                if ($userApartmentId) {
                    $aptData = $allAptData[$userApartmentId];
                    $result[$userLogin]['address'][0]['type'] = 'connect';
                    $result[$userLogin]['address'][0]['house_id'] = $aptData['buildid'];
                    $result[$userLogin]['address'][0]['apartment']['id'] = $userApartmentId;
                    $result[$userLogin]['address'][0]['apartment']['full_name'] = $aptData['apt'];
                    $result[$userLogin]['address'][0]['apartment']['number'] = vf($aptData['apt'], 3);
                    if ($aptData['entrance']) {
                        $result[$userLogin]['address'][0]['entrance'] = $aptData['entrance'];
                    }
                    if ($aptData['floor']) {
                        $result[$userLogin]['address'][0]['floor'] = $aptData['floor'];
                    }
                }


                $userPhoneData = @$allPhones[$userLogin];
                if (!empty($userPhoneData)) {
                    if (isset($userPhoneData['phone'])) {
                        $result[$userLogin]['phone'][0]['number'] = $userPhoneData['phone'];
                        $result[$userLogin]['phone'][0]['flag_main'] = 0;
                    }
                    if (isset($userPhoneData['mobile'])) {
                        $result[$userLogin]['phone'][1]['number'] = $userPhoneData['mobile'];
                        $result[$userLogin]['phone'][1]['flag_main'] = 1;
                    }
                }

                $userEmail = @$allEmails[$userLogin];
                if ($userEmail) {
                    $result[$userLogin]['email'][0]['address'] = $userEmail;
                    $result[$userLogin]['email'][0]['flag_main'] = 1;
                }

                $userIp = $userData['IP'];
                $userIp = ip2int($userIp);
                $result[$userLogin]['ip_mac'][0]['ip'] = $userIp;
                $nethostsData = @$allNethosts[$userData['IP']];
                if (!empty($nethostsData)) {
                    $subnetId = $nethostsData['netid'];
                    $userMac = $nethostsData['mac'];
                    $userMac = str_replace(':', '', $userMac);
                    $userMac = strtolower($userMac); // mac lowercased withot delimiters
                    $result[$userLogin]['ip_mac'][0]['mac'] = $userMac;
                    $result[$userLogin]['ip_mac'][0]['ip_net'] = @$allNetworks[$subnetId]['desc'];
                }

                if (isset($this->allCfData[$userLogin])) {
                    $result[$userLogin]['additional_data'] = $this->allCfData[$userLogin];
                }
                //   die(print_r($result, true));
            }
        }

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
                    case 'get_tariff_list':
                        $this->renderReply($this->getTariffsData());
                        break;
                    case 'get_city_list':
                        $this->renderReply($this->getCitiesData());
                        break;
                    case 'get_street_list':
                        $this->renderReply($this->getStreetsData());
                        break;
                    case 'get_house_list':
                        $this->renderReply($this->getBuildsData());
                        break;
                    case 'get_user_additional_data_type_list':
                        $this->renderReply($this->getCFTypesData());
                        break;
                    case 'get_user_state_list':
                        $this->renderReply($this->getUsersStateList());
                        break;
                    case 'get_supported_method_list':
                        $this->renderReply($this->getMethodsList());
                        break;
                    case 'get_api_information':
                        $this->renderReply($this->getApiInformation());
                        break;
                    case 'get_user_group_list':
                        $this->renderReply($this->getTagTypesList());
                        break;
                    case 'get_system_information':
                        $this->renderReply($this->getSystemInformation());
                        break;
                    case 'get_user_list':
                        $this->renderReply($this->getUsersList());
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
