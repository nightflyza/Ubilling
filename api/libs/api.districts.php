<?php

/**
 * Coverage map districts absctraction
 */
class Districts {

    /**
     * Contains available districts as id=>name
     *
     * @var array
     */
    protected $allDistricts = array();

    /**
     * Contains array of available districts data as id=>data
     *
     * @var array
     */
    protected $allDistrictData = array();

    /**
     * Contains available cities as id=>data
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains available streets as id=>data
     *
     * @var array
     */
    protected $allStreets = array();

    /**
     * Contains available builds as id=>data
     *
     * @var array
     */
    protected $allBuilds = array();

    /**
     * Contains available apts as id=>data
     *
     * @var array
     */
    protected $allApts = array();

    /**
     * Contains available address data as login=>aptid
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * Contains available users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Flag that signalizes, how accurate we need to detect user ativity. 
     * Based only on Cash>=Credit or use Freezing and AO states too, like exhorse.
     *
     * @var bool
     */
    protected $accurateActivityDetection = true;

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains previously cached login=>districts data
     *
     * @var array
     */
    protected $cachedData = array();

    /**
     * Default caching timeout in seconds
     */
    const CACHE_TIME = 3600;

    /**
     * Base module URL
     */
    const URL_ME = '?module=districts';

    /**
     * User profile link
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Creates new districts instance
     * 
     * @return void
     */
    public function __construct($fullLoaders = false) {
        $this->initMessages();
        $this->initCache();
        $this->loadDistricts();
        if ($fullLoaders) {
            $this->loadDistrictData();
            $this->loadCityData();
            $this->loadStreetData();
            $this->loadBuildData();
            $this->loadAptData();
            $this->loadAddressData();
            $this->loadUserData();
        }
    }

