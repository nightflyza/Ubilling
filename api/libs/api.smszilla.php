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
        $this->loadCities();
        $this->loadUsers();
        $this->loadTagTypes();
        $this->loadTariffs();
        $this->loadTemplates();
        $this->loadFilters();
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
        $this->allUserData = zb_UserGetAllDataCache();
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
     * Inits system messages helper into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets all necessary options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->filterTypes = array(
            self::URL_ME . '&filters=true&newfilterdirection=none' => __('No'),
            self::URL_ME . '&filters=true&newfilterdirection=login' => __('Login'),
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
        $result.=wf_Link(self::URL_ME . '&filters=true', wf_img('skins/icon_mobile.gif') . ' ' . __('Numbers lists'), true, 'ubButton') . ' ';
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

        $numListParams = array('' => '-');

        $nasParams = array('' => __('Any'));

        $branchParams = array('' => __('Any'));

        $inputs.=wf_AjaxSelectorAC('inputscontainer', $this->filterTypes, __('SMS direction'), '', true);
        $inputs.= wf_tag('br');

        if ($direction != 'none') {
            $inputs.= wf_HiddenInput('newfilterdirection', $direction);
            $inputs.= wf_TextInput('newfiltername', __('Fiter name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '30');

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.=wf_Selector('newfiltercity', $citiesParams, __('City'), '', true, false);
            }

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs.= wf_TextInput('newfilteraddress', __('Address contains') . ' ' . __('(separator - comma)'), '', true, '40');
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
                $inputs.=wf_Selector('newfilternas', $nasParams, __('NAS'), '', true, false);
                $inputs.=wf_Selector('newfilterbranch', $branchParams, __('Branch'), '', true, false);
            }

            if (($direction == 'numlist')) {
                $inputs.=wf_Selector('newfilternumlist', $numListParams, __('Numbers list'), '', true, false);
                $inputs.=wf_TextInput('newfilternumcontain', __('Notes contains'), '', true, '20');
            }

            if ($direction == 'employee') {
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
                $rows.= wf_TableRow($cells, 'row3');
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
                        foreach ($this->allUserData as $userLogin => $eachUser) {
                            foreach ($filterData as $eachFilter => $eachFilterParam) {
                                if ((ispos($eachFilter, 'newfilter')) AND ( $eachFilter != 'newfilterdirection') AND ( $eachFilter != 'newfiltername')) {
                                    $filterMethodName = str_replace('new', '', $eachFilter);
                                    if (method_exists($this, $filterMethodName)) {
                                        $this->$filterMethodName($direction, $eachUser, $eachFilterParam);
                                    } else {
                                        $unknownFilters[$filterMethodName] = $filterMethodName;
                                    }
                                }
                            }
                        }


                        break;
                    case 'ukv':

                        break;
                    case 'employee':

                        break;
                    case 'numlist':

                        break;
                }

                debarr($unknownFilters);
            }
        }
        debarr($this->filteredEntities);
        return ($result);
    }

    protected function filtercity($direction, $entity, $param) {
        if (!empty($param)) {
            if (!empty($entity)) {
                switch ($direction) {
                    case 'login':
                        if ($entity['cityname'] == $param) {
                            $this->filteredEntities[$entity['login']] = $entity;
                        }
                        break;
                }
            }
        }
    }

    protected function filteraddress($direction, $entity, $param) {
        
        if (!empty($param)) {
            if (!empty($entity)) {
                $explodedParams = explode(',', $param);
                switch ($direction) {
                    case 'login':
                        foreach ($explodedParams as $io => $each) {
                            $search = trim($each);
                            if (ispos($entity['fulladress'], $search)) {
                                $this->filteredEntities[$entity['login']] = $entity;
                            } else {
//                                if (isset($this->filteredEntities[$entity['login']])) {
//                                    unset($this->filteredEntities[$entity['login']]);
//                                }
                            }
                        }
                        break;
                }
            }
        }
    }

}

?>