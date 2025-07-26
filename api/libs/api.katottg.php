<?php

/**
 * Basic KATOTTG implementation
 */
class KATOTTG {
    /**
     * Basic entities database abstraction layer
     *
     * @var object
     */
    protected $katottgDb = '';
    /**
     * City bindings database abstraction layer
     *
     * @var object
     */
    protected $katottgCitiesDb = '';
    /**
     * Street bindings database abstraction layer
     *
     * @var object
     */
    protected $katottgStreetsDb = '';

    /**
     * Contains all basic entities as id=>data
     *
     * @var array
     */
    protected $allKatottg = array();
    /**
     * Contains all city bindings as cityid=>data
     *
     * @var array
     */
    protected $allCityBindings = array();
    /**
     * Contains all street bindings as streetid=>data
     *
     * @var array
     */
    protected $allStreetBindings = array();

    /**
     * Contains all cities as cityId=>name
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains all streets as streetId=>streetData
     *
     * @var array
     */
    protected $allStreets = array();

    /**
     * System messages helper
     *
     * @var object
     */
    protected $messages = '';


    /**
     * Some predefined stuff
     */
    const TABLE_KATOTTG = 'katottg';
    const TABLE_KATOTTG_CITIES = 'katottg_cities';
    const TABLE_KATOTTG_STREETS = 'katottg_streets';
    const URL_ME = '?module=katottg';
    const URL_CHECK = 'https://directory.org.ua/territories/';
    const URL_API_LOOKUP = 'http://katottg.ubilling.net.ua/';
    const AGENT_PREFIX = 'UbillingKATOTTG';
    /**
     * Some routing here
     */
    const PROUTE_NEW_OB = 'newkatottgob';
    const PROUTE_NEW_RA = 'newkatottgra';
    const PROUTE_NEW_TG = 'newkatottgtg';
    const PROUTE_NEW_CI = 'newkatottgci';
    const PROUTE_NEW_NAME = 'newkatottgname';
    const PROUTE_BIND_KAT = 'bindkatottgid';
    const PROUTE_BIND_CITY = 'bindcityid';
    const PROUTE_BIND_STREET = 'bindstreetid';
    const PROUTE_BIND_STREET_CD = 'bindstreetcd';
    const PROUTE_BIND_STREET_CITYID = 'bindstreetcityid';
    const PROUTE_BIND_STREET_KATID = 'bindstreetkatid';
    const PROUTE_EDIT_KATID = 'editkatottgid';
    const PROUTE_EDIT_NAME = 'editkatottgname';
    const PROUTE_EDIT_OB = 'editkatottgob';
    const PROUTE_EDIT_RA = 'editkatottgra';
    const PROUTE_EDIT_TG = 'editkatottgtg';
    const PROUTE_EDIT_CI = 'editkatottgci';

    const ROUTE_LIST = 'list';
    const ROUTE_CREATE_AUTO = 'createkatottgauto';
    const ROUTE_CREATE_MANUAL = 'createkatottgmanual';
    const ROUTE_EDIT = 'editkatottg';
    const ROUTE_DELETE = 'deletekatottg';
    const ROUTE_UNBIND_CITY = 'unbindcityid';
    const ROUTE_STREET_BIND = 'streetmagic';
    const ROUTE_CHECK = 'checkkatottgcode';
    const ROUTE_UNBIND_STREET = 'unbindstreetid';

    /**
     * Wandering in a cursed vale
     * Not barren, but cluttered
     * With sculptures and follies half-finished
     * Elaborate creations
     */
    public function __construct() {
        $this->initMessages();
        $this->initDb();
        $this->loadData();
        $this->loadCities();
        $this->loadStreets();
    }

    /**
     * Initializes the message helper for system notifications
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initializes database abstraction layers for KATOTTG tables
     * 
     * @return void
     */
    protected function initDb() {
        $this->katottgDb = new NyanORM(self::TABLE_KATOTTG);
        $this->katottgCitiesDb = new NyanORM(self::TABLE_KATOTTG_CITIES);
        $this->katottgStreetsDb = new NyanORM(self::TABLE_KATOTTG_STREETS);
    }

    /**
     * Loads all KATOTTG data, city bindings, and street bindings from database
     * 
     * @return void
     */
    protected function loadData() {
        $this->allKatottg = $this->katottgDb->getAll('id');
        $this->allCityBindings = $this->katottgCitiesDb->getAll('cityid');
        $this->allStreetBindings = $this->katottgStreetsDb->getAll('streetid');
    }

