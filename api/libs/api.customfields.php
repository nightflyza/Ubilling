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
     * Contains all current instance user custom fields data
     *
     * @var array
     */
    protected $userFieldsData = array();

    /**
     * Some predefined stuff like URLs, routes etc
     */
    const TABLE_TYPES = 'cftypes';
    const TABLE_ITEMS = 'cfitems';
    const URL_ME = '?module=cftypes';
    const PROUTE_NEWTYPE = 'newtype';
    const PROUTE_NEWNAME = 'newname';
    const PROUTE_EDID = 'editid';
    const PROUTE_EDTYPE = 'edittype';
    const PROUTE_EDNAME = 'editname';
    const ROUTE_DELETE = 'deletetypeid';
    const ROUTE_EDIT = 'edittypeid';

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
     * 
     * @return string
     */
    protected function renderField($fieldType, $data) {
        if ($fieldType == 'TRIGGER') {
            $data = web_bool_led($data);
        }

        if ($fieldType == 'TEXT') {
            $data = nl2br($data);
        }
        return ($data);
    }

    /**
     * Returns user custom field content depends on its type
     * 
     * @param int $typeId
     * 
     * @return string
     */
    protected function getUserFieldContent($typeId) {
        $result = '';
        if (isset($this->userFieldsData[$typeId])) {
            $result .= $this->userFieldsData[$typeId]['content'];
            ;
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
                    $cells = wf_TableCell($eachType['name'], '30%', 'row2');
                    $cells .= wf_TableCell($this->renderField($eachType['type'], $this->getUserFieldContent($eachType['id'])), '', 'row3');
                    $rows .= wf_TableRow($cells);
                }

                $result = wf_TableBody($rows, '100%', 0, '');
            }
        }
        return($result);
    }

}
