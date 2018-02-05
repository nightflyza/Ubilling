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
     * Contains entities that passed full filter set
     *
     * @var array
     */
    protected $filteredEntities = array();

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
     * Contains available employee list
     *
     * @var array
     */
    protected $employee = array();

    /**
     * Base module URL
     */
    const URL_ME = '?module=smszilla';

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
        $this->initFundsFlow();
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
            $inputs.= wf_TextInput('newfiltername', __('Fiter name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '30');

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.=wf_Selector('newfiltercity', $citiesParams, __('City'), '', true, false);
            }

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.= wf_TextInput('newfilteraddress', __('Address contains'), '', true, '40');
            }

            if (($direction == 'login')) {
                $inputs.= wf_TextInput('newfilterlogin', __('Login contains'), '', true, '20');
                $inputs.= wf_CheckInput('newfiltercashmonth', __('Balance is not enough for the next month'), true, false);
                $inputs.= wf_TextInput('newfiltercashdays', __('Balance is enought less than days'), '', true, '5');
            }

            if ($direction == 'ukv') {
                $inputs.=wf_Selector('newfilterukvtariff', $ukvTariffParams, __('User have tariff'), '', true, false);
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
                $inputs.= wf_CheckInput('newfilteremployeeactive', __('Employee is active'), true, false);
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
                $actLinks.= wf_modalAuto(web_icon_search(), __('Preview'), wf_tag('pre') . print_r(json_decode($each['filters'], true), true) . wf_tag('pre', true));
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
        if ((!empty($this->templates)) AND ( !empty($this->filters))) {
            $templatesParams = array();
            foreach ($this->templates as $io => $each) {
                $templatesParams[$each['id']] = $each['name'];
            }

            $filterParams = array();
            foreach ($this->filters as $io => $each) {
                $filterParams[$each['id']] = $each['name'];
            }

            $inputs = wf_Selector('sendingtemplateid', $templatesParams, __('Template'), '', false) . ' ';
            $inputs.= wf_Selector('sendingfilterid', $filterParams, __('Filter'), '', false);
            $inputs.= wf_Submit(__('Preview'));

            $result.=wf_Form(self::URL_ME . '&sending=true', 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('No existing templates or filters available'), 'warning');
        }
        return ($result);
    }

    /**
     * Performs draft filter entities preprocessing
     * 
     * @param int $filterId
     * 
     * @return string
     */
    public function filtersPreprocessing($filterId) {
        $result = array();
        $unknownFilters = array();
        if (isset($this->filters[$filterId])) {
            $filterData = $this->filters[$filterId]['filters'];
            $filterData = json_decode($filterData, true);
            if (isset($filterData['newfilterdirection'])) {
                $direction = $filterData['newfilterdirection'];
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

                        break;
                }

                foreach ($filterData as $eachFilter => $eachFilterParam) {
                    if ((ispos($eachFilter, 'newfilter')) AND ( $eachFilter != 'newfilterdirection') AND ( $eachFilter != 'newfiltername')) {
                        $filterMethodName = str_replace('new', '', $eachFilter);
                        if (method_exists($this, $filterMethodName)) {
                            $this->$filterMethodName($direction, $eachFilterParam);
                        } else {
                            show_error(__('Something went wrong') . ': UNKNOWN_FILTER_METHOD ' . $filterMethodName);
                        }
                    }
                }
            }
        }
        //  debarr($this->filteredEntities);
        deb(sizeof($this->filteredEntities));
        return ($result);
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

}

?>