    /**
     * Loads all cities data from the address system
     * 
     * @return void
     */
    protected function loadCities() {
        $this->allCities = zb_AddressGetFullCityNames();
    }

    /**
     * Loads all streets data from the address system
     * 
     * @return void
     */
    protected function loadStreets() {
        $this->allStreets = zb_AddressGetStreetsDataAssoc();
    }

    /**
     * Requests KATOTTG data from remote API
     * 
     * @param int $level
     * @param string $filter
     * 
     * @return array
     */
    protected function requestRemoteKatottg($level, $filter = '') {
        $result = array();
        $requestUrl = self::URL_API_LOOKUP . '?level=' . $level;
        if (!empty($filter)) {
            $requestUrl .= '&filter=' . $filter;
        }
        $remoteApi = new OmaeUrl($requestUrl);
        $ubVer = file_get_contents('RELEASE');
        $agent = self::AGENT_PREFIX . '/' . trim($ubVer);
        $remoteApi->setUserAgent($agent);
        $result = $remoteApi->response();
        if (!empty($result)) {
            if (json_validate($result)) {
                $result = json_decode($result, true);
            }
        }

        return ($result);
    }

    /**
     * Prepares selector data from remote API response
     * 
     * @param array $data
     * 
     * @return array
     */
    protected function prepareSelectorData($data) {
        $result = array();
        if (!empty($data)) {
            $usedKeys = array();
            $result[''] = '-';
            foreach ($data as $item) {
                $entityCode = $item['code'];
                if (isset($usedKeys[$entityCode])) {
                    while (isset($usedKeys[$entityCode])) {
                        $entityCode .= '+';
                    }
                }
                $result[$entityCode] = $item['name'];
                $usedKeys[$entityCode] = 1;
            }
        }

        return ($result);
    }

    /**
     * Renders a searchable KATOTTG selector with remote data
     * 
     * @param string $name
     * @param string $label
     * @param int $level
     * @param string $filter
     * 
     * @return string
     */
    protected function renderKatottgSelector($name, $label, $level = 1, $filter = '') {
        $result = '';
        $remoteData = $this->requestRemoteKatottg($level, $filter);
        $params = $this->prepareSelectorData($remoteData);
        if (!empty($remoteData)) {
            $result .= wf_SelectorSearchableAC($name, $params, $label, '', true);
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }

        return ($result);
    }

    /**
     * Renders validation modal for KATOTTG code checking
     * 
     * @param string $code
     * 
     * @return string
     */
    public function renderValidationModal($code) {
        $result = '';
        if (!empty($code)) {
            $validationUrl = self::URL_CHECK . $code;
            $frameOpts = 'src="' . $validationUrl . '" width="800" height="600" frameborder="0"';
            $validationCode = wf_tag('iframe', false, '', $frameOpts);
            $validationCode .= wf_tag('iframe', true);
            $result .= wf_modalOpenedAuto(__('Check'), $validationCode);
        }

        return ($result);
    }

    /**
     * Renders validation control link for KATOTTG codes
     * 
     * @param string $code
     * 
     * @return string
     */
    protected function renderValidationControl($code) {
        $result = '';
        if (!empty($code)) {
            $callbackUrl = self::URL_ME . '&' . self::ROUTE_CHECK . '=' . $code;
            $result .= wf_AjaxLink($callbackUrl, wf_img('skins/question.png', __('Check')), 'katottg_check_' . $code, false);
            $result .= wf_AjaxContainerSpan('katottg_check_' . $code);
        }

        return ($result);
    }

    /**
     * Validates KATOTTG code format (UA + 17 digits)
     * 
     * @param string $code
     * 
     * @return bool
     */
    protected function validateKatottgCode($code) {
        $result = false;
        if (!empty($code)) {
            if (preg_match('/^UA\d{17}$/', $code)) {
                $result = true;
            }
        }

        return ($result);
    }

