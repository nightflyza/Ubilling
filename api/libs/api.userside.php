<?php

class UserSideApi {

    const API_VER = '1.5';
    const API_DATE = '06.10.2017';

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
     * Contains supported methods for change_user_data request
     *
     * @var array
     */
    protected $supportedChangeMethods = array();

    /**
     * Contains supported user states to change
     *
     * @var array
     */
    protected $supportedChageUserState = array();

    /**
     * Contains localised error notices
     *
     * @var array
     */
    protected $errorNotices = array();

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

    /**
     * Contains build passports data as buildid=>data
     *
     * @var array
     */
    protected $buildPassports = array();

    /**
     * Contains list of available virtual services as id=>data
     *
     * @var array
     */
    protected $vServices = array();

    /**
     * Contains virtual services to user tags mappings as tagid=>serviceid
     *
     * @var array
     */
    protected $serviceTagMappings = array();

    /**
     * Contains available device types as id=>name
     *
     * @var array
     */
    protected $allDeviceTypes = array();

    /**
     * Contains available devices models as id=>modeldata
     *
     * @var array
     */
    protected $allSwitchModels = array();

    /**
     * Contains available devices directory as id=>devicedata
     *
     * @var array
     */
    protected $allSwitches = array();

    /**
     * Debug mode flag
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Creates new instance of basic UserSide API
     */
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
        $this->loadBuildPassports();
        $this->loadVservices();
        $this->loadSwitchModels();
        $this->loadSwitchesAll();
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
            'get_user_tags' => __('Returns available users tags'),
            'get_services_list' => __('Returns available user services'),
            'get_user_history' => __('Returns user financial operations history'),
            'get_user_messages' => __('Previous user tickets'),
            'change_user_data' => __('Do some changes with user data'),
            'get_supported_change_user_data_list' => __('Returns list of supported change user data methods'),
            'get_supported_change_user_state' => __('Returns list of supported change user states'),
            'get_supported_change_user_tariff' => __('Returns list of supported change user tariffs'),
            'get_device_type' => __('Returns device type'),
            'get_device_model' => __('Returns device model'),
            'get_device_list' => __('Returns devices list'),
            'get_connect_list' => __('Get device connection list')
        );

        $this->supportedChangeMethods = array(
            'balance_operation' => __('User balance operations'),
            'name' => __('User name operations'),
            'comment' => __('User notes operations'),
            'tariff' => __('User tariff operations'),
            'state' => __('User state operations'),
        );

        $this->supportedChageUserState = array(
            'frozen' => __('Frozen user'),
            'unfrozen' => __('Not frozen user'),
            'down' => __('User down'),
            'notdown' => __('User not down'),
            'ao' => __('User AlwaysOnline'),
            'notao' => __('User not AlwaysOnline')
        );

        $this->allDeviceTypes = array(
            1 => 'switch',
            2 => 'radio',
            3 => 'olt',
            4 => 'onu',
            5 => 'other'
        );

        $this->errorNotices = array(
            'EX_NO_PARAMS' => __('No request parameters set'),
            'EX_USER_NOT_EXISTS' => __('No such user available'),
            'EX_PARAM_MISSED' => __('Important parameter missed'),
            'EX_METHOD_NOT_SUPPORTED' => __('Method not supported'),
            'EX_BAD_MONEY_FORMAT' => __('Wrong format of money sum'),
            'EX_WRONG_TARIFF' => __('Wrong tariff name')
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
     * Preloads available build passports for further usage
     * 
     * @return void
     */
    protected function loadBuildPassports() {
        if ($this->altCfg['BUILD_EXTENDED']) {
            $query = "SELECT * from `buildpassport`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->buildPassports[$each['buildid']] = $each;
                }
            }
        }
    }

    /**
     * Loads array of available virtual services
     * 
     * @return void
     */
    protected function loadVservices() {
        $query = "SELECT * from `vservices`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->vServices[$each['id']] = $each;
                $this->serviceTagMappings[$each['tagid']] = $each['id'];
            }
        }
    }

    /**
     * Loads available devices models from database
     * 
     * @return void
     */
    protected function loadSwitchModels() {
        $all = zb_SwitchModelsGetAll();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSwitchModels[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing devices directory from database
     * 
     * @return void
     */
    protected function loadSwitchesAll() {
        $all = zb_SwitchesGetAll();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSwitches[$each['id']] = $each;
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
     * @param bool $brief - brief tariffs listing only with names
     * 
     * @return array
     */
    protected function getTariffsData($brief = false) {
        $result = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                if (!$brief) {
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
                    $result[$tariffName]['service_type'] = 0;
                } else {
                    $result[$tariffName]['id'] = $tariffName;
                    $result[$tariffName]['name'] = $tariffName;
                }
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

                if (isset($this->buildPassports[$buildId])) {
                    $result[$buildId]['floor'] = $this->buildPassports[$buildId]['floors'];
                    $result[$buildId]['entrance'] = $this->buildPassports[$buildId]['entrances'];
                } else {
                    $result[$buildId]['floor'] = '';
                    $result[$buildId]['entrance'] = '';
                }

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
                $result[$buildId]['coordinates'] = $buildData['geo'];
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
     * Returns available change methods list
     * 
     * @return array
     */
    protected function getChangeMethodsList() {
        $result = array();
        if (!empty($this->supportedChangeMethods)) {
            foreach ($this->supportedChangeMethods as $io => $each) {
                $result[$io]['comment'] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns supported change user state options
     * 
     * @return array
     */
    protected function getChangeStateMethodsList() {
        $result = array();
        if (!empty($this->supportedChageUserState)) {
            foreach ($this->supportedChageUserState as $io => $each) {
                $result[$io] = $each;
            }
        }
        return ($result);
    }

    /**
     * Returns available devices types
     * 
     * @return array
     */
    protected function getDeviceTypesList() {
        $result = $this->allDeviceTypes;
        return ($result);
    }

    /**
     * Returns available device models
     * 
     * @return array
     */
    protected function getDeviceModels() {
        $result = array();
        if (!empty($this->allSwitchModels)) {
            foreach ($this->allSwitchModels as $io => $each) {
                $result[$each['id']]['id'] = $each['id'];
                $result[$each['id']]['type_id'] = ''; //empty now, because model don't know anything about this
                $result[$each['id']]['name'] = $each['modelname'];
                $result[$each['id']]['iface_count'] = $each['ports'];
            }
        }
        return ($result);
    }

    /**
     * Returns list of available devices in database
     * 
     * @param string $types
     * 
     * @return  array
     */
    protected function getDevicesList($types = '') {
        $result = array();
        if (!empty($this->allSwitches)) {
            $typesFilter = array(); //ids of device types for result rendering
            $switchCemetery = zb_SwitchesGetAllDeathTime(); //getting currently dead switches list
            if (!empty($types)) {
                //filters preprocessing
                $filtersTmp = explode(',', $types);
                if (!empty($filtersTmp)) {
                    foreach ($filtersTmp as $ia => $eachFilter) {
                        $tidTmp = array_search($eachFilter, $this->allDeviceTypes);
                        if ($tidTmp) {
                            $typesFilter[$tidTmp] = $this->allDeviceTypes[$tidTmp];
                        }
                    }
                }
            }
            foreach ($this->allSwitches as $io => $each) {
                //setting device type
                $deviceType = $this->getDeviceType($each['id']);

                //applying filters if required
                if (empty($typesFilter)) {
                    $filteredFlag = true;
                } else {
                    if (isset($typesFilter[$deviceType])) {
                        $filteredFlag = true;
                    } else {
                        $filteredFlag = false;
                    }
                }

                $switchIp = $each['ip'];
                if (isset($switchCemetery[$switchIp])) {
                    $lastActivityTime = $switchCemetery[$switchIp];
                } else {
                    $lastActivityTime = curdatetime();
                }
                $switchMac = (check_mac_format($each['swid'])) ? $each['swid'] : '';

                if ($filteredFlag) {
                    $result[$each['id']]['id'] = $each['id'];
                    $result[$each['id']]['type_id'] = $deviceType;
                    $result[$each['id']]['model_id'] = $each['modelid'];
                    $result[$each['id']]['ip'] = $each['ip'];
                    $result[$each['id']]['mac'] = $switchMac; //requires SWITCHES_EXTENDED option enabled.
                    $result[$each['id']]['house_id'] = ''; //no normal topology points at this moment
                    $result[$each['id']]['entrance'] = '';
                    $result[$each['id']]['floor'] = '';
                    $result[$each['id']]['node_id'] = '';
                    $result[$each['id']]['location'] = $each['location'];
                    $result[$each['id']]['geo'] = $each['geo'];
                    $result[$each['id']]['comment'] = $each['desc'];
                    $result[$each['id']]['date_activity'] = $lastActivityTime;
                    $result[$each['id']]['date_create'] = '';
                    $result[$each['id']]['snmp_version'] = '2c';
                    $result[$each['id']]['snmp_port'] = '161';
                    $result[$each['id']]['snmp_read_community'] = $each['snmp'];
                    $result[$each['id']]['software_version'] = '';
                }
            }
        }
        return ($result);
    }

    /**
     * Returns device type id
     * 
     * @param int $deviceId
     * 
     * @return int
     */
    protected function getDeviceType($deviceId) {
        //switch by default
        $result = 1;
        if (isset($this->allSwitches[$deviceId])) {
            if (ispos($this->allSwitches[$deviceId]['desc'], 'OLT')) {
                $result = 3;
            }

            if ((ispos($this->allSwitches[$deviceId]['desc'], 'MTSIGMON')) OR ( ispos($this->allSwitches[$deviceId]['desc'], 'AP')) OR ( ispos($this->allSwitches[$deviceId]['desc'], 'ssid:'))) {
                $result = 2;
            }
        }
        return ($result);
    }

    /**
     * Returns array of devices connection topology
     * 
     * @return array
     */
    protected function getDeviceConnectionsList() {
        $result = array();
        if (!empty($this->allSwitches)) {
            foreach ($this->allSwitches as $io => $each) {
                //setting device type
                $deviceType = $this->getDeviceType($each['id']);
                // We dont know anyting about is. Like John Snow, yeah.
                $uplinkPort = 1;
                //detecting, have device uplinks or not?
                if (!empty($each['parentid'])) {
                    //uplinks description
                    $result[$this->allDeviceTypes[$deviceType]][$each['id']][1][$uplinkPort][] = array(
                        'type' => $this->allDeviceTypes[$this->getDeviceType($each['parentid'])],
                        'id' => $each['parentid'],
                        'direction' => 1,
                        'interface' => $uplinkPort);

                    //downlinks description
                    $result[$this->allDeviceTypes[$this->getDeviceType($each['parentid'])]][$each['parentid']][1][$uplinkPort][] = array(
                        'type' => $this->allDeviceTypes[$deviceType],
                        'id' => $each['id'],
                        'direction' => 1,
                        'interface' => 2
                    );
                }
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
        $curdate = curdatetime();
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
                    $result[$each['login']] = ip2long($each['IP']);
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
     * Returns all users support tickets
     * 
     * @return array
     */
    protected function getUsersMessages() {
        $result = array();
        $query = "SELECT * from `ticketing` ORDER BY `id` ASC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                if (($each['from'] != 'NULL') AND ( empty($each['replyid']))) {
                    //original ticket body
                    $result[$each['id']]['id'] = $each['id'];
                    $result[$each['id']]['user_id'] = $each['from'];
                    $result[$each['id']]['msg_date'] = $each['date'];
                    $result[$each['id']]['subject'] = __('Ticket') . ' ' . $each['date'];
                    $result[$each['id']]['text'] = $each['text'];
                } else {
                    //thats replies for a ticket
                    if ((isset($result[$each['replyid']]))) {
                        $replyAuthor = (!empty($each['admin'])) ? $each['admin'] : $each['from'];
                        $result[$each['replyid']]['text'].=PHP_EOL . '=========' . PHP_EOL;
                        $result[$each['replyid']]['text'].=PHP_EOL . __('Message') . ' ' . $each['date'] . ' (' . $replyAuthor . ')';
                        $result[$each['replyid']]['text'].=': ' . PHP_EOL . $each['text'] . PHP_EOL;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns existing users full info
     * 
     * @param string $customerId
     * 
     * @return array
     */
    protected function getUsersList($customerId = '') {
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
        $scanData = array();

        if (!empty($allContracts)) {
            $allContracts = array_flip($allContracts);
        }
        $allContractDates = zb_UserContractDatesGetAll();

        //just one user
        if (!empty($customerId)) {
            if (isset($this->allUserData[$customerId])) {
                $scanData[$customerId] = $this->allUserData[$customerId];
            } else {
                $scanData = array();
            }
        } else {
            $scanData = $this->allUserData;
        }

        if (!empty($scanData)) {
            foreach ($scanData as $userLogin => $userData) {
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
                $userIp = ip2long($userIp);
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

                if (isset($allUserTags[$userLogin])) {
                    foreach ($allUserTags[$userLogin] as $tagIo => $eachTagid) {
                        $result[$userLogin]['tag'][$eachTagid]['id'] = $eachTagid;
                        $result[$userLogin]['tag'][$eachTagid]['date_add'] = '';
                    }
                }

                if (isset($allUserTags[$userLogin])) {
                    foreach ($allUserTags[$userLogin] as $tagIo => $eachTagid) {
                        if (isset($this->serviceTagMappings[$eachTagid])) {
                            $serviceId = $this->serviceTagMappings[$eachTagid];
                            $result[$userLogin]['service'][$serviceId]['cost'] = $this->vServices[$serviceId]['price'];
                            $result[$userLogin]['service'][$serviceId]['date_add'] = '';
                            $result[$userLogin]['service'][$serviceId]['comment'] = '';
                        }
                    }
                }

                if (isset($this->allCfData[$userLogin])) {
                    $result[$userLogin]['additional_data'] = $this->allCfData[$userLogin];
                }

                $result[$userLogin]['password'] = $userData['Password'];
            }
        }

        return ($result);
    }

    /**
     * Returns array of available tags
     * 
     * @return array
     */
    protected function getUserTags() {
        $result = array();
        if (!empty($this->allTagTypes)) {
            foreach ($this->allTagTypes as $tagId => $tagName) {
                $result[$tagId]['id'] = $tagId;
                $result[$tagId]['name'] = $tagName;
            }
        }
        return ($result);
    }

    /**
     * Returns list of available services
     * 
     * @return array
     */
    protected function getServicesList() {
        $result = array();
        if (!empty($this->vServices)) {
            foreach ($this->vServices as $serviceId => $serviceData) {
                $result[$serviceId]['id'] = $serviceId;
                $result[$serviceId]['name'] = $this->allTagTypes[$serviceData['tagid']];
                $result[$serviceId]['cost'] = $serviceData['price'];
            }
        }
        return ($result);
    }

    /**
     * Returns users finance operations history
     * 
     * @param string $customerId
     * 
     * @return array
     */
    protected function getUserFinanceHistory($customerId) {
        $result = array();
        if (isset($this->allUserData[$customerId])) {
            $allServices = zb_VservicesGetAllNamesLabeled();
            $fundsFlow = new FundsFlow();
            $allfees = $fundsFlow->getFees($customerId);
            $allpayments = $fundsFlow->getPayments($customerId);
            $allcorrectings = $fundsFlow->getPaymentsCorr($customerId);

            $allOps = $allfees + $allpayments + $allcorrectings;
            $allOps = $fundsFlow->transformArray($allOps);
            $i = 0;

            if (!empty($allOps)) {
                foreach ($allOps as $io => $each) {
                    // print_r($each);
                    $result[] = array(
                        'id' => $i,
                        'date' => $each['date'],
                        'type' => 'financial',
                        'name' => __($each['operation']),
                        'data' => json_encode(array(
                            'amount' => $each['summ'],
                            'from' => $each['from'],
                            'to' => $each['to'],
                            'operator_name' => $each['admin']
                        )),
                        'comment' => zb_TranslatePaymentNote($each['note'], $allServices)
                    );
                    $i++;
                }
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $customerId);
        }
        return ($result);
    }

    /**
     * Catches and preprocess change_user_data request params
     * 
     * @return array
     */
    protected function catchChangeParams() {
        $result = array();
        /**
         * There is a house in New Orleans
         * They call the Rising Sun
         * And it's been the ruin of many a poor boy
         * And God, I know I'm one
         */
        if (wf_CheckGet(array('customer_id'))) {
            $result['customerid'] = $_GET['customer_id'];
        }
        if (wf_CheckGet(array('type'))) {
            $result['type'] = vf($_GET['type']);
        }
        if (wf_CheckGet(array('value'))) {
            $result['value'] = $_GET['value'];
        }
        if (wf_CheckGet(array('comment'))) {
            $result['comment'] = $_GET['comment'];
        }
        return ($result);
    }

    /**
     * Do some user finance data changes
     * 
     * @param array $changeParams
     * 
     * @return array
     */
    protected function changeUserFinance($changeParams) {
        $result = array();
        if (isset($changeParams['customerid'])) {
            if (isset($this->allUserData[$changeParams['customerid']])) {
                if (isset($changeParams['value'])) {
                    if (zb_checkMoney($changeParams['value'])) {
                        $paymentNotes = (isset($changeParams['comment'])) ? $changeParams['comment'] : '';
                        zb_CashAdd($changeParams['customerid'], $changeParams['value'], 'add', 1, $paymentNotes);
                        $result = array('result' => 'ok');
                    } else {
                        $result = array('result' => 'error', 'error' => $this->errorNotices['EX_BAD_MONEY_FORMAT'] . ': ' . $changeParams['value']);
                    }
                } else {
                    $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': value');
                }
            } else {
                $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $changeParams['customerid']);
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id');
        }
        return ($result);
    }

    /**
     * Changes user RealName
     * 
     * @param array $changeParams
     * 
     * @return array
     */
    protected function changeUserRealName($changeParams) {
        $result = array();
        if (isset($changeParams['customerid'])) {
            if (isset($this->allUserData[$changeParams['customerid']])) {
                if (isset($changeParams['value'])) {
                    zb_UserChangeRealName($changeParams['customerid'], $changeParams['value']);
                    $result = array('result' => 'ok');
                } else {
                    $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': value');
                }
            } else {
                $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $changeParams['customerid']);
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id');
        }
        return ($result);
    }

    /**
     * Changes user notes
     * 
     * @param array $changeParams
     * 
     * @return array
     */
    protected function changeUserNotes($changeParams) {
        $result = array();
        if (isset($changeParams['customerid'])) {
            if (isset($this->allUserData[$changeParams['customerid']])) {
                if (isset($changeParams['value'])) {
                    zb_UserDeleteNotes($changeParams['customerid']);
                    zb_UserCreateNotes($changeParams['customerid'], $changeParams['value']);
                    $result = array('result' => 'ok');
                } else {
                    $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': value');
                }
            } else {
                $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $changeParams['customerid']);
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id');
        }
        return ($result);
    }

    /**
     * Changes user tariff
     * 
     * @param array $changeParams
     * 
     * @return array
     */
    protected function changeUserTariff($changeParams) {
        $result = array();
        global $billing;
        if (isset($changeParams['customerid'])) {
            if (isset($this->allUserData[$changeParams['customerid']])) {
                if (isset($changeParams['value'])) {
                    if (isset($this->allTariffs[$changeParams['value']])) {
                        $newTariff = $changeParams['value'];
                        $billing->settariff($changeParams['customerid'], $newTariff);
                        log_register('CHANGE Tariff (' . $changeParams['customerid'] . ') ON `' . $newTariff . '`');
                        if ($this->altCfg['TARIFFCHGRESET']) {
                            $billing->resetuser($changeParams['customerid']);
                            log_register('RESET User (' . $changeParams['customerid'] . ')');
                        }
                        $result = array('result' => 'ok');
                    } else {
                        $result = array('result' => 'error', 'error' => $this->errorNotices['EX_WRONG_TARIFF'] . ': ' . $changeParams['value']);
                    }
                } else {
                    $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': value');
                }
            } else {
                $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $changeParams['customerid']);
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id');
        }
        return ($result);
    }

    /**
     * Changes user basic state
     * 
     * @global object $billing
     * @param array $changeParams
     * 
     * @return array
     */
    protected function changeUserState($changeParams) {
        global $billing;
        $result = array();

        if (isset($changeParams['customerid'])) {
            if (isset($this->allUserData[$changeParams['customerid']])) {
                if (isset($changeParams['value'])) {
                    $newState = $changeParams['value'];
                    if (isset($this->supportedChageUserState[$newState])) {
                        switch ($newState) {
                            case 'frozen':
                                $billing->setpassive($changeParams['customerid'], 1);
                                log_register('CHANGE Passive (' . $changeParams['customerid'] . ') ON 1');
                                $result = array('result' => 'ok');
                                break;
                            case 'unfrozen':
                                $billing->setpassive($changeParams['customerid'], 0);
                                log_register('CHANGE Passive (' . $changeParams['customerid'] . ') ON 0');
                                $result = array('result' => 'ok');
                                break;
                            case 'down':
                                $billing->setdown($changeParams['customerid'], 1);
                                log_register('CHANGE Down (' . $changeParams['customerid'] . ') ON 1');
                                $result = array('result' => 'ok');
                                break;
                            case 'notdown':
                                $billing->setdown($changeParams['customerid'], 0);
                                log_register('CHANGE Down (' . $changeParams['customerid'] . ') ON 0');
                                $result = array('result' => 'ok');
                                break;
                            case 'ao':
                                $billing->setao($changeParams['customerid'], 1);
                                log_register('CHANGE AlwaysOnline (' . $changeParams['customerid'] . ') ON 1');
                                $result = array('result' => 'ok');
                                break;
                            case 'notao':
                                $billing->setao($changeParams['customerid'], 0);
                                log_register('CHANGE AlwaysOnline (' . $changeParams['customerid'] . ') ON 0');
                                $result = array('result' => 'ok');
                                break;
                        }
                    } else {
                        $result = array('result' => 'error', 'error' => $this->errorNotices['EX_METHOD_NOT_SUPPORTED'] . ': ' . $newState);
                    }
                } else {
                    $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': value');
                }
            } else {
                $result = array('result' => 'error', 'error' => $this->errorNotices['EX_USER_NOT_EXISTS'] . ': ' . $changeParams['customerid']);
            }
        } else {
            $result = array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id');
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
            $customerId = (wf_CheckGet(array('customer_id'))) ? mysql_real_escape_string($_GET['customer_id']) : '';
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
                        if (empty($customerId)) {
                            $this->renderReply($this->getUsersList());
                        } else {
                            $this->renderReply($this->getUsersList($customerId));
                        }
                        break;
                    case 'get_user_messages':
                        $this->renderReply($this->getUsersMessages());
                        break;
                    case 'get_user_history':
                        if (!empty($customerId)) {
                            $this->renderReply($this->getUserFinanceHistory($customerId));
                        } else {
                            $this->renderReply(array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': customer_id'));
                        }
                        break;
                    case 'get_user_tags':
                        $this->renderReply($this->getUserTags());
                        break;
                    case 'get_services_list':
                        $this->renderReply($this->getServicesList());
                        break;
                    case 'get_supported_change_user_data_list':
                        $this->renderReply($this->getChangeMethodsList());
                        break;
                    case 'get_supported_change_user_state':
                        $this->renderReply($this->getChangeStateMethodsList());
                        break;
                    case 'get_supported_change_user_tariff':
                        $this->renderReply($this->getTariffsData(true));
                        break;
                    case 'get_device_type':
                        $this->renderReply($this->getDeviceTypesList());
                        break;
                    case 'get_device_model':
                        $this->renderReply($this->getDeviceModels());
                        break;

                    case 'get_device_list':
                        $devTypeFilters = (wf_CheckGet(array('device_type'))) ? $_GET['device_type'] : '';
                        $this->renderReply($this->getDevicesList($devTypeFilters));
                        break;

                    case 'get_connect_list':
                        $this->renderReply($this->getDeviceConnectionsList());
                        break;

                    case 'change_user_data':
                        $changeParams = $this->catchChangeParams();
                        if (!empty($changeParams)) {
                            if (isset($changeParams['type'])) {
                                $changeOperationType = $changeParams['type'];
                                if (isset($this->supportedChangeMethods[$changeOperationType])) {
                                    switch ($changeOperationType) {
                                        case 'balance_operation':
                                            $this->renderReply($this->changeUserFinance($changeParams));
                                            break;
                                        case 'name':
                                            $this->renderReply($this->changeUserRealName($changeParams));
                                            break;
                                        case 'comment':
                                            $this->renderReply($this->changeUserNotes($changeParams));
                                            break;
                                        case 'tariff':
                                            $this->renderReply($this->changeUserTariff($changeParams));
                                            break;
                                        case 'state':
                                            $this->renderReply($this->changeUserState($changeParams));
                                            break;
                                    }
                                } else {
                                    $this->renderReply(array('result' => 'error', 'error' => $this->errorNotices['EX_METHOD_NOT_SUPPORTED'] . ': ' . $changeOperationType));
                                }
                            } else {
                                $this->renderReply(array('result' => 'error', 'error' => $this->errorNotices['EX_PARAM_MISSED'] . ': type'));
                            }
                        } else {
                            $this->renderReply(array('result' => 'error', 'error' => $this->errorNotices['EX_NO_PARAMS']));
                        }
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