    /**
     * Inits system messages helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initalizes system caching object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
        $this->cachedData = $this->cache->get('DISTRICTS', self::CACHE_TIME);
    }

    /**
     * Loads existing districts from database
     * 
     * @return void
     */
    protected function loadDistricts() {
        $query = "SELECT * from `districtnames`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDistricts[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads existing districts data from database
     * 
     * @return void
     */
    protected function loadDistrictData() {
        $query = "SELECT * from `districtdata`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDistrictData[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available cities from database
     * 
     * @return void
     */
    protected function loadCityData() {
        $tmpArr = zb_AddressGetCityAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allCities[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available streets from database
     * 
     * @return void
     */
    protected function loadStreetData() {
        $tmpArr = zb_AddressGetStreetAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allStreets[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available builds data from database
     * 
     * @return void
     */
    protected function loadBuildData() {
        $tmpArr = zb_AddressGetBuildAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allBuilds[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available apt data from database
     * 
     * @return void
     */
    protected function loadAptData() {
        $tmpArr = zb_AddressGetAptAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allApts[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available address apt=>login data from database
     * 
     * @return void
     */
    protected function loadAddressData() {
        $tmpArr = zb_AddressGetAddressAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allAddress[$each['login']] = $each['aptid'];
            }
        }
    }

    /**
     * Loads existing users data
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Renders district creation form
     * 
     * @return string
     */
    public function renderDistrictsCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newdistrictname', __('Name'), '', false, 15);
        $inputs.= wf_Submit(__('Create'));
        $result.=wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders district edit form
     * 
     * @return string
     */
    public function renderDistrictsEditForm($districtId) {
        $districtId = vf($districtId, 3);
        $result = '';
        if (isset($this->allDistricts[$districtId])) {
            $inputs = wf_TextInput('editdistrictname', __('Name'), $this->allDistricts[$districtId], false, 15);
            $inputs.= wf_HiddenInput('editdistrictid', $districtId);
            $inputs.= wf_Submit(__('Save'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Creates new district in database
     * 
     * @param string $name
     * 
     * @return void
     */
    public function createDistrict($name) {
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `districtnames` (`id`,`name`) VALUES "
                . "(NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('districtnames');
        log_register('DISTRICT CREATE [' . $newId . '] `' . $name . '`');
    }

    /**
     * Deletes some district from database
     * 
     * @param int $districtId
     * 
     * @return void
     */
    public function deleteDistrict($districtId) {
        $districtId = vf($districtId, 3);
        if (isset($this->allDistricts[$districtId])) {
            $districtName = $this->allDistricts[$districtId];
            $query = "DELETE FROM `districtnames` WHERE `id`='" . $districtId . "';";
            nr_query($query);
            $query = "DELETE FROM `districtdata` WHERE `districtid`='" . $districtId . "';";
            nr_query($query);
            log_register('DISTRICT DELETE [' . $districtId . '] `' . $districtName . '`');
        }
    }

    /**
     * Changes district name in database
     * 
     * @param int $districtId
     * @param string $districtName
     * 
     * @return void
     */
    public function saveDistrictName($districtId, $districtName) {
        if (isset($this->allDistricts[$districtId])) {
            simple_update_field('districtnames', 'name', $districtName, "WHERE `id`='" . $districtId . "'");
            log_register('DISTRICT EDIT [' . $districtId . '] `' . $districtName . '`');
        }
    }

    /**
     * Renders available districts list with some controls
     * 
     * @return string
     */
    public function renderDistrictsList() {
        $result = '';
        if (!empty($this->allDistricts)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Name'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDistricts as $io => $each) {
                $cells = wf_TableCell($io);
                $districtViewLink = wf_link(self::URL_ME . '&viewusers=' . $io, $each);
                $cells.= wf_TableCell($districtViewLink);
                $actLinks = wf_JSAlert(self::URL_ME . '&deletedistrict=' . $io, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderDistrictsEditForm($io)) . ' ';
                $actLinks.= wf_Link(self::URL_ME . '&editdistrict=' . $io, web_icon_extended(__('Settings')));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

    /**
     * Returns list of checkbox controls for some previously selected street
     * 
     * @param int $streetId
     * 
     * @return string
     */
    protected function getBuildForm($streetId) {
        $streetId = vf($streetId, 3);
        $result = '';
        if (!empty($this->allBuilds)) {
            foreach ($this->allBuilds as $io => $each) {
                if ($each['streetid'] == $streetId) {
                    $result.=wf_CheckInput('_addbuilds[' . $each['id'] . ']', $each['buildnum'], true, false);
                }
            }
        }
        return ($result);
    }

    /**
     * Renders new district data creation form
     * 
     * @param int $districtId
     * 
     * @return string
     */
    public function renderDistrictDataCreateForm($districtId) {
        $districtId = vf($districtId, 3);
        $result = '';
        $inputs = '';

        if (!wf_CheckPost(array('citysel'))) {
            $inputs.= web_CitySelectorAc() . wf_tag('br');
        } else {
            $inputs.= wf_img('skins/icon_ok.gif') . $this->allCities[$_POST['citysel']]['cityname'] . wf_tag('br');
            $inputs.= wf_HiddenInput('citysel', $_POST['citysel']);
            if (!wf_CheckPost(array('streetsel'))) {
                $inputs.=web_StreetSelectorAc($_POST['citysel']) . wf_tag('br');
            } else {
                $inputs.= wf_img('skins/icon_ok.gif') . ' ' . @$this->allStreets[$_POST['streetsel']]['streetname'] . wf_tag('br');
                $inputs.= wf_HiddenInput('streetsel', $_POST['streetsel']);
                $inputs.=$this->getBuildForm($_POST['streetsel']);
            }

            $inputs.=wf_tag('hr');
            $inputs.=wf_CheckInput('allchoicesdone', __('I`m ready'), true, false);
            $inputs.=wf_Submit(__('Save'));
        }

        $result.=wf_Form(self::URL_ME . '&editdistrict=' . $districtId, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Catches new district data creation request
     * 
     * @return void
     */
    public function catchDistrictDataCreate() {
        if (wf_CheckGet(array('editdistrict'))) {
            $districtId = vf($_GET['editdistrict'], 3);
            if (wf_CheckPost(array('citysel'))) {
                $cityId = vf($_POST['citysel'], 3);
                $streetId = (wf_CheckPost(array('streetsel'))) ? vf($_POST['streetsel'], 3) : '';
                $buildsArr = (wf_CheckPost(array('_addbuilds'))) ? $_POST['_addbuilds'] : array();
                //only city
                if ((empty($streetId)) AND ( empty($buildsArr)) AND ( !empty($cityId))) {
                    $query = "INSERT INTO `districtdata` (`id`,`districtid`,`cityid`,`streetid`,`buildid`) VALUES "
                            . "(NULL,'" . $districtId . "','" . $cityId . "',NULL,NULL);";
                    nr_query($query);
                    log_register('DISTRICT DATACREATE [' . $districtId . '] CITY [' . $cityId . ']');
                }
                //city with street
                if ((!empty($streetId)) AND ( empty($buildsArr)) AND ( !empty($cityId))) {
                    $query = "INSERT INTO `districtdata` (`id`,`districtid`,`cityid`,`streetid`,`buildid`) VALUES "
                            . "(NULL,'" . $districtId . "','" . $cityId . "','" . $streetId . "',NULL);";
                    nr_query($query);
                    log_register('DISTRICT DATACREATE [' . $districtId . '] CITY [' . $cityId . '] STREET [' . $streetId . ']');
                }

                //city->street->build
                if ((!empty($streetId)) AND ( !empty($buildsArr)) AND ( !empty($cityId))) {
                    $buildCount = 0;
                    foreach ($buildsArr as $io => $each) {
                        $query = "INSERT INTO `districtdata` (`id`,`districtid`,`cityid`,`streetid`,`buildid`) VALUES "
                                . "(NULL,'" . $districtId . "','" . $cityId . "','" . $streetId . "','" . $io . "');";
                        nr_query($query);
                        $buildCount++;
                    }
                    log_register('DISTRICT DATACREATE [' . $districtId . '] CITY [' . $cityId . '] STREET [' . $streetId . '] BUILDCOUNT `' . $buildCount . '`');
                }
            }
        }
    }

    /**
     * Returns district name by its ID
     * 
     * @param int $districtId
     * 
     * @return string
     */
    public function getDistrictName($districtId) {
        $districtId = vf($districtId, 3);
        $result = '';
        if (isset($this->allDistricts[$districtId])) {
            $result = $this->allDistricts[$districtId];
        }
        return ($result);
    }

    /**
     * Returns array of available districts as id=>name
     * 
     * @return array
     */
    public function getDistricts() {
        return ($this->allDistricts);
    }

    /**
     * Renders available district data with some controls
     * 
     * @param int $districtId
     * 
     * @return string
     */
    public function renderDistrictData($districtId) {
        $districtId = vf($districtId, 3);
        $result = '';
        if (!empty($this->allDistrictData)) {
            $cells = wf_TableCell(__('ID'));
            $cells.=wf_TableCell(__('District'));
            $cells.=wf_TableCell(__('City'));
            $cells.=wf_TableCell(__('Street'));
            $cells.=wf_TableCell(__('Build'));
            $cells.=wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allDistrictData as $io => $each) {
                if ($each['districtid'] == $districtId) {
                    $cells = wf_TableCell($each['id']);
                    $cells.=wf_TableCell(@$this->allDistricts[$each['districtid']]);
                    $cells.=wf_TableCell(@$this->allCities[$each['cityid']]['cityname']);
                    $cells.=wf_TableCell(@$this->allStreets[$each['streetid']]['streetname']);
                    $cells.=wf_TableCell(@$this->allBuilds[$each['buildid']]['buildnum']);
                    $actLinks = wf_JSAlert(self::URL_ME . '&editdistrict=' . $districtId . '&deletedata=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.=wf_TableCell($actLinks);
                    $rows.= wf_TableRow($cells, 'row5');
                }
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Deletes some district data row from database
     * 
     * @param int $dataId
     * 
     * @return void
     */
    public function deleteDistrictData($dataId) {
        $dataId = vf($dataId, 3);
        if (isset($this->allDistrictData[$dataId])) {
            $districtId = $this->allDistrictData[$dataId]['districtid'];
            $query = "DELETE from `districtdata` WHERE `id`='" . $dataId . "';";
            nr_query($query);
            log_register('DISTRICT DATADELETE [' . $districtId . '] DATAID [' . $dataId . ']');
        }
    }

    /**
     * Renders districts users report container
     * 
     * @param int $districtId
     * 
     * @return string
     */
    public function renderDistrictUsersContainer($districtId) {
        $result = '';
        $columns = array('Login', 'Address', 'Real Name', 'IP', 'Tariff', 'Active', 'Balance', 'Credit');
        $result.=wf_JqDtLoader($columns, self::URL_ME . '&viewusers=' . $districtId . '&ajax=true', false, 'Users', 100);
        return ($result);
    }

    /**
     * Returns is user active or not. Customizable in future.
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function isUserActive($login) {
        $result = false;
        if (isset($this->allUserData[$login])) {
            if ($this->accurateActivityDetection) {
                if (($this->allUserData[$login]['Cash'] >= '-' . $this->allUserData[$login]['Credit']) AND ( $this->allUserData[$login]['Passive'] == 0) AND ( $this->allUserData[$login]['AlwaysOnline'] == 1)) {
                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $result = ($this->allUserData[$login]['Cash'] >= '-' . $this->allUserData[$login]['Credit']) ? true : false;
            }
        }
        return ($result);
    }

    /**
     * Checks is user in some district or not
     * 
     * @param string $login
     * @param int $districtId
     * 
     * @return bool
     */
    protected function isUserInDistrict($login, $districtId) {
        $result = false;
        if (isset($this->allAddress[$login])) {
            if (isset($this->allDistricts[$districtId])) {
                if (!empty($this->allDistrictData)) {
                    $userAptId = $this->allAddress[$login];
                    $userApt = $this->allApts[$userAptId];
                    $userBuildId = $userApt['buildid'];
                    $userBuild = $this->allBuilds[$userBuildId];
                    $userStreetId = $userBuild['streetid'];
                    $userStreet = $this->allStreets[$userStreetId];
                    $userCityId = $userStreet['cityid'];
                    foreach ($this->allDistrictData as $io => $each) {
                        if ($each['districtid'] == $districtId) {
                            if ($userCityId == $each['cityid']) {
                                $result = true;
                                if (!empty($each['streetid'])) {
                                    if ($userStreetId == $each['streetid']) {
                                        $result = true;
                                        if (!empty($each['buildid'])) {
                                            if ($userBuildId == $each['buildid']) {
                                                $result = true;
                                                return ($result);
                                            } else {
                                                $result = false;
                                            }
                                        } else {
                                            return ($result);
                                        }
                                    } else {
                                        $result = false;
                                    }
                                } else {
                                    return ($result);
                                }
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders datatables report JSON data
     * 
     * @param int $districtId
     * 
     * @return void
     */
    public function renderDistrictUsersAjaxData($districtId) {
        $districtId = vf($districtId, 3);
        $json = new wf_JqDtHelper();
        if (isset($this->allDistricts[$districtId])) {
            if (!empty($this->allAddress)) {
                foreach ($this->allAddress as $login => $aptId) {
                    if ($this->isUserInDistrict($login, $districtId)) {
                        $userLink = wf_Link(self::URL_PROFILE . $login, web_profile_icon() . ' ' . $login);
                        $data[] = $userLink;
                        $data[] = @$this->allUserData[$login]['fulladress'];
                        $data[] = @$this->allUserData[$login]['realname'];
                        $data[] = @$this->allUserData[$login]['ip'];
                        $data[] = @$this->allUserData[$login]['Tariff'];
                        $actFlag = ($this->isUserActive($login)) ? web_bool_led(true) . ' ' . __('Active') : web_bool_led(false) . ' ' . __('Not really');
                        $data[] = $actFlag;
                        $data[] = @$this->allUserData[$login]['Cash'];
                        $data[] = @$this->allUserData[$login]['Credit'];
                        $json->addRow($data);
                        unset($data);
                    }
                }
            }
        }
        $json->getJson();
    }

    /**
     * Fills districts cache for further fast usage
     * 
     * @return void
     */
    public function fillDistrictsCache() {
        $tmpArr = array();
        if (!empty($this->allDistricts)) {
            if (!empty($this->allAddress)) {
                foreach ($this->allAddress as $login => $aptId) {
                    foreach ($this->allDistricts as $districtId => $districtName) {
                        if ($this->isUserInDistrict($login, $districtId)) {
                            $tmpArr[$login][$districtId] = $districtName;
                        }
                    }
                }
            }
        }
        $this->cache->set('DISTRICTS', $tmpArr, self::CACHE_TIME);
    }

    /**
     * Returns some user districts array as id=>name from cache
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getUserDistrictsFast($login) {
        $result = array();
        if (!empty($this->cachedData)) {
            if (isset($this->cachedData[$login])) {
                $result = $this->cachedData[$login];
            }
        }
        return ($result);
    }

    /**
     * Check user district based on cached data
     * 
     * @param string $login
     * @param int $districtId
     * 
     * @return bool
     */
    public function checkUserDistrictFast($login, $districtId) {
        $result = false;
        if (isset($this->cachedData[$login])) {
            if (isset($this->cachedData[$login][$districtId])) {
                $result = true;
                deb($login.'->'.$districtId);
            }
        }
        return ($result);
    }

    /**
     * Returns list of user districts text list from cache
     * 
     * @param string $login
     * 
     * @return string
     */
    public function getUserDistrictsListFast($login) {
        $result = '';
        $userDistricts = $this->getUserDistrictsFast($login);
        if (!empty($userDistricts)) {
            $result.=implode(', ', $userDistricts);
        }
        return ($result);
    }

}

?>