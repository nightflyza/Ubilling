<?php

/**
 * Custom profile fields implementation
 */
class CustomFields {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available custom fileds types as TYPE=>description
     *
     * @var array
     */
    protected $typesAvailable = array();

    /**
     * Current instance user login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Custom Fileds types database abstraction layer
     *
     * @var object
     */
    protected $typesDb = '';

    /**
     * Custom Fileds item records database abstraction layer
     *
     * @var object
     */
    protected $itemsDb = '';

    /**
     * System message helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all available custom profile fileds types as id=>typeData
     *
     * @var array
     */
    protected $allTypes = array();

    /**
     * Contains all current instance user custom fields data as typeId=>cfData
     *
     * @var array
     */
    protected $userFieldsData = array();

    /**
     * Some predefined stuff like URLs, routes etc
     */
    const TABLE_TYPES = 'cftypes';
    const TABLE_ITEMS = 'cfitems';
    const PROUTE_NEWTYPE = 'newtype';
    const PROUTE_NEWNAME = 'newname';
    const PROUTE_EDID = 'editid';
    const PROUTE_EDTYPE = 'edittype';
    const PROUTE_EDNAME = 'editname';
    const ROUTE_DELETE = 'deletetypeid';
    const ROUTE_EDIT = 'edittypeid';
    const PROUTE_MODTYPE = 'modcftypeid';
    const PROUTE_MODLOGIN = 'modcflogin';
    const PROUTE_MODCONTENT = 'modcfcontent';
    const PROUTE_SEARCHTYPEID = 'cftypeid';
    const PROUTE_SEARCHQUERY = 'cfquery';
    const PHOTOSTORAGE_SCOPE = 'CFITEMS';
    const PHOTOSTORAGE_ITEMID_DELIMITER = '|';
    const FILESTORAGE_SCOPE = 'CFITEMS';
    const FILESTORAGE_ITEMID_DELIMITER = '|';
    const URL_ME = '?module=cftypes';
    const URL_EDIT_BACK = '?module=useredit&username=';
    const URL_PHOTOUPL = '?module=photostorage&scope=CFITEMS&mode=list&itemid=';
    const URL_FILEUPL = '?module=filestorage&scope=CFITEMS&mode=list&itemid=';

//          _
//        {` `'-.
//       {       \   (\._
//        {       | /   o'.
//         `}    /.`'.___.'
//         {    .' ,  \_/`\
//         }  /`_   '-. '=/
//        {  .'     `\;`'`
//         { ;       /_
//          '-'...-;`__\ 
//              
//                ^^^^ A CE BILOCHKA

    /**
     * Creates new CF instance
     * 
     * @param string $login
     */
    public function __construct($login = '') {
        $this->loadAltCfg();
        $this->initMessages();
        $this->setAvailableTypes();
        $this->setLogin($login);
        $this->initDb();
        $this->loadTypes();

        if (!empty($this->login)) {
            $this->loadUserItems();
        }
    }

    /**
     * Inits database absctraction layer for further usage
     * 
     * @return void
     */
    protected function initDb() {
        $this->typesDb = new NyanORM(self::TABLE_TYPES);
        $this->itemsDb = new NyanORM(self::TABLE_ITEMS);
    }

    /**
     * Loads system alter config to protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAltCfg() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system messages helper protected instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing CF types from database
     * 
     * @return void
     */
    protected function loadTypes() {
        $this->allTypes = $this->typesDb->getAll('id');
    }

    /**
     * Sets available custom fields types
     * 
     * @return void
     */
    protected function setAvailableTypes() {
        //basic types
        $this->typesAvailable = array(
            'VARCHAR' => __('String'),
            'TRIGGER' => __('Trigger'),
            'TEXT' => __('Text'),
            'INT' => __('Integer'),
            'FLOAT' => __('Float'),
            'FINANCE' => __('Finance'),
            'NETWORK' => __('Network'),
            'URL' => __('URL'),
            'DATE' => __('Date'),
            'TIME' => __('Time'),
            'COLOR' => __('Color'),
            'LIST' => __('List')
        );

        //optional types
        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $this->typesAvailable['PHOTO'] = __('Image');
        }

        if ($this->altCfg['FILESTORAGE_ENABLED']) {
            $this->typesAvailable['FILE'] = __('File');
        }
    }

    /**
     * Sets current instance user login
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login = '') {
        if (!empty($login)) {
            $this->login = ubRouting::filters($login, 'mres');
        }
    }

    /**
     * Returns all available CF types as id=>typeData
     * 
     * @return array
     */
    public function getTypesAll() {
        return($this->allTypes);
    }

    /**
     * Returns all available fields data for all users
     * 
     * @return array
     */
    public function getAllFieldsData() {
        $result = array();
        if (!empty($this->allTypes)) {
            $result = $this->itemsDb->getAll();
        }
        return($result);
    }

    /**
     * Returns existing CF type data by its typeId
     * 
     * @param int $typeId Existing CF type database ID
     * 
     * @return array
     */
    protected function getTypeData($typeId) {
        $result = array();
        if (isset($this->allTypes[$typeId])) {
            $result = $this->allTypes[$typeId];
        }
        return($result);
    }

    /**
     * Loads current instance users items data into protected property
     * 
     * @return void
     */
    protected function loadUserItems() {
        if (!empty($this->login)) {
            if (!empty($this->allTypes)) {
                $this->itemsDb->where('login', '=', $this->login);
                $this->userFieldsData = $this->itemsDb->getAll('typeid');
            }
        }
    }

    /**
     * Flushes all of assigned to users CFs data from database
     * 
     * @param int $typeId Existing CF type database ID
     * 
     * @return void
     */
    protected function flushType($typeId) {
        $typeId = ubRouting::filters($typeId, 'int');
        $this->itemsDb->where('typeid', '=', $typeId);
        $this->itemsDb->delete();
        log_register('CFTYPE FLUSH [' . $typeId . ']');
    }

    /**
     * Deletes CF type from database by its ID and flushes assigned
     * 
     * @param int $typeId Existing CF type database ID
     * 
     * @return void
     */
    public function deleteType($typeId) {
        $typeId = ubRouting::filters($typeId, 'int');
        $this->typesDb->where('id', '=', $typeId);
        $this->typesDb->delete();
        log_register('CFTYPE DELETE [' . $typeId . ']');
        $this->flushType($typeId);
    }

    /**
     * Deletes all of CF items from database associated with some user
     * 
     * @return void
     */
    public function flushAllUserFieldsData() {
        if (!empty($this->login)) {
            $this->itemsDb->where('login', '=', $this->login);
            $this->itemsDb->delete();
            log_register('CF FLUSH (' . $this->login . ')');
        }
    }

    /**
     * Creates new CF type in database
     * 
     * @param string $type Type of the CF (VARCHAR, TRIGGER, TEXT etc)
     * @param string $name Name of the custom field for display
     * 
     * @return void
     */
    public function createType($type, $name) {
        $type = ubRouting::filters($type, 'mres');
        $name = ubRouting::filters($name, 'mres');
        if ((!empty($name)) AND ( !empty($type))) {
            $this->typesDb->data('type', $type);
            $this->typesDb->data('name', $name);
            $this->typesDb->create();
            $newId = $this->typesDb->getLastId();
            log_register('CFTYPE ADD `' . $type . '` NAME `' . $name . '` AS [' . $newId . ']');
        }
    }

    /**
     * Returns Custom Field Type creation form 
     * 
     * @return string
     */
    public function renderTypeCreationForm() {
        $result = '';
        $inputs = wf_Selector(self::PROUTE_NEWTYPE, $this->typesAvailable, __('Field type'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_NEWNAME, __('Field name'), '', true, 15);
        $inputs .= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Returns CF type editing form
     * 
     * @param int $typeId Existing CF type ID
     * 
     * @return string
     */
    public function renderTypeEditForm($typeId) {
        $result = '';
        $typeId = ubRouting::filters($typeId, 'int');
        $typeData = $this->getTypeData($typeId);
        if (!empty($typeData)) {
            $inputs = wf_HiddenInput('editid', $typeId);
            $inputs .= wf_Selector('edittype', $this->typesAvailable, 'Field type', $typeData['type'], true);
            $inputs .= wf_TextInput('editname', 'Field name', $typeData['name'], true);
            $inputs .= wf_Submit('Edit');
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ' [' . $typeId . '] ' . __('Not exists'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME);
        return($result);
    }

    /**
     * Saves CF type editing form into database
     * 
     * @return void
     */
    public function saveType() {
        if (ubRouting::checkPost(array(self::PROUTE_EDID, self::PROUTE_EDTYPE, self::PROUTE_EDNAME))) {
            $id = ubRouting::post(self::PROUTE_EDID, 'int');
            $type = ubRouting::post(self::PROUTE_EDTYPE, 'mres');
            $name = ubRouting::post(self::PROUTE_EDNAME);
            $nameF = ubRouting::filters($name, 'mres');
            if (isset($this->allTypes[$id])) {
                $this->typesDb->data('type', $type);
                $this->typesDb->data('name', $nameF);
                $this->typesDb->where('id', '=', $id);
                $this->typesDb->save();
                log_register('CFTYPE CHANGE [' . $id . '] NAME `' . $name . '`');
            }
        }
    }

    /**
     * Returns human-readable type description
     * 
     * @param string $type
     * 
     * @return string
     */
    protected function getTypeNameDesc($type) {
        $result = '';
        if (isset($this->typesAvailable[$type])) {
            $result .= $this->typesAvailable[$type];
        } else {
            $result .= __('Disabled');
        }
        return($result);
    }

    /**
     * Renders list of available CF types
     * 
     * @return string
     */
    public function renderTypesList() {
        $result = '';
        if (!empty($this->allTypes)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Field type'));
            $cells .= wf_TableCell(__('Field name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTypes as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($this->getTypeNameDesc($each['type']));
                $cells .= wf_TableCell($each['name']);
                $urlDelete = self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $each['id'];
                $urlEdit = self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $each['id'];
                $cancelUrl = self::URL_ME;
                $deleteTitle = __('Delete') . ' ' . $each['name'] . '?';
                $deleteAlert = $this->messages->getDeleteAlert() . '. ' . __('All related data will be deleted too');
                $actionControls = wf_ConfirmDialog($urlDelete, web_delete_icon(), $deleteAlert, '', $cancelUrl, $deleteTitle) . ' ';
                $actionControls .= wf_JSAlert($urlEdit, web_edit_icon(), $this->messages->getEditAlert());
                $cells .= wf_TableCell($actionControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Returns preformatted view of CF content preprocessed depends by its type
     * 
     * @param string $fieldType Type of the data (VARCHAR, TRIGGER, TEXT etc)
     * @param string $data Data of CF
     * @param int $typeId CF type ID
     * 
     * @return string
     */
    protected function renderField($fieldType, $data, $typeId = 0) {
        if ($fieldType == 'TRIGGER') {
            $data = ($data) ? wf_img_sized('skins/icon_active.gif', '', '12') : wf_img_sized('skins/icon_inactive.gif', '', '12');
        }

        if ($fieldType == 'TEXT') {
            $data = nl2br($data);
        }

        if ($fieldType == 'URL') {
            $data = wf_Link($data, $data, false, '', 'target="_BLANK"');
        }

        if ($fieldType == 'COLOR') {
            if (!empty($data)) {
                $data = wf_tag('color', false, '', 'style="color:' . $data . '"') . $data . wf_tag('font', true);
            }
        }

        if ($fieldType == 'PHOTO') {
            if (!empty($typeId) AND ! empty($this->login)) {
                if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                    $photostorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $this->login . self::PHOTOSTORAGE_ITEMID_DELIMITER . $typeId);
                    $data = $photostorage->renderImagesRaw();
                }
            }
        }

        if ($fieldType == 'FILE') {
            if (!empty($typeId) AND ! empty($this->login)) {
                if ($this->altCfg['FILESTORAGE_ENABLED']) {
                    $fileStorage = new FileStorage(self::FILESTORAGE_SCOPE, $this->login . self::FILESTORAGE_ITEMID_DELIMITER . $typeId);
                    $data = $fileStorage->renderFilesPreview(false, '', '', 64);
                }
            }
        }

        return ($data);
    }

    /**
     * Returns user custom field content depends on its type
     * 
     * @param int $typeId existing CF type ID
     * 
     * @return string|void
     */
    public function getUserFieldContent($typeId) {
        $result = '';
        if (isset($this->userFieldsData[$typeId])) {
            $result .= $this->userFieldsData[$typeId]['content'];
        }
        return($result);
    }

    /**
     * Returns available user custom fields for user profile
     * 
     * @result
     */
    public function renderUserFields() {
        $result = '';
        if (!empty($this->login)) {
            if (!empty($this->allTypes)) {
                $rows = '';
                foreach ($this->allTypes as $io => $eachType) {
                    $cells = wf_TableCell($this->renderTypeName($eachType['id']), '30%', 'row2', 'valign="top"');
                    $cells .= wf_TableCell($this->renderField($eachType['type'], $this->getUserFieldContent($eachType['id']), $eachType['id']), '', 'row3', 'valign="top"');
                    $rows .= wf_TableRow($cells);
                }

                $result = wf_TableBody($rows, '100%', 0, '', UserProfile::MAIN_TABLE_STYLE);
            }
        }
        return($result);
    }

    /**
     * Sets content of custom field for current instance user
     * 
     * @param string $login
     * @param int $typeId
     * @param string $content
     * @throws Exception
     * 
     * @return void
     */
    protected function setFieldContent($login, $typeId, $content) {
        $typeId = ubRouting::filters($typeId, 'int');
        $contentF = ubRouting::filters($content, 'mres');
        if (!empty($login)) {
            if (isset($this->allTypes[$typeId])) {
                $this->itemsDb->data('typeid', $typeId);
                $this->itemsDb->data('login', $login);
                $this->itemsDb->data('content', $contentF);
                //update or create field content
                if (isset($this->userFieldsData[$typeId])) {
                    $currentFieldId = $this->userFieldsData[$typeId]['id'];
                    $this->itemsDb->where('id', '=', $currentFieldId);
                    $this->itemsDb->save();
                } else {
                    $this->itemsDb->create();
                }

                $logContent = (strlen($content) < 20) ? $content : substr($content, 0, 20) . '..';
                log_register('CF SET (' . $login . ') TYPE [' . $typeId . ']' . ' ON `' . $logContent . '`');
            } else {
                throw new Exception('EX_TYPEID_NOT_EXISTS');
            }
        } else {
            throw new Exception('EX_LOGIN_EMPTY');
        }
    }

    /**
     * Returns existing type name, may be cleaned from technical data
     * 
     * @param int $typeId
     * 
     * @return string
     */
    public function renderTypeName($typeId) {
        $result = '';
        if (isset($this->allTypes[$typeId])) {
            $typeData = $this->allTypes[$typeId];
            $typeName = $typeData['name'];

            //some optional postprocessing
            if ($typeData['type'] == 'LIST') {
                if (ispos($typeName, '[') AND ispos($typeName, ']')) {
                    $rawList = zb_ParseTagData('[', ']', $typeName);
                    $typeName = str_replace('[' . $rawList . ']', '', $typeName);
                }
            }

            $result = $typeName;
        } else {
            $result .= '[' . $typeId . '] ' . __('Not exists');
        }
        return($result);
    }

    /**
     * Renders CF editor controller
     * 
     * @return void
     */
    public function renderUserFieldEditor() {
        global $billing;
        $result = '';
        //editing subroutine 
        if (ubRouting::checkPost(self::PROUTE_MODTYPE)) {
            $this->setFieldContent(ubRouting::post(self::PROUTE_MODLOGIN), ubRouting::post(self::PROUTE_MODTYPE), ubRouting::post(self::PROUTE_MODCONTENT));
            //is user reset required after field change?
            if ($this->altCfg['RESETONCFCHANGE']) {
                $billing->resetuser($this->login);
                log_register('RESET (' . $this->login . ')');
            }
            ubRouting::nav(self::URL_EDIT_BACK . $this->login);
        }

        if (!empty($this->allTypes)) {
            $cells = wf_TableCell(__('Field name'));
            $cells .= wf_TableCell(__('Current value'));
            $cells .= wf_TableCell(__('Edit'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allTypes as $io => $eachType) {
                $cells = wf_TableCell($this->renderTypeName($eachType['id']), '', '', 'valign="top"');
                $cells .= wf_TableCell($this->renderField($eachType['type'], $this->getUserFieldContent($eachType['id']), $eachType['id']), '', '', 'valign="top"');
                $cells .= wf_TableCell($this->renderTypeController($this->login, $eachType['type'], $eachType['id']), '', '', 'valign="top"');
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0, '', UserProfile::MAIN_TABLE_STYLE);
        }
        return($result);
    }

    /**
     * Returns editing controller for CF assigned to user
     * 
     * @param string $login Existing user login
     * @param string $type Type of CF to return control
     * @param int    $typeId Type ID for change
     * 
     * @return string
     */
    protected function renderTypeController($login, $type, $typeId) {
        $result = '';
        $type = ubRouting::filters($type, 'vf');
        $typeId = ubRouting::filters($typeId, 'int');

        $currentFieldContent = '';
        if (isset($this->userFieldsData[$typeId])) {
            $currentFieldContent = $this->userFieldsData[$typeId]['content'];
        }

        //basic forms inputs
        $inputs = wf_HiddenInput(self::PROUTE_MODTYPE, $typeId);
        $inputs .= wf_HiddenInput(self::PROUTE_MODLOGIN, $login);

        if ($type == 'VARCHAR') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 20);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'TRIGGER') {
            $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
            $inputs .= wf_Selector(self::PROUTE_MODCONTENT, $triggerOpts, '', $currentFieldContent, false);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'TEXT') {
            $inputs .= wf_TextArea(self::PROUTE_MODCONTENT, '', $currentFieldContent, true, '45x5');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'INT') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 10, 'digits');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'FLOAT') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 10, 'float');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'FINANCE') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 10, 'float');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'NETWORK') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 10, 'net-cidr');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'URL') {
            $inputs .= wf_TextInput(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 20, 'url');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'DATE') {
            $inputs .= wf_DatePickerPreset(self::PROUTE_MODCONTENT, $currentFieldContent, true) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'TIME') {
            $inputs .= wf_TimePickerPreset(self::PROUTE_MODCONTENT, $currentFieldContent, '', false) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'COLOR') {
            $inputs .= wf_ColPicker(self::PROUTE_MODCONTENT, '', $currentFieldContent, false, 10);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, '');
        }

        if ($type == 'LIST') {
            if (isset($this->allTypes[$typeId])) {
                $typeName = $this->allTypes[$typeId]['name'];
                if (ispos($typeName, '[') AND ispos($typeName, ']')) {
                    $rawList = zb_ParseTagData('[', ']', $typeName);
                    if (!empty($rawList)) {
                        $selectorOpts = array();
                        $rawList = explode(',', $rawList);
                        if (!empty($rawList)) {
                            $selectorOpts[''] = '-';
                            foreach ($rawList as $io => $each) {
                                $cleanOpt = trim($each);
                                $selectorOpts[$cleanOpt] = $cleanOpt;
                            }
                        }

                        $inputs .= wf_Selector(self::PROUTE_MODCONTENT, $selectorOpts, '', $currentFieldContent, false);
                        $inputs .= wf_Submit(__('Save'));
                        $result = wf_Form("", 'POST', $inputs, '');
                    } else {
                        $result .= __('Wrong element format') . ': ' . __('is empty');
                    }
                } else {
                    $result .= __('Wrong element format') . ': ' . __('No tags');
                }
            }
        }

        if ($type == 'PHOTO') {
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $uploadUrl = self::URL_PHOTOUPL . $login . self::PHOTOSTORAGE_ITEMID_DELIMITER . $typeId;
                $result = wf_Link($uploadUrl, wf_img('skins/photostorage.png', __('Upload images')) . ' ' . __('Upload images'));
            } else {
                $result = __('Disabled');
            }
        }

        if ($type == 'FILE') {
            if ($this->altCfg['FILESTORAGE_ENABLED']) {
                $fileStorageItemId = $login . self::FILESTORAGE_ITEMID_DELIMITER . $typeId;
                $uploadUrl = self::URL_FILEUPL . $fileStorageItemId;
                $result = wf_Link($uploadUrl, wf_img('skins/photostorage_upload.png') . ' ' . __('Upload files'), false);
            } else {
                $result = __('Disabled');
            }
        }
        return ($result);
    }

    /**
     * Returns search controller for CFs assigned to user
     * 
     * @param string $type Type of CF to return control
     * @param int    $typeid Type ID for change
     * 
     * @return string
     */
    function getTypeSearchControl($type, $typeid) {
        $type = ubRouting::filters($type, 'vf');
        $typeid = ubRouting::filters($typeid, 'int');

        $result = '';
        $inputs = '';
        $ignoredTypes = array('PHOTO', 'FILE', 'COLOR'); //I`m too lazy to do it today
        $ignoredTypes = array_flip($ignoredTypes);

        if (!isset($ignoredTypes[$type])) {
            if ($type == 'VARCHAR') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 20);
            }

            if ($type == 'TRIGGER') {
                $triggerOpts = array(1 => __('Yes'), 0 => __('No'));
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_Selector(self::PROUTE_SEARCHQUERY, $triggerOpts, '', '', false);
            }

            if ($type == 'TEXT') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 20);
            }

            if ($type == 'INT') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 10, 'digits');
            }

            if ($type == 'FLOAT') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 10, 'float');
            }

            if ($type == 'FINANCE') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 10, 'finance');
            }

            if ($type == 'NETWORK') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 10, 'net-cidr');
            }

            if ($type == 'URL') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TextInput(self::PROUTE_SEARCHQUERY, '', '', false, 20, 'url');
            }

            if ($type == 'DATE') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid) . ' ';
                $inputs .= wf_DatePicker(self::PROUTE_SEARCHQUERY, true);
            }

            if ($type == 'TIME') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                $inputs .= wf_TimePickerPreset(self::PROUTE_SEARCHQUERY, '', '', false) . ' ';
            }

            if ($type == 'LIST') {
                $inputs = wf_HiddenInput(self::PROUTE_SEARCHTYPEID, $typeid);
                if (isset($this->allTypes[$typeid])) {
                    $typeName = $this->allTypes[$typeid]['name'];
                    if (ispos($typeName, '[') AND ispos($typeName, ']')) {
                        $rawList = zb_ParseTagData('[', ']', $typeName);
                        if (!empty($rawList)) {
                            $selectorOpts = array();
                            $rawList = explode(',', $rawList);
                            if (!empty($rawList)) {
                                $selectorOpts[''] = '-';
                                foreach ($rawList as $io => $each) {
                                    $cleanOpt = trim($each);
                                    $selectorOpts[$cleanOpt] = $cleanOpt;
                                }
                                $inputs .= wf_Selector(self::PROUTE_SEARCHQUERY, $selectorOpts, '', '', false);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($inputs)) {
            $inputs .= wf_Submit(__('Search')); //appending search button to each
            $result = wf_Form("", 'POST', $inputs, '');
        }

        return ($result);
    }

}
