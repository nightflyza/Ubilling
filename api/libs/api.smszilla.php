<?php

class SMSZilla {

    /**
     * System alter.ini config as key=>value
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
     * Contains available cities as cityid=>data
     *
     * @var array
     */
    protected $allCities = array();

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
     * Contains available templates as id=>data
     *
     * @var array
     */
    protected $templates = array();

    /**
     * Contains available filters
     *
     * @var array
     */
    protected $filters = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Available filter types
     *
     * @var array
     */
    protected $filterTypes = array();

    /**
     * Contains available filters names
     *
     * @var array
     */
    protected $filterNames = array();

    /**
     * Current run entities type
     *
     * @var strings
     */
    protected $entitiesType = '';

    /**
     * Contains entities that passed full filter set
     *
     * @var array
     */
    protected $filteredEntities = array();

    /**
     * Contains filtered numbers extracted from filtered entities
     *
     * @var array
     */
    protected $filteredNumbers = array();

    /**
     * FundsFlow object placeholder
     *
     * @var object
     */
    protected $fundsFlow = '';

    /**
     * Contains tags for internet users
     *
     * @var array
     */
    protected $inetTags = array();

    /**
     * Contains tags for UKV users
     *
     * @var array
     */
    protected $ukvTags = array();

    /**
     * Contains all users and their down state as login=>state
     *
     * @var array
     */
    protected $downUsers = array();

    /**
     * Caching flag
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * SMS abstraction layer placeholder
     *
     * @var object
     */
    protected $sms = '';

    /**
     * Branches object placeholder
     *
     * @var object
     */
    protected $branches = '';

    /**
     * UKV object placeholder
     *
     * @var object
     */
    protected $ukv = '';

    /**
     * UKV debtors once loading flag
     *
     * @var bool
     */
    protected $ukvDebtorsLoaded = false;

    /**
     * Available UKV debtors
     *
     * @var string
     */
    protected $ukvDebtors = array();

    /**
     * Contains available employee list
     *
     * @var array
     */
    protected $employee = array();

    /**
     * Extended mobiles phonebase usage flag
     *
     * @var bool
     */
    protected $useExtMobiles = false;

    /**
     * Extended mobiles object placeholder
     *
     * @var object
     */
    protected $extMobiles = '';

    /**
     * Contains filters workflow stats as name=>count
     *
     * @var array
     */
    protected $filterStats = array();

    /**
     * Base module URL
     */
    const URL_ME = '?module=smszilla';

    /**
     * Contains SMS Pool saving path
     */
    const POOL_PATH = './exports/';

