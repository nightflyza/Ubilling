<?php

class Districts {

    /**
     * Contains available districts as id=>name
     *
     * @var array
     */
    protected $allDistricts = array();

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
     * Contains available address data
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Base module URL
     */
    const URL_ME = '?module=districts';

    /**
     * Creates new districts instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->loadDistricts();
        $this->loadCityData();
        $this->loadStreetData();
        $this->loadBuildData();
        $this->loadAptData();
        $this->loadAddressData();
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
                $this->allAddress[$each['aptid']] = $each['login'];
            }
        }
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
                $cells.= wf_TableCell($each);
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
        $result.=wf_BackLink(self::URL_ME);
        $result.=wf_CleanDiv() . wf_delimiter();
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
                    log_register('DISTRICT ADD [' . $districtId . '] CITY [' . $cityId . ']');
                }
                //city with street
                if ((!empty($streetId)) AND ( empty($buildsArr)) AND ( !empty($cityId))) {
                    $query = "INSERT INTO `districtdata` (`id`,`districtid`,`cityid`,`streetid`,`buildid`) VALUES "
                            . "(NULL,'" . $districtId . "','" . $cityId . "','" . $streetId . "',NULL);";
                    nr_query($query);
                    log_register('DISTRICT ADD [' . $districtId . '] CITY [' . $cityId . '] STREET [' . $streetId . ']');
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
                    log_register('DISTRICT ADD [' . $districtId . '] CITY [' . $cityId . '] STREET [' . $streetId . '] BUILDCOUNT `' . $buildCount . '`');
                }
            }
        }
    }

}
?>