    /**
     * Renders main module control buttons
     * 
     * @return string
     */
    public function renderModuleControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CREATE_AUTO . '=true', wf_img('skins/done_icon.png') . ' ' . __('Create automatically'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CREATE_MANUAL . '=true', wf_img('skins/categories_icon.png') . ' ' . __('Create manual'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_LIST . '=true', wf_img('skins/icon_table.png') . ' ' . __('Available locations'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_STREET_BIND . '=true', wf_img('skins/icon_street.gif') . ' ' . __('Street magic'), false, 'ubButton');

        return ($result);
    }

    /**
     * Renders automatic KATOTTG creation form with remote selectors
     * 
     * @return string
     */
    public function renderCreateFormAuto() {
        $result = '';
        $inputs = '';
        $sup = wf_tag('sup', false) . '*' . wf_tag('sup', false);

        $currentOb = ubRouting::post(self::PROUTE_NEW_OB, 'gigasafe');
        $currentRa = ubRouting::post(self::PROUTE_NEW_RA, 'gigasafe');
        $currentTg = ubRouting::post(self::PROUTE_NEW_TG, 'gigasafe');
        $currentCi = ubRouting::post(self::PROUTE_NEW_CI, 'gigasafe');
        $currentName = ubRouting::post(self::PROUTE_NEW_NAME, 'safe');

        if (empty($currentOb)) {
            $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_OB, __('Region') . ' / ' . __('Oblast'), 1);
        } else {
            $validationControl = $this->renderValidationControl($currentOb);
            $inputs .= wf_TextInput(self::PROUTE_NEW_OB, __('Region') . ' / ' . __('Oblast') . ' ' . $validationControl, $currentOb, true, 22, 'gigasafe', '', self::PROUTE_NEW_OB);
        }

        if (!empty($currentOb)) {
            if (empty($currentRa)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_RA, __('District'), 2, $currentOb);
            } else {
                $validationControl = $this->renderValidationControl($currentRa);
                $inputs .= wf_TextInput(self::PROUTE_NEW_RA, __('District') . ' ' . $validationControl, $currentRa, true, 22, 'gigasafe', '', self::PROUTE_NEW_RA);
            }
        }