    /**
     * Creates new SMSZilla instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->initSMS();
        $this->loadCities();
        $this->loadUsers();
        $this->loadDownUsers();
        $this->initUKV();
        $this->initBranches();
        $this->loadTagTypes();
        $this->loadTariffs();
        $this->loadTemplates();
        $this->loadFilters();
        $this->loadInetTags();
        $this->loadUkvTags();
        $this->loadEmployee();
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
     * Loads all existing Internet users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        if ($this->useCache) {
            $this->allUserData = zb_UserGetAllDataCache();
        } else {
            $this->allUserData = zb_UserGetAllData();
        }
    }

    /**
     * Loads available users down states into separate property
     * 
     * @return void
     */
    protected function loadDownUsers() {
        $query = "SELECT `login`,`Down` from `users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->downUsers[$each['login']] = $each['Down'];
            }
        }
    }

    /**
     * Inits UKV object instance
     * 
     * @return void
     */
    protected function initUKV() {
        $this->ukv = new UkvSystem();
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
     * Loads all existing SMS templates from database
     * 
     * @return void
     */
    protected function loadTemplates() {
        $query = "SELECT * from smz_templates";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->templates[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads array of all tagtypes set to users
     * 
     * @return void
     */
    protected function loadInetTags() {
        $query = "SELECT * from `tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->inetTags[$each['login']][] = $each['tagid'];
            }
        }
    }

    /**
     * Loads array of all tagtypes set to users
     * 
     * @return void
     */
    protected function loadUkvTags() {
        $query = "SELECT * from `ukv_tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->ukvTags[$each['userid']][] = $each['tagtypeid'];
            }
        }
    }

    /**
     * Loads available employee from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->employee[$each['id']] = $each;
            }
        }
    }

    /**
     * Inits system messages helper into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits SMS queue abstraction layer
     * 
     * @return void
     */
    protected function initSMS() {
        $this->sms = new UbillingSMS();
    }

    /**
     * Branches initalization
     * 
     * @return void
     */
    protected function initBranches() {
        $this->branches = new UbillingBranches();
    }

    /**
     * Inits funds flow object instance
     * 
     * @return void
     */
    protected function initFundsFlow() {
        $this->fundsFlow = new FundsFlow();
        $this->fundsFlow->runDataLoders();
    }

    /**
     * Sets all necessary options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->filterTypes = array(
            self::URL_ME . '&filters=true&newfilterdirection=none' => __('No'),
            self::URL_ME . '&filters=true&newfilterdirection=login' => __('Internet'),
            self::URL_ME . '&filters=true&newfilterdirection=ukv' => __('UKV'),
            self::URL_ME . '&filters=true&newfilterdirection=employee' => __('Employee'),
            self::URL_ME . '&filters=true&newfilterdirection=numlist' => __('Numbers list')
        );

        $this->filterNames = array(
            'atstart' => 'At begining',
            'filterdirection' => 'SMS direction',
            'filtername' => 'Filter name',
            'filteraddress' => 'Address contains',
            'filterao' => 'User is AlwaysOnline',
            'filterbranch' => 'Branch',
            'filtercashdays' => 'Balance is enought less than days',
            'filtercashgreater' => 'Balance is greater than',
            'filtercashlesser' => 'Balance is less than',
            'filtercashmonth' => 'Balance is not enough for the next month',
            'filtercity' => 'City',
            'filterdown' => 'User is down',
            'filteremployeeactive' => 'Employee is active',
            'filteremployeeappointment' => 'Appointment',
            'filterextmobiles' => 'Use additional mobiles',
            'filterlogin' => 'Login contains',
            'filternotariff' => 'User have no tariff assigned',
            'filterpassive' => 'User is frozen',
            'filtertags' => 'User have tag assigned',
            'filtertariff' => 'User have tariff',
            'filterukvactive' => 'User is active',
            'filterukvdebtor' => 'Debtors',
            'filterukvtariff' => 'User have tariff',
            'filterrealname' => 'Real Name contains'
        );
    }

    /**
     * Creates new SMS text template
     * 
     * @param string $name
     * @param string $text
     * 
     * @return int
     */
    public function createTemplate($name, $text) {
        $name = mysql_real_escape_string($name);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `smz_templates` (`id`,`name`,`text`) VALUES ";
        $query.= "(NULL,'" . $name . "','" . $text . "');";
        nr_query($query);
        $newId = simple_get_lastid('smz_templates');
        log_register('SMSZILLA TEMPLATE CREATE [' . $newId . ']');
        return ($newId);
    }

    /**
     * Deletes existing template
     * 
     * @param int $templateId
     * 
     * @return void/string on error
     */
    public function deleteTemplate($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $query = "DELETE from `smz_templates` WHERE `id`='" . $templateId . "';";
            nr_query($query);
            log_register('SMSZILLA TEMPLATE DELETE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Saves changes in existing template
     * 
     * @param int $templateId
     * @param string $name
     * @param string $text
     * 
     * @return void/string on error
     */
    public function saveTemplate($templateId, $name, $text) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $where = "WHERE `id`='" . $templateId . "'";
            simple_update_field('smz_templates', 'name', $name, $where);
            simple_update_field('smz_templates', 'text', $text, $where);
            log_register('SMSZILLA TEMPLATE CHANGE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders new template creation form
     * 
     * @return string
     */
    public function renderTemplateCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newtemplatename', __('Name'), '', true, '40');
        $inputs.=__('Template') . wf_tag('br');
        $inputs.= wf_TextArea('newtemplatetext', '', '', true, '45x5');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME . '&templates=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders existing template edit form
     * 
     * @param int $templateId
     * 
     * @return string
     */
    public function renderTemplateEditForm($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $templateData = $this->templates[$templateId];
            $inputs = wf_HiddenInput('edittemplateid', $templateId);
            $inputs.= wf_TextInput('edittemplatename', __('Name'), $templateData['name'], true, '40');
            $inputs.=__('Template') . wf_tag('br');
            $inputs.= wf_TextArea('edittemplatetext', '', $templateData['text'], true, '45x5');
            $templateSize = strlen($templateData['text']);
            $inputs.=__('Text size') . ' ~' . $templateSize . wf_tag('br');
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form(self::URL_ME . '&templates=true&edittemplate=' . $templateId, 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Renders existing templates list with some controls
     * 
     * @return string
     */
    public function renderTemplatesList() {
        $result = '';
        if (!empty($this->templates)) {
            $cells = wf_TableCell(__('ID'));
            $cells.=wf_TableCell(__('Name'));
            $cells.=wf_TableCell(__('Text'));
            $cells.=wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->templates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.=wf_TableCell($each['name']);
                $cells.=wf_TableCell($each['text']);
                $actLinks = wf_JSAlert(self::URL_ME . '&templates=true&deletetemplate=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_JSAlert(self::URL_ME . '&templates=true&edittemplate=' . $each['id'], web_edit_icon(), $this->messages->getEditAlert());

                $cells.=wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No existing templates available'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders default module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result.=wf_Link(self::URL_ME . '&sending=true', wf_img('skins/icon_sms_micro.gif') . ' ' . __('SMS sending'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&templates=true', wf_img('skins/icon_template.png') . ' ' . __('Templates'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&filters=true', web_icon_extended() . ' ' . __('Filters'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&numlists=true', wf_img('skins/icon_mobile.gif') . ' ' . __('Numbers lists'), true, 'ubButton') . ' ';
        if (wf_CheckGet(array('templates'))) {
            $result.=wf_tag('br');
            if (wf_CheckGet(array('edittemplate'))) {
                $result.=wf_BackLink(self::URL_ME . '&templates=true') . ' ';
            } else {
                $result.=wf_modalAuto(web_icon_create() . ' ' . __('Create new template'), __('Create new template'), $this->renderTemplateCreateForm(), 'ubButton');
            }
        }
        return ($result);
    }

    /**
     * Renders filter creation form
     * 
     * @return string
     */
    public function renderFilterCreateForm() {
        $result = '';
        $result.=wf_AjaxLoader();
        $inputs = wf_AjaxContainer('inputscontainer', '', $this->catchAjRequest(true));
        $result.= wf_Form(self::URL_ME . '&filters=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns ajax inputs of required type
     * 
     * @return string
     */
    public function catchAjRequest($remote = false) {
        $inputs = '';
        $direction = 'none';
        if (wf_CheckGet(array('newfilterdirection'))) {
            $direction = vf($_GET['newfilterdirection']);
        }

        $citiesParams = array('' => __('Any'));
        if (!empty($this->allCities)) {
            foreach ($this->allCities as $io => $each) {
                $citiesParams[$each['cityname']] = $each['cityname'];
            }
        }

        $tagsParams = array('' => __('-'));
        if (!empty($this->allTagTypes)) {
            foreach ($this->allTagTypes as $io => $each) {
                $tagsParams[$io] = $each;
            }
        }

        $tariffParams = array('' => __('Any'));
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $tariffParams[$each['name']] = $each['name'];
            }
        }

        $ukvTariffParams = array('' => __('Any'));
        $ukvfTariffsAvail = $this->ukv->getTariffs();
        if (!empty($ukvfTariffsAvail)) {
            foreach ($ukvfTariffsAvail as $io => $each) {
                $ukvTariffParams[$each['id']] = $each['tariffname'];
            }
        }


        $numListParams = array('' => '-');

        $branchParams = array('' => __('Any'));
        $availBranches = $this->branches->getBranchesAvailable();
        if (!empty($availBranches)) {
            foreach ($availBranches as $io => $each) {
                $branchParams[$io] = $each;
            }
        }


        $inputs.=wf_AjaxSelectorAC('inputscontainer', $this->filterTypes, __('SMS direction'), self::URL_ME . '&filters=true&newfilterdirection=' . $direction, true);
        $inputs.= wf_tag('br');

        if ($direction != 'none') {
            $inputs.= wf_HiddenInput('newfilterdirection', $direction);
            $inputs.= wf_TextInput('newfiltername', __('Filter name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '30');

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.=wf_Selector('newfiltercity', $citiesParams, __('City'), '', true, false);
                $inputs.= wf_TextInput('newfilteraddress', __('Address contains'), '', true, '40');
            }

            if (($direction == 'login') OR ( $direction == 'ukv') OR ( $direction == 'employee')) {
                $inputs.= wf_TextInput('newfilterrealname', __('Real Name') . ' ' . __('contains'), '', true, '30');
            }


            if (($direction == 'login')) {
                $inputs.= wf_TextInput('newfilterlogin', __('Login contains'), '', true, '20');
                $inputs.= wf_CheckInput('newfiltercashmonth', __('Balance is not enough for the next month'), true, false);
                $inputs.= wf_TextInput('newfiltercashdays', __('Balance is enought less than days'), '', true, '5');
            }

            if ($direction == 'ukv') {
                $inputs.=wf_Selector('newfilterukvtariff', $ukvTariffParams, __('User have tariff'), '', true, false);
                $inputs.= wf_CheckInput('newfilterukvdebtor', __('Debtors'), true, false);
                $inputs.= wf_CheckInput('newfilterukvactive', __('User is active'), true, false);
            }

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.= wf_TextInput('newfiltercashgreater', __('Balance is greater than'), '', true, '5');
                $inputs.= wf_TextInput('newfiltercashlesser', __('Balance is less than'), '', true, '5');
                $inputs.=wf_Selector('newfiltertags', $tagsParams, __('User have tag assigned'), '', true, false);
            }


            if (($direction == 'login')) {
                $inputs.= wf_CheckInput('newfilterpassive', __('User is frozen'), true, false);
                $inputs.= wf_CheckInput('newfilterdown', __('User is down'), true, false);
                $inputs.= wf_CheckInput('newfilterao', __('User is AlwaysOnline'), true, true);
                $inputs.=wf_Selector('newfiltertariff', $tariffParams, __('User have tariff'), '', true, false);
                $inputs.= wf_CheckInput('newfilternotariff', __('User have no tariff assigned'), true, false);
                $inputs.= wf_CheckInput('newfilterextmobiles', __('Use additional mobiles'), true, false);
                $inputs.=wf_Selector('newfilterbranch', $branchParams, __('Branch'), '', true, false);
            }

            if (($direction == 'numlist')) {
                $inputs.=wf_Selector('newfilternumlist', $numListParams, __('Numbers list'), '', true, false);
                $inputs.=wf_TextInput('newfilternumcontain', __('Notes contains'), '', true, '20');
            }

            if ($direction == 'employee') {
                $inputs.= wf_TextInput('newfilteremployeeappointment', __('Appointment'), '', true, '30');
                $inputs.= wf_CheckInput('newfilteremployeeactive', __('Employee is active'), true, true);
            }

            $inputs.=wf_tag('br');
            $inputs.=wf_Submit(__('Create'));
        } else {
            $inputs.=__('Please select SMS direction');
        }

        if (!$remote) {
            die($inputs);
        } else {
            return ($inputs);
        }
    }

    /**
     * Creates new filter in database
     * 
     * @return void/string
     */
    public function createFilter() {
        $result = '';
        if (wf_CheckPost(array('newfilterdirection'))) {
            $filterName = $_POST['newfiltername'];
            $filterNameF = mysql_real_escape_string($filterName);
            if (!empty($filterNameF)) {
                $filterParams = array();
                foreach ($_POST as $io => $each) {
                    if (ispos($io, 'newfilter')) {
                        $filterParams[$io] = $each;
                    }
                }
                if (!empty($filterParams)) {
                    $filterParams = json_encode($filterParams);
                    $filterParams = mysql_real_escape_string($filterParams);
                    $query = "INSERT INTO `smz_filters` (`id`,`name`,`filters`) VALUES ";
                    $query.="(NULL,'" . $filterNameF . "','" . $filterParams . "');";
                    nr_query($query);
                    $newId = simple_get_lastid('smz_filters');
                    log_register('SMSZILLA FILTER CREATE [' . $newId . '] `' . $filterName . '`');
                }
            } else {
                $result = __('Something went wrong') . ': EX_FILTER_NAME_EMPTY';
            }
        }
        return ($result);
    }

    /**
     * Loads existing filters from database
     * 
     * @return void
     */
    protected function loadFilters() {
        $query = "SELECT * from `smz_filters`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->filters[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders existing filter preview
     * 
     * @param string $filters
     * 
     * @return string
     */
    protected function renderFilterPreview($filters) {
        $result = '';
        if (!empty($filters)) {
            $unpack = json_decode($filters, true);
            if (!empty($unpack)) {
                $cells = wf_TableCell(__('Filter'));
                $cells.= wf_TableCell(__('Parameter'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($unpack as $io => $each) {
                    $filterName = str_replace('new', '', $io);
                    if (isset($this->filterNames[$filterName])) {
                        $filterName = __($this->filterNames[$filterName]);
                    }
                    $cells = wf_TableCell($filterName);
                    $cells.= wf_TableCell($each);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result.= wf_TableBody($rows, '100%', 0, '');
            }
        }
        return ($result);
    }

    /**
     * Renders available filters list
     * 
     * @return string
     */
    public function renderFiltersList() {
        $result = '';
        if (!empty($this->filters)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Name'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->filters as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['name']);
                $actLinks = wf_JSAlert(self::URL_ME . '&filters=true&deletefilterid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_icon_search(), __('Preview'), $this->renderFilterPreview($each['filters']));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Deletes existing filter from database
     * 
     * @param int $filterId
     * 
     * @return void/string
     */
    public function deleteFilter($filterId) {
        $result = '';
        $filterId = vf($filterId, 3);
        if (isset($this->filters[$filterId])) {
            $query = "DELETE FROM `smz_filters` WHERE `id`='" . $filterId . "';";
            nr_query($query);
            log_register('SMSZILLA FILTER DELETE [' . $filterId . ']');
        } else {
            $result = __('Something went wrong') . ': : FILTER_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Checks have user some tag assigned
     * 
     * @param string $login
     * @param int $tagId
     * 
     * @return bool
     */
    protected function checkInetTagId($login, $tagId) {
        $result = false;
        if (isset($this->inetTags[$login])) {
            if (!empty($this->inetTags[$login])) {
                foreach ($this->inetTags[$login] as $io => $each) {
                    if ($each == $tagId) {
                        $result = true;
                        return ($result);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Checks have UKV user some tag assigned
     * 
     * @param int $userid
     * @param int $tagId
     * 
     * @return bool
     */
    protected function checkUkvTagId($userid, $tagId) {
        $result = false;
        if (isset($this->ukvTags[$userid])) {
            if (!empty($this->ukvTags[$userid])) {
                foreach ($this->ukvTags[$userid] as $io => $each) {
                    if ($each == $tagId) {
                        $result = true;
                        return ($result);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders template and filters selection form
     * 
     * @return string
     */
    public function renderSendingForm() {
        $result = '';

//saving previous selectors state
        $curTemplateId = (wf_CheckPost(array('sendingtemplateid'))) ? $_POST['sendingtemplateid'] : '';
        $curFilterId = (wf_CheckPost(array('sendingfilterid'))) ? $_POST['sendingfilterid'] : '';
        $curVisualFlag = (wf_CheckPost(array('sendingvisualfilters'))) ? true : false;
        $curTranslitFlag = (wf_CheckPost(array('forcetranslit'))) ? true : false;

        if (!(empty($this->templates)) AND ( !empty($this->filters))) {
            $templatesParams = array();
            foreach ($this->templates as $io => $each) {
                $templatesParams[$each['id']] = $each['name'];
            }

            $filterParams = array();
            foreach ($this->filters as $io => $each) {
                $filterParams[$each['id']] = $each['name'];
            }


            $inputs = wf_Selector('sendingtemplateid', $templatesParams, __('Template'), $curTemplateId, false) . ' ';
            $inputs.= wf_Selector('sendingfilterid', $filterParams, __('Filter'), $curFilterId, false) . ' ';
            $inputs.= wf_CheckInput('sendingvisualfilters', __('Visual'), false, $curVisualFlag) . ' ';
            $inputs.= wf_CheckInput('forcetranslit', __('Forced transliteration'), false, $curTranslitFlag) . ' ';
            $inputs.= wf_CheckInput('sendingperform', __('Perform real sending'), false, false) . ' ';

            $inputs.= wf_Submit(__('Send SMS'));

            $result.=wf_Form(self::URL_ME . '&sending=true', 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('No existing templates or filters available'), 'warning');
        }
        return ($result);
    }

    /**
     * Normalizes mobile number to +380 format. 
     * May be not acceptable for countries other than Ukraine.
     * 
     * @param string $mobile
     * 
     * @return string/void on error
     */
    protected function normalizePhoneFormat($mobile) {
        $mobile = vf($mobile, 3);
        $len = strlen($mobile);
//all is ok
        if ($len != 12) {
            switch ($len) {
                case 11:
                    $mobile = '3' . $mobile;
                    break;
                case 10:
                    $mobile = '38' . $mobile;
                    break;
                case 9:
                    $mobile = '380' . $mobile;
                    break;
            }
        }

        $newLen = strlen($mobile);
        if ($newLen == 12) {
            $mobile = '+' . $mobile;
        } else {
            $mobile = '';
        }


        return ($mobile);
    }

    /**
     * Extract mobile numbers from filtered entities array
     * 
     * @return void
     */
    protected function extractEntitiesNumbers() {

        if (!empty($this->filteredEntities)) {
            switch ($this->entitiesType) {
                case 'login':
                    if ($this->useExtMobiles) {
                        $this->extMobiles = new MobilesExt();
                    }
                    foreach ($this->filteredEntities as $io => $each) {
                        $userLogin = $each['login'];
                        $primaryMobile = $this->normalizePhoneFormat($each['mobile']);
                        if (!empty($primaryMobile)) {
                            $this->filteredNumbers[$userLogin][] = $primaryMobile;
                        }

                        if ($this->useExtMobiles) {
                            $userExtMobiles = $this->extMobiles->getUserMobiles($userLogin);
                            if (!empty($userExtMobiles)) {
                                foreach ($userExtMobiles as $ia => $eachExt) {
                                    $additionalMobile = $this->normalizePhoneFormat($eachExt['mobile']);
                                    if (!empty($additionalMobile)) {
                                        $this->filteredNumbers[$userLogin][] = $additionalMobile;
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'ukv':
                    foreach ($this->filteredEntities as $io => $each) {
                        $userPrimaryMobile = $this->normalizePhoneFormat($each['mobile']);
                        if (!empty($userPrimaryMobile)) {
                            $this->filteredNumbers[$each['id']] = $userPrimaryMobile;
                        }
                    }
                    break;

                case 'employee':
                    foreach ($this->filteredEntities as $io => $each) {
                        $employeeMobile = $this->normalizePhoneFormat($each['mobile']);
                        if (!empty($employeeMobile)) {
                            $this->filteredNumbers[$each['id']] = $employeeMobile;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Saves each filter workflow stats
     * 
     * @param string $filterName
     * @param int $entityCount
     * 
     * @return void
     */
    protected function saveFilterStats($filterName, $entityCount) {
        $this->filterStats[$filterName] = $entityCount;
    }

    protected function renderFilterStats() {
        $result = '';
        $params = array();
        $params[] = array(__('Filter'), __('Entities count'));

        $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";
        if (!empty($this->filterStats)) {
            foreach ($this->filterStats as $filterName => $entityCount) {
                $filterNormalName = (isset($this->filterNames[$filterName])) ? __($this->filterNames[$filterName]) : $filterName;
                $params[] = array($filterNormalName, $entityCount);
            }
            $result.=wf_gchartsLine($params, __('Filters'), '100%', '300px', $chartsOptions);
        }
        return ($result);
    }

    /**
     * Performs draft filter entities preprocessing
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return string
     */
    public function filtersPreprocessing($filterId, $templateId) {
        $result = array();
        $unknownFilters = array();
        if (isset($this->filters[$filterId])) {
            $filterData = $this->filters[$filterId]['filters'];
            $filterData = json_decode($filterData, true);
            if (isset($filterData['newfilterdirection'])) {
                $direction = $filterData['newfilterdirection'];
                $this->entitiesType = $direction;
                switch ($direction) {
                    case 'login':
                        $this->filteredEntities = $this->allUserData;
                        break;
                    case 'ukv':
                        $this->filteredEntities = $this->ukv->getUsers();
                        break;
                    case 'employee':
                        $this->filteredEntities = $this->employee;
                        break;
                    case 'numlist':
// TODO
                        break;
                }

//setting base entities count
                $this->saveFilterStats('atstart', sizeof($this->filteredEntities));

                foreach ($filterData as $eachFilter => $eachFilterParam) {
                    if ((ispos($eachFilter, 'newfilter')) AND ( $eachFilter != 'newfilterdirection') AND ( $eachFilter != 'newfiltername')) {
                        $filterMethodName = str_replace('new', '', $eachFilter);
                        if (method_exists($this, $filterMethodName)) {
                            $this->$filterMethodName($direction, $eachFilterParam);
//saving filter stats
                            if (!empty($eachFilterParam)) {
                                $this->saveFilterStats($filterMethodName, sizeof($this->filteredEntities));
                            }
                        } else {
                            show_error(__('Something went wrong') . ': UNKNOWN_FILTER_METHOD ' . $filterMethodName);
                        }
                    }
                }
            }
        }

        if ((!empty($this->filteredEntities)) AND ( !empty($this->entitiesType))) {
            if (wf_CheckPost(array('sendingvisualfilters'))) {
                show_window(__('Filters workflow visualization'), $this->renderFilterStats());
            }
            $this->extractEntitiesNumbers();
            show_window('', $this->messages->getStyledMessage(__('Entities filtered') . ': ' . sizeof($this->filteredEntities) . ' ' . __('Numbers extracted') . ': ' . sizeof($this->filteredNumbers), 'info'));
            $this->generateSmsPool($filterId, $templateId);
            show_window(__('Preview'), $this->renderSmsPoolPreviewContainer($filterId, $templateId));
        } else {
            show_warning(__('Nothing found'));
        }

        return ($result);
    }

    /**
     * Renders SMS pool preview container
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return string
     */
    protected function renderSmsPoolPreviewContainer($filterId, $templateId) {
        $result = '';
        $columns = array('SMS direction', 'Mobile', 'Text', 'Size');
        $result = wf_JqDtLoader($columns, self::URL_ME . '&sending=true&ajpreview=true&filterid=' . $filterId . '&templateid=' . $templateId, false, __('SMS'), 100);
        return ($result);
    }

    /**
     * Renders previously cached preview JSON data
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return void
     */
    public function ajaxPreviewReply($filterId, $templateId) {
        $result = '';
        $previewCacheName = self::POOL_PATH . 'SMZ_PREVIEW_' . $filterId . '_' . $templateId;
        if (file_exists($previewCacheName)) {
            die(file_get_contents($previewCacheName));
        } else {
            $reply = array('aaData' => array());
            die(json_encode($reply));
        }
    }

    /**
     * Generates SMS text for sending/preview
     * 
     * @param int $templateId
     * @param array $entity
     * @param bool $forceTranslit
     * 
     * @return strings
     */
    protected function generateSmsText($templateId, $entity, $forceTranslit) {
        $result = '';

        $result = $this->templates[$templateId]['text'];
        switch ($this->entitiesType) {
            case 'login':
                $result = str_ireplace('{LOGIN}', $this->filteredEntities[$entity]['login'], $result);
                $result = str_ireplace('{REALNAME}', $this->filteredEntities[$entity]['realname'], $result);
                $result = str_ireplace('{TARIFF}', $this->filteredEntities[$entity]['Tariff'], $result);
                $result = str_ireplace('{CREDIT}', $this->filteredEntities[$entity]['Credit'], $result);
                $result = str_ireplace('{CASH}', $this->filteredEntities[$entity]['Cash'], $result);
                $result = str_ireplace('{ROUNDCASH}', round($this->filteredEntities[$entity]['Cash'], 2), $result);
                $result = str_ireplace('{IP}', $this->filteredEntities[$entity]['ip'], $result);
                $result = str_ireplace('{MAC}', $this->filteredEntities[$entity]['mac'], $result);
                $result = str_ireplace('{FULLADDRESS}', $this->filteredEntities[$entity]['fulladress'], $result);
                $result = str_ireplace('{PHONE}', $this->filteredEntities[$entity]['phone'], $result);
                $result = str_ireplace('{MOBILE}', $this->filteredEntities[$entity]['mobile'], $result);
                $result = str_ireplace('{CONTRACT}', $this->filteredEntities[$entity]['contract'], $result);
                $result = str_ireplace('{EMAIL}', $this->filteredEntities[$entity]['email'], $result);
                $result = str_ireplace('{CURDATE}', date("Y-m-d"), $result);
                break;
            case 'ukv':
                $result = str_ireplace('{REALNAME}', $this->filteredEntities[$entity]['realname'], $result);
                $result = str_ireplace('{TARIFF}', $this->ukv->tariffGetName($this->filteredEntities[$entity]['tariffid']), $result);
                $result = str_ireplace('{CASH}', $this->filteredEntities[$entity]['cash'], $result);
                $result = str_ireplace('{ROUNDCASH}', round($this->filteredEntities[$entity]['cash'], 2), $result);
                $result = str_ireplace('{FULLADDRESS}', $this->ukv->userGetFullAddress($entity), $result);
                $result = str_ireplace('{PHONE}', $this->filteredEntities[$entity]['phone'], $result);
                $result = str_ireplace('{MOBILE}', $this->filteredEntities[$entity]['mobile'], $result);
                $result = str_ireplace('{CONTRACT}', $this->filteredEntities[$entity]['contract'], $result);
                $result = str_ireplace('{CURDATE}', date("Y-m-d"), $result);
                break;
        }
        if ($forceTranslit) {
            $result = zb_TranslitString($result, true);
        }
        return ($result);
    }

    /**
     * 
     * @param type $filterId
     * @param type $templateId
     * 
     * @return void
     */
    protected function generateSmsPool($filterId, $templateId) {
        $json = new wf_JqDtHelper();
        $data = array();
        $realSending = (wf_CheckPost(array('sendingperform'))) ? true : false;
        $forceTranslit = (wf_CheckPost(array('forcetranslit'))) ? true : false;

        if (!empty($this->filteredNumbers)) {
            switch ($this->entitiesType) {
                case 'login':
                    foreach ($this->filteredNumbers as $entityId => $numbers) {
                        if (!empty($numbers)) {
                            foreach ($numbers as $io => $eachNumber) {
                                $userLogin = $entityId;
                                $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $this->filteredEntities[$userLogin]['fulladress']);

                                $messageText = $this->generateSmsText($templateId, $userLogin, $forceTranslit);

                                $data[] = $userLink . ' ' . $this->filteredEntities[$userLogin]['realname'];
                                $data[] = $eachNumber;
                                $data[] = $messageText;
                                $data[] = strlen($messageText);
                                $json->addRow($data);
                                unset($data);

//pushing some messages into queue
                                if ($realSending) {
                                    $this->sms->sendSMS($eachNumber, $messageText, false, 'SMSZILLA');
                                }
                            }
                        }
                    }
                    break;

                case 'ukv':
                    foreach ($this->filteredNumbers as $entityId => $number) {
                        if (!empty($number)) {
                            $userId = $entityId;
                            $userLink = wf_Link('?module=ukv&users=true&showuser=' . $userId, web_profile_icon() . ' ' . $this->ukv->userGetFullAddress($userId));
                            $messageText = $this->generateSmsText($templateId, $userId, $forceTranslit);

                            $data[] = $userLink . ' ' . $this->filteredEntities[$userId]['realname'];
                            $data[] = $number;
                            $data[] = $messageText;
                            $data[] = strlen($messageText);
                            $json->addRow($data);
                            unset($data);

//pushing some messages into queue
                            if ($realSending) {
                                $this->sms->sendSMS($number, $messageText, false, 'SMSZILLA');
                            }
                        }
                    }
                    break;

                case 'employee':
                    foreach ($this->filteredNumbers as $entityId => $number) {
                        if (!empty($number)) {
                            $employeeId = $entityId;
                            $employeeLink = wf_Link('?module=employee&edit=' . $employeeId, web_profile_icon() . ' ' . $this->employee[$employeeId]['name']);
                            $messageText = $this->generateSmsText($templateId, $employeeId, $forceTranslit);

                            $data[] = $employeeLink;
                            $data[] = $number;
                            $data[] = $messageText;
                            $data[] = strlen($messageText);
                            $json->addRow($data);
                            unset($data);

//pushing some messages into queue
                            if ($realSending) {
                                $this->sms->sendSMS($number, $messageText, false, 'SMSZILLA');
                            }
                        }
                    }
                    break;
            }
//saving preview data
            file_put_contents(self::POOL_PATH . 'SMZ_PREVIEW_' . $filterId . '_' . $templateId, $json->extractJson());
        }
    }

    /**
     * City filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercity($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cityname'] != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['city'] != $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Address filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteraddress($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['fulladress'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $userAddress = $this->ukv->userGetFullAddress($entity['id']);
                            if (!ispos($userAddress, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Login filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterlogin($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['login'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Month cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashmonth($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
//init slow funds flow object if required
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            $daysLeft = $this->fundsFlow->getOnlineLeftCountFast($entity['login']);
                            if ($daysLeft > 0) {
                                $curMonthDays = date("t");
                                $curDate = date("j");
                                $expr = ($curMonthDays - $curDate) + 30;
                                if ($daysLeft > $expr) {
                                    unset($this->filteredEntities[$entity['login']]);
                                }
                            } else {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Days less cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashdays($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
//init slow funds flow object if required
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            $daysLeft = $this->fundsFlow->getOnlineLeftCountFast($entity['login']);
                            if ($daysLeft > 0) {
                                if ($daysLeft > $param) {
                                    unset($this->filteredEntities[$entity['login']]);
                                }
                            } else {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Greater cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashgreater($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] < $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] < $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Lesser cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashlesser($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] > $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] > $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Tags filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtertags($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->checkInetTagId($entity['login'], $param)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->checkUkvTagId($entity['id'], $param)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * AlwaysOnline filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterao($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['AlwaysOnline'] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Passive filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterpassive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Passive'] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Down filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterdown($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($this->downUsers[$entity['login']] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Tariff filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtertariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Tariff'] != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Branch filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterbranch($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($this->branches->userGetBranch($entity['login']) != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * No tariff users filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternotariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Tariff'] != '*_NO_TARIFF_*') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV tariff users filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvtariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['tariffid'] != $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV activity filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvactive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['active'] != '1') {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Employee appointment filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteremployeeappointment($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'employee':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['appointment'], $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Employee activity filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteremployeeactive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'employee':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['active'] != '1') {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV debtors filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvdebtor($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
//one time debtors loading on filter run
                        if (!$this->ukvDebtorsLoaded) {
                            $this->ukvDebtors = $this->ukv->getDebtors();
                            $this->ukvDebtorsLoaded = true;
                        }
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!isset($this->ukvDebtors[$entity['id']])) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Just sets ext mobiles extraction flag
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterextmobiles($direction, $param) {
        $this->useExtMobiles = true;
    }

    /**
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterrealname($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['realname'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $userRealName = $entity['realname'];
                            if (!ispos($userRealName, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                    case 'employee':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $employeeRealName = $this->filteredEntities[$entity['id']]['name'];
                            if (!ispos($employeeRealName, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

}

?>