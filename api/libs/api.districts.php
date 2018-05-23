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
                $actLinks = wf_JSAlert(self::URL_ME . '&deletedistrict=' . $io, web_delete_icon(), $this->messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderDistrictsEditForm($io));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

}
?>