        if (!empty($currentOb) && !empty($currentRa)) {
            if (empty($currentTg)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_TG, __('Territorial community'), 3, $currentRa);
            } else {
                $validationControl = $this->renderValidationControl($currentTg);
                $inputs .= wf_TextInput(self::PROUTE_NEW_TG, __('Territorial community') . ' ' . $validationControl, $currentTg, true, 22, 'gigasafe', '', self::PROUTE_NEW_TG);
            }
        }

        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg)) {
            if (empty($currentCi)) {
                $inputs .= $this->renderKatottgSelector(self::PROUTE_NEW_CI, __('Settlement'), 4, $currentTg);
            } else {
                $validationControl = $this->renderValidationControl($currentCi);
                $inputs .= wf_TextInput(self::PROUTE_NEW_CI, __('Settlement') . ' ' . $validationControl, $currentCi, true, 22, 'gigasafe', '', self::PROUTE_NEW_CI);
            }
        }

        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg) && !empty($currentCi)) {
            $inputs .= wf_TextInput(self::PROUTE_NEW_NAME, __('Name') . $sup, $currentName, true, 22);
        }

        if (!empty($currentOb) && !empty($currentRa) && !empty($currentTg) && !empty($currentCi)) {
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Create'));
        }

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Renders manual KATOTTG creation form with text inputs
     * 
     * @return string
     */
    public function renderCreateFormManual() {
        $result = '';
        $inputs = '';
        $sup = wf_tag('sup', false) . '*' . wf_tag('sup', false);

        $inputs .= wf_TextInput(self::PROUTE_NEW_OB, __('Region') . ' / ' . __('Oblast') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_OB);
        $inputs .= wf_TextInput(self::PROUTE_NEW_RA, __('District') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_RA);
        $inputs .= wf_TextInput(self::PROUTE_NEW_TG, __('Territorial community') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_TG);
        $inputs .= wf_TextInput(self::PROUTE_NEW_CI, __('Settlement') . $sup, '', true, 22, 'gigasafe', '', self::PROUTE_NEW_CI);
        $inputs .= wf_TextInput(self::PROUTE_NEW_NAME, __('Name') . $sup, '', true, 22);

        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Creates new KATOTTG entity from form data
     * 
     * @return void
     */
    public function createKatottgEntity() {
        $requiredFields = array(
            self::PROUTE_NEW_OB,
            self::PROUTE_NEW_RA,
            self::PROUTE_NEW_TG,
            self::PROUTE_NEW_CI,
        );

        if (ubRouting::checkPost($requiredFields)) {
            $currentOb = ubRouting::post(self::PROUTE_NEW_OB, 'gigasafe');
            $currentRa = ubRouting::post(self::PROUTE_NEW_RA, 'gigasafe');
            $currentTg = ubRouting::post(self::PROUTE_NEW_TG, 'gigasafe');
            $currentCi = ubRouting::post(self::PROUTE_NEW_CI, 'gigasafe');
            $currentName = ubRouting::post(self::PROUTE_NEW_NAME, 'safe');


            $invalidCodes = array();

            if (!$this->validateKatottgCode($currentOb)) {
                $invalidCodes[] = __('Region') . ': ' . $currentOb;
            }
            if (!$this->validateKatottgCode($currentRa)) {
                $invalidCodes[] = __('District') . ': ' . $currentRa;
            }
            if (!$this->validateKatottgCode($currentTg)) {
                $invalidCodes[] = __('Territorial community') . ': ' . $currentTg;
            }
            if (!$this->validateKatottgCode($currentCi)) {
                $invalidCodes[] = __('Settlement') . ': ' . $currentCi;
            }

            if (empty($invalidCodes)) {
                if (!empty($currentName)) {
                    $this->katottgDb->data('ob', $currentOb);
                    $this->katottgDb->data('ra', $currentRa);
                    $this->katottgDb->data('tg', $currentTg);
                    $this->katottgDb->data('ci', $currentCi);
                    $this->katottgDb->data('name', $currentName);
                    $this->katottgDb->create();
                    $newId = $this->katottgDb->getLastId();
                    log_register('KATOTTG CREATE [' . $newId . '] NAME `' . $currentName . '`');
                    ubRouting::nav(self::URL_ME . '&' . self::ROUTE_LIST . '=true');
                } else {
                    show_error(__('All fields marked with an asterisk are mandatory'));
                }
            } else {
                $errorMessage = __('Wrong request') . ': ' . implode(', ', $invalidCodes);
                show_error($errorMessage);
            }
        }
    }

    /**
     * Deletes KATOTTG entity by ID
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteKatottgEntity($id) {
        $id = ubRouting::get(self::ROUTE_DELETE, 'int');
        if (!empty($id)) {
            if (isset($this->allKatottg[$id])) {
                $currentData = $this->allKatottg[$id];
                $this->katottgDb->where('id', '=', $id);
                $this->katottgDb->delete();
                log_register('KATOTTG DELETE [' . $id . '] NAME `' . $currentData['name'] . '`');
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_LIST . '=true');
            } else {
                log_register('KATOTTG DELETE FAIL [' . $id . '] NOT FOUND');
            }
        }
    }

    /**
     * Renders list of all KATOTTG entities
     * 
     * @return string
     */
    public function renderKatottgList() {
        $result = '';
        if (!empty($this->allKatottg)) {
            $cells = wf_tablecell(__('ID'));
            $cells .= wf_tablecell(__('Name'));
            $cells .= wf_tablecell(__('Region'));
            $cells .= wf_tablecell(__('District'));
            $cells .= wf_tablecell(__('Territorial community'));
            $cells .= wf_tablecell(__('Settlement'));
            $cells .= wf_tablecell(__('Actions'));
            $rows = wf_tableRow($cells, 'row1');
            foreach ($this->allKatottg as $katottg) {
                $cells = wf_tablecell($katottg['id']);
                $cells .= wf_tablecell($katottg['name']);
                $cells .= wf_tablecell($katottg['ob'] . ' ' . $this->renderValidationControl($katottg['ob']));
                $cells .= wf_tablecell($katottg['ra'] . ' ' . $this->renderValidationControl($katottg['ra']));
                $cells .= wf_tablecell($katottg['tg'] . ' ' . $this->renderValidationControl($katottg['tg']));
                $cells .= wf_tablecell($katottg['ci'] . ' ' . $this->renderValidationControl($katottg['ci']));
                $actionControls = wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $katottg['id'], web_edit_icon());
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $katottg['id'];
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_LIST . '=true';
                $deleteAlert = $this->messages->getDeleteAlert();
                $deleteDialog = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $deleteAlert, '', $cancelUrl, __('Delete') . '?');
                if (!$this->isKatottgProtected($katottg['id'])) {
                    $actionControls .= $deleteDialog;
                }

                $cells .= wf_tablecell($actionControls);
                $rows .= wf_tableRow($cells, 'row5');
            }
            $result .= wf_tableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     * Renders city binding form
     * 
     * @return string
     */
    public function renderCityBindingForm() {
        $result = '';

        if (!empty($this->allCities)) {
            if (!empty($this->allKatottg)) {
                $cityParams = $this->allCities;
                $katParams = array();
                foreach ($this->allKatottg as $katottg) {
                    $katParams[$katottg['id']] = $katottg['name'];
                }

                foreach ($cityParams as $cityId => $cityName) {
                    if (isset($this->allCityBindings[$cityId])) {
                        unset($cityParams[$cityId]);
                    }
                }

                if (!empty($cityParams)) {
                    $inputs = wf_Selector(self::PROUTE_BIND_CITY, $cityParams, __('City'), '', false);
                    $inputs .= wf_Selector(self::PROUTE_BIND_KAT, $katParams, __('KATOTTG'), '', false) . ' ';
                    $inputs .= wf_Submit(__('Assign'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                }
            }
        }

        return ($result);
    }

    /**
     * Checks if KATOTTG entity is protected from deletion
     * 
     * @param int $katottgId
     * 
     * @return bool
     */
    protected function isKatottgProtected($katottgId) {
        $katottgId = ubRouting::filters($katottgId, 'int');
        $result = false;
        if (!empty($this->allCityBindings)) {
            foreach ($this->allCityBindings as $cityId => $bindData) {
                if ($bindData['katid'] == $katottgId) {
                    $result = true;
                    break;
                }
            }
        }

        //also check if street is bound to katottg
        if (!empty($this->allStreetBindings)) {
            foreach ($this->allStreetBindings as $streetId => $bindData) {
                if ($bindData['katid'] == $katottgId) {
                    $result = true;
                    break;
                }
            }
        }

        return ($result);
    }

    /**
     * Binds city to KATOTTG entity
     * 
     * @param int $katottgId
     * @param int $cityId
     * 
     * @return void
     */
    public function bindCityToKatottg($katottgId, $cityId) {
        $katottgId = ubRouting::filters($katottgId, 'int');
        $cityId = ubRouting::filters($cityId, 'int');

        if (!empty($katottgId) and !empty($cityId)) {
            if (isset($this->allKatottg[$katottgId]) and isset($this->allCities[$cityId])) {

                $this->katottgCitiesDb->data('cityid', $cityId);
                $this->katottgCitiesDb->data('katid', $katottgId);
                $this->katottgCitiesDb->create();
                log_register('KATOTTG BIND CITY [' . $cityId . '] TO KATOTTG [' . $katottgId . ']');
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_LIST . '=true');
            } else {
                log_register('KATOTTG BIND CITY FAIL [' . $cityId . '] TO KATOTTG [' . $katottgId . '] NOT FOUND');
            }
        }
    }

    /**
     * Unbinds city from KATOTTG entity
     * 
     * @param int $cityId
     * 
     * @return void
     */
    public function unbindCityFromKatottg($cityId) {
        $cityId = ubRouting::filters($cityId, 'int');
        if (!empty($cityId)) {
            $this->katottgCitiesDb->where('cityid', '=', $cityId);
            $this->katottgCitiesDb->delete();
            log_register('KATOTTG UNBIND CITY [' . $cityId . ']');
            ubRouting::nav(self::URL_ME . '&' . self::ROUTE_LIST . '=true');
        }
    }

    /**
     * Renders list of city bindings
     * 
     * @return string
     */
    public function renderCityBindingList() {
        $result = '';
        if (!empty($this->allCityBindings)) {
            $cells = wf_tablecell(__('City'));
            $cells .= wf_tablecell(__('Settlement') . ' ' . __('KATOTTG'));
            $cells .= wf_tablecell(__('Code'));
            $cells .= wf_tablecell(__('Actions'));
            $rows = wf_tableRow($cells, 'row1');
            foreach ($this->allCityBindings as $cityId => $bindData) {
                $katName = (isset($this->allKatottg[$bindData['katid']])) ? $this->allKatottg[$bindData['katid']]['name'] : __('Deleted');
                $cityName = (isset($this->allCities[$cityId])) ? $this->allCities[$cityId] : __('Deleted');
                $cityCode = (isset($this->allKatottg[$bindData['katid']])) ? $this->allKatottg[$bindData['katid']]['ci'] . ' ' . $this->renderValidationControl($this->allKatottg[$bindData['katid']]['ci']) : __('Unknown');
                $cells = wf_tablecell($cityName);
                $cells .= wf_tablecell($katName);
                $cells .= wf_tablecell($cityCode);
                $unbindUrl = self::URL_ME . '&' . self::ROUTE_UNBIND_CITY . '=' . $bindData['cityid'];
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_LIST . '=true';
                $alert = $this->messages->getDeleteAlert();
                $unbindDialog = wf_ConfirmDialog($unbindUrl, web_delete_icon(), $alert, '', $cancelUrl, __('Delete') . '?');
                $actionControls = $unbindDialog;
                $cells .= wf_tablecell($actionControls);
                $rows .= wf_tableRow($cells, 'row5');
            }
            $result .= wf_tableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     * Gets city ID by KATOTTG ID
     * 
     * @param int $katId
     * 
     * @return string
     */
    protected function getCityIdByKatId($katId) {
        $result = '';
        if (!empty($this->allCityBindings)) {
            foreach ($this->allCityBindings as $cityId => $bindData) {
                if ($bindData['katid'] == $katId) {
                    $result = $cityId;
                    break;
                }
            }
        }

        return ($result);
    }

    /**
     * Checks if street is bound to KATOTTG
     * 
     * @param int $streetId
     * 
     * @return bool
     */
    protected function isStreetBound($streetId) {
        $result = false;
        if (!empty($this->allStreetBindings)) {
            if (isset($this->allStreetBindings[$streetId])) {
                $result = true;
            }
        }

        return ($result);
    }

    /**
     * Unbinds street from KATOTTG entity
     * 
     * @param int $streetId
     * 
     * @return void
     */
    public function unbindStreetFromKatottg($streetId) {
        $streetId = ubRouting::filters($streetId, 'int');
        if (!empty($streetId)) {
            if ($this->isStreetBound($streetId)) {
                $this->katottgStreetsDb->where('streetid', '=', $streetId);
                $this->katottgStreetsDb->delete();
                log_register('KATOTTG UNBIND STREET [' . $streetId . ']');
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_STREET_BIND . '=true');
            }
        }
    }

    /**
     * Renders street selector for given KATOTTG entity
     * 
     * @param int $katId
     * 
     * @return string
     */
    protected function renderStreetSelector($katId) {
        $katId = ubRouting::filters($katId, 'int');
        $cityId = $this->getCityIdByKatId($katId);
        $result = '';
        if (!empty($cityId)) {
            $cityName = $this->allCities[$cityId];

            if (!empty($this->allStreets)) {
                $streetParams = array();
                foreach ($this->allStreets as $streetId => $streetData) {
                    if ($cityId == $streetData['cityid']) {
                        if (!$this->isStreetBound($streetId)) {
                            $streetParams[$streetId] = $cityName . ' - ' . $streetData['streetname'];
                        }
                    }
                }

                $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET, $streetParams, __('Street'), '', false);
                $result .= $inputs;
            }
        }

        return ($result);
    }

    /**
     * Renders city district selector based on city code
     * 
     * @param string $cityCode
     * 
     * @return string
     */
    public function renderCityDistrictSelector($cityCode) {
        $result = '';
        $cityCode = ubRouting::filters($cityCode, 'gigasafe');
        $remoteData = $this->requestRemoteKatottg(5, '&filter=' . $cityCode);
        if (!empty($remoteData)) {
            $districtParams = array();
            foreach ($remoteData as $io => $district) {
                $districtParams[$district['code']] = $district['name'];
            }
            $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET_CD, $districtParams, __('City district'), '', false);
            $result .= $inputs;
        } else {
            $result .= wf_TextInput(self::PROUTE_BIND_STREET_CD, __('City district'), '', false, 22);
        }

        return ($result);
    }

    /**
     * Renders street binding form
     * 
     * @return string
     */
    public function renderStreetBindingForm() {
        $result = '';
        $inputs = '';
        if (!ubRouting::checkPost(self::PROUTE_BIND_STREET_KATID)) {
            if (!empty($this->allCityBindings)) {
                $cityParams = array();
                foreach ($this->allCityBindings as $cityId => $bindData) {
                    $cityParams[$bindData['katid']] = $this->allCities[$cityId];
                }
                $inputs = wf_SelectorSearchable(self::PROUTE_BIND_STREET_KATID, $cityParams, __('City'), '', false);
                $inputs .= wf_Submit(__('Chose'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        } else {
            //city already chosen
            $katId = ubRouting::post(self::PROUTE_BIND_STREET_KATID, 'int');
            $katData = $this->allKatottg[$katId];
            $cityCode = $katData['ci'];
            $inputs .= wf_HiddenInput(self::PROUTE_BIND_STREET_KATID, ubRouting::post(self::PROUTE_BIND_STREET_KATID, 'int'));
            $inputs .= $this->renderStreetSelector($katId);
            $inputs .= $this->renderCityDistrictSelector($cityCode);
            $inputs .= wf_Submit(__('Assign'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }

        return ($result);
    }

    /**
     * Creates street binding to KATOTTG entity
     * 
     * @return void
     */
    public function createStreetBinding() {
        $requiredFields = array(
            self::PROUTE_BIND_STREET_KATID,
            self::PROUTE_BIND_STREET_CD,
            self::PROUTE_BIND_STREET,
        );

        if (ubRouting::checkPost($requiredFields)) {
            $katId = ubRouting::post(self::PROUTE_BIND_STREET_KATID, 'int');
            $streetId = ubRouting::post(self::PROUTE_BIND_STREET, 'int');
            $cd = ubRouting::post(self::PROUTE_BIND_STREET_CD, 'gigasafe');
            if ($this->validateKatottgCode($cd)) {
                $this->katottgStreetsDb->data('katid', $katId);
                $this->katottgStreetsDb->data('streetid', $streetId);
                $this->katottgStreetsDb->data('cd', $cd);
                $this->katottgStreetsDb->create();
                log_register('KATOTTG BIND STREET [' . $streetId . '] TO KATOTTG [' . $katId . '] CD `' . $cd . '`');
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_STREET_BIND . '=true');
            } else {
                show_error(__('Wrong request') . ' ' . __('City district') . ': ' . $cd);
            }
        }
    }

    /**
     * Renders list of street bindings
     * 
     * @return string
     */
    public function renderStreetBindingList() {
        $result = '';
        if (!empty($this->allStreetBindings)) {
            $cells = wf_tablecell(__('City'));
            $cells .= wf_tablecell(__('Street'));
            $cells .= wf_tablecell(__('Settlement') . ' ' . __('KATOTTG'));
            $cells .= wf_tablecell(__('City district'));
            $cells .= wf_tablecell(__('Actions'));
            $rows = wf_tableRow($cells, 'row1');
            foreach ($this->allStreetBindings as $streetId => $bindData) {
                $streetData = $this->allStreets[$streetId];
                $streetName = $streetData['streetname'];
                $streetCityId = $streetData['cityid'];
                $cityName = $this->allCities[$streetCityId];
                $katName = (isset($this->allKatottg[$bindData['katid']])) ? $this->allKatottg[$bindData['katid']]['name'] : __('Deleted');
                $cells = wf_tablecell($cityName);
                $cells .= wf_tablecell($streetName);
                $cells .= wf_tablecell($katName);
                $cells .= wf_tablecell($bindData['cd']);
                $unbindUrl = self::URL_ME . '&' . self::ROUTE_UNBIND_STREET . '=' . $streetId;
                $actionControls = wf_JSAlert($unbindUrl, web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_tablecell($actionControls);
                $rows .= wf_tableRow($cells, 'row5');
            }
            $result .= wf_tableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     * Renders KATOTTG entity edit form
     * 
     * @param int $katId
     * 
     * @return string
     */
    public function renderEditForm($katId) {
        $katId = ubRouting::filters($katId, 'int');
        if (isset($this->allKatottg[$katId])) {
            $katData = $this->allKatottg[$katId];
            $sup = wf_tag('sup', false) . '*' . wf_tag('sup', true);
            $checkOb = $this->renderValidationControl($katData['ob']);
            $checkRa = $this->renderValidationControl($katData['ra']);
            $checkTg = $this->renderValidationControl($katData['tg']);
            $checkCi = $this->renderValidationControl($katData['ci']);

            $inputs = wf_HiddenInput(self::PROUTE_EDIT_KATID, $katId);
            $inputs .= wf_TextInput(self::PROUTE_EDIT_OB, $checkOb . __('Region') . ' / ' . __('District') . $sup, $katData['ob'], true, 22);
            $inputs .= wf_TextInput(self::PROUTE_EDIT_RA, $checkRa . __('District') . $sup, $katData['ra'], true, 22);
            $inputs .= wf_TextInput(self::PROUTE_EDIT_TG, $checkTg . __('Territorial community') . $sup, $katData['tg'], true, 22);
            $inputs .= wf_TextInput(self::PROUTE_EDIT_CI, $checkCi . __('Settlement') . $sup, $katData['ci'], true, 22);
            $inputs .= wf_TextInput(self::PROUTE_EDIT_NAME, __('Name') . $sup, $katData['name'], true, 22);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('KATOTTG') . ' [' . $katId . '] ' . __('not exists'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_LIST . '=true');

        return ($result);
    }

    /**
     * Saves KATOTTG entity changes
     * 
     * @return void
     */
    public function saveKatottgEntity() {
        $requiredFields = array(
            self::PROUTE_EDIT_OB,
            self::PROUTE_EDIT_RA,
            self::PROUTE_EDIT_TG,
            self::PROUTE_EDIT_CI,
            self::PROUTE_EDIT_NAME,
        );

        if (ubRouting::checkPost($requiredFields)) {
            $katId = ubRouting::post(self::PROUTE_EDIT_KATID, 'int');
            if (isset($this->allKatottg[$katId])) {
                $newOb = ubRouting::post(self::PROUTE_EDIT_OB, 'gigasafe');
                $newRa = ubRouting::post(self::PROUTE_EDIT_RA, 'gigasafe');
                $newTg = ubRouting::post(self::PROUTE_EDIT_TG, 'gigasafe');
                $newCi = ubRouting::post(self::PROUTE_EDIT_CI, 'gigasafe');
                $newName = ubRouting::post(self::PROUTE_EDIT_NAME, 'safe');
                $this->katottgDb->where('id', '=', $katId);
                $this->katottgDb->data('ob', $newOb);
                $this->katottgDb->data('ra', $newRa);
                $this->katottgDb->data('tg', $newTg);
                $this->katottgDb->data('ci', $newCi);
                $this->katottgDb->data('name', $newName);
                $this->katottgDb->save();
                log_register('KATOTTG UPDATE [' . $katId . '] NAME `' . $newName . '`');
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $katId);
            }
        }
    }

    /**
     * Gets KATOTTG data by ID
     * 
     * @param int $katId
     * 
     * @return array
     */
    public function getKatottgData($katId) {
        $katId = ubRouting::filters($katId, 'int');
        $result = array();
        if (isset($this->allKatottg[$katId])) {
            $result = $this->allKatottg[$katId];
        }

        return ($result);
    }

    /**
     * Gets KATOTTG code data by city ID
     * 
     * @param int $cityId
     * 
     * @return array
     */
    public function getCodeDataByCity($cityId) {
        $result = array();
        if (isset($this->allCityBindings[$cityId])) {
            $katId = $this->allCityBindings[$cityId]['katid'];
            $result['katid'] = $katId;
            $result['name'] = $this->allKatottg[$katId]['name'];
            $result['ob'] = $this->allKatottg[$katId]['ob'];
            $result['ra'] = $this->allKatottg[$katId]['ra'];
            $result['tg'] = $this->allKatottg[$katId]['tg'];
            $result['ci'] = $this->allKatottg[$katId]['ci'];
        }

        return ($result);
    }

    /**
     * Gets KATOTTG code data by street ID
     * 
     * @param int $streetId
     * 
     * @return array
     */
    public function getCodeDataByStreet($streetId) {
        $result = array();
        //check if street is bound to katottg directly
        if (isset($this->allStreetBindings[$streetId])) {
            $katId = $this->allStreetBindings[$streetId]['katid'];
            $katData = $this->getKatottgData($katId);
            $result['katid'] = $katId;
            $result['name'] = $katData['name'];
            $result['ob'] = $katData['ob'];
            $result['ra'] = $katData['ra'];
            $result['tg'] = $katData['tg'];
            $result['ci'] = $katData['ci'];
            $result['cd'] = $this->allStreetBindings[$streetId]['cd'];
        } else {
            //check if street is bound to katottg by city
            $streetData = $this->allStreets[$streetId];
            $cityId = $streetData['cityid'];
            $katId = $this->getCodeDataByCity($cityId);
            if (!empty($katId)) {
                $result['katid'] = $katId['katid'];
                $result['name'] = $katId['name'];
                $result['ob'] = $katId['ob'];
                $result['ra'] = $katId['ra'];
                $result['tg'] = $katId['tg'];
                $result['ci'] = $katId['ci'];
            }
        }

        return ($result);
    }
}
