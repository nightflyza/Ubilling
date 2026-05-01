<?php

/**
 * Switch models management class
 */
class SwitchModels {
 
    /**
     * Database abstraction layer for switchmodels
     * 
     * @var object
     */
    protected $modelsDb='';

    /**
     * Database abstraction layer for switches
     * 
     * @var object
     */
    protected $switchesDb='';

    /**
     * Database abstraction layer for onus
     * 
     * @var object
     */
    protected $onuDb='';

    /**
     * Some predefined stuff
     */
    
    const TABLE_MODELS = 'switchmodels';
    const TABLE_SWITCHES = 'switches';
    const TABLE_ONUS = 'pononu';
    const URL_ME = '?module=switchmodels';
    const URL_SWITCHES = '?module=switches';
    const ROUTE_DELETE = 'deletesm';
    const ROUTE_EDIT = 'edit';
    const ROUTE_CREATE = 'createsm';

    const PROUTE_NEWNAME = 'newsm';
    const PROUTE_NEWPORTS = 'newsmp';
    const PROUTE_NEWSNMPTPL = 'newsst';
    const PROUTE_EDITNAME = 'editmodelname';
    const PROUTE_EDITPORTS = 'editports';
    const PROUTE_EDITSNMPTPL = 'editsnmptemplate';

    /**
     * U tome je smisao
     * U tome je ljepota
     * Tko tebe kamenom
     * Liši ga života
     */
    public function __construct() {
        $this->initDb();
        $this->initSwitchesDb();
        $this->initOnuDb();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDb() {
        $this->modelsDb = new NyanORM(self::TABLE_MODELS);
    }

    /**
     * Inits switches database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initSwitchesDb() {
        $this->switchesDb = new NyanORM(self::TABLE_SWITCHES);
    }

    /**
     * Inits ONUs database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initOnuDb() {
        $this->onuDb = new NyanORM(self::TABLE_ONUS);
    }

    /**
     * Creates new switch model in database
     * 
     * @param string $name
     * @param int $ports
     * @param string $snmptemplate
     * 
     * @return void|string on error
     */
    public function create($name, $ports, $snmptemplate = '') {
        $ports = ubRouting::filters($ports, 'int');
        $nameF = ubRouting::filters($name, 'mres');
        $nameF = ubRouting::filters($nameF,'safe');
        $snmptemplateF = ubRouting::filters($snmptemplate, 'mres');
        $result = '';

        if (!empty($nameF)) {
                $this->modelsDb->data('modelname', $nameF);
                if (!empty($ports)) {
                $this->modelsDb->data('ports', $ports);
                }
                if (!empty($snmptemplate)) {
                    $this->modelsDb->data('snmptemplate', $snmptemplateF);
                }
                $this->modelsDb->create();

                $newId = $this->modelsDb->getLastId();
                log_register('SWITCHMODEL CREATE [' . $newId . '] NAME `' . $name . '` PORTS `' . $ports . '` SNMPTEMPLATE `' . $snmptemplate . '` ');
            } else {
                $result = __('Name') . ' ' . __('is empty');
                log_register('SWITCHMODEL CREATE FAIL NAME EMPTY');
            }

        return ($result);
    }

    /**
     * Returns data of switch model by its ID
     * 
     * @param int $id
     * 
     * @return array
     */
    public function getData($id) {
        $id = ubRouting::filters($id, 'int');
        $this->modelsDb->where('id', '=', $id);
        $rawData = $this->modelsDb->getAll();
        if (!empty($rawData)) {
            $result = $rawData[0];
        } else {
            $result = array();
        }
        return ($result);
    }

    

/**
 * Deletes switch model from database by its ID
 * 
 * @param integer $id
 * 
 * @return void|string on error
 */
public function delete($id) {
    $id = ubRouting::filters($id, 'int');
    $result = '';
    if (!empty($id)) {
        $this->initSwitchesDb();
        $this->initOnuDb();

        $this->switchesDb->where('modelid', '=', $id);
        $this->switchesDb->selectable('id');
        $switchesUsingThisModel = $this->switchesDb->getAll();
        $this->switchesDb->selectable();

        $this->onuDb->where('onumodelid', '=', $id);
        $this->onuDb->selectable('id');
        $onuUsingThisModel = $this->onuDb->getAll();
        $this->onuDb->selectable();

        //is this model used by any devices?
        if (empty($switchesUsingThisModel) and empty($onuUsingThisModel)) {
            $currentModelData = $this->getData($id);
            $modelName = $currentModelData['modelname'];
            $this->modelsDb->where('id', '=', $id);
            $this->modelsDb->delete();
            log_register('SWITCHMODEL DELETE  [' . $id . '] NAME `' . $modelName . '`');
        } else {
            $result .= __('You know, we really would like to let you perform this action, but our conscience does not allow us to do');
            log_register('SWITCHMODEL DELETE  [' . $id . '] FAIL IN USE');
        }
    } else {
        $result .= __('Model') . ' ' . __('is empty');
        log_register('SWITCHMODEL DELETE  [' . $id . '] FAIL ID EMPTY');
    }
    return ($result);
}


/**
 * Updates existing switch model in database
 * 
 * @param int $id
 * @param string $name
 * @param int $ports
 * @param string $snmptemplate
 * 
 * @return void|string on error
 */
public function update($id, $name='', $ports='', $snmptemplate='') {
    $id = ubRouting::filters($id, 'int');
    $nameF = ubRouting::filters($name, 'mres');
    $nameF = ubRouting::filters($nameF,'safe');
    $ports = ubRouting::filters($ports, 'int');
    $snmptemplateF = ubRouting::filters($snmptemplate, 'mres');
    $result = '';
    
    if (!empty($name)) {
        $this->modelsDb->where('id', '=', $id);
        $this->modelsDb->data('modelname', $nameF);
        

        if (!empty($ports)) {
            $this->modelsDb->data('ports', $ports);
        } else {
            $this->modelsDb->data('ports', 0);
        }
        
        $this->modelsDb->data('snmptemplate', $snmptemplateF);
        $this->modelsDb->save();

        log_register('SWITCHMODEL CHANGE [' . $id . '] NAME `' . $name . '` PORTS `' . $ports . '` SNMPTEMPLATE `' . $snmptemplate . '` ');
    } else {
      log_register('SWITCHMODEL CHANGE [' . $id . '] FAIL NAME EMPTY');
      $result .= __('Name') . ' ' . __('is empty');
    }
    return ($result);
}

/**
 * Renders switch models module navigation links
 * 
 * @return string
 */
public function renderNavLinks() {
    $result = '';
    $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CREATE . '=true', wf_img('skins/add_icon.png') . ' ' . __('Create'), false, 'ubButton').' ';
    $result .= wf_Link(self::URL_SWITCHES, wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');
    return ($result);
}


/**
 * Returns switch model add form
 * 
 * @return string
 */
public function renderCreateForm() {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('MODLTPL_SEARCHBL');

    $allSnmpTemplates = $this->getSnmpTemplatesAll();
    $sup=wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = wf_TextInput(self::PROUTE_NEWNAME, __('Model') . $sup, '', true, 20);
    $inputs .= wf_TextInput(self::PROUTE_NEWPORTS, 'Ports', '', true, 5,'digits');
    if ($searchableFlag) {
        $inputs .= wf_SelectorSearchable(self::PROUTE_NEWSNMPTPL, $allSnmpTemplates, 'SNMP template', '');
    } else {
        $inputs .= wf_Selector(self::PROUTE_NEWSNMPTPL, $allSnmpTemplates, 'SNMP template', '');
    }
    $inputs .= wf_delimiter() . wf_Submit('Create');

    $result = wf_Form('', 'POST', $inputs, 'glamour');

    return ($result);
}

/**
 * Renders switch model edit form
 * 
 * @param int $id
 * 
 * @return string
 */
public function renderEditForm($id) {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('MODLTPL_SEARCHBL');

    $id = ubRouting::filters($id, 'int');
    $modelData = $this->getData($id);
    $allSnmpTemplates = $this->getSnmpTemplatesAll();
    $sup=wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput(self::PROUTE_EDITNAME, __('Model') . $sup, $modelData['modelname'], true, 20);
    $inputs .= wf_TextInput(self::PROUTE_EDITPORTS, 'Ports', $modelData['ports'], true, 5,'digits');
    if ($searchableFlag) {
        $inputs .= wf_SelectorSearchable(self::PROUTE_EDITSNMPTPL, $allSnmpTemplates, 'SNMP template', $modelData['snmptemplate']);
    } else {
        $inputs .= wf_Selector(self::PROUTE_EDITSNMPTPL, $allSnmpTemplates, 'SNMP template', $modelData['snmptemplate']);
    }
    $inputs .= wf_delimiter() . wf_Submit('Save');
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

    /**
     * Returns array of all available switch models
     *
     * @return array
     */
    public function getAll() {
        global $ubillingConfig;
        $sortByModelName = $ubillingConfig->getAlterParam('DEVICES_LISTS_SORT_BY_MODELNAME');
        $this->modelsDb->orderBy('id', 'DESC');
        $result = $this->modelsDb->getAll();

        if (!empty($result) and $sortByModelName) {
            $result = zb_sortArray($result, 'modelname');
        }

        return ($result);
    }

    /**
     * Returns array of all available switch models names as id=>modelName
     * 
     * @return array
     */
    public function getAllNames() {
        $result = array();
        $allModels = $this->getAll();
        if (!empty($allModels)) {
            foreach ($allModels as $io => $eachModel) {
                $result[$eachModel['id']] = $eachModel['modelname'];
            }
        }
        return ($result);
    }

    /**
     * Returns array of all available snmp model templates as name=>device description
     * 
     * @return array
     */
    public function getSnmpTemplatesAll() {
        $allSnmpTemplatesRaw = sp_SnmpGetAllModelTemplates();
        $allSnmpTemplates = array('' => __('None'));
        if (!empty($allSnmpTemplatesRaw)) {
            foreach ($allSnmpTemplatesRaw as $io => $each) {
                if (isset($each['define'])) {
                    if (isset($each['define']['DEVICE'])) {
                        $allSnmpTemplates[$io] = $each['define']['DEVICE'];
                    } else {
                        $allSnmpTemplates[$io] = '⚠️ ' . __('Template') . ' ' . $io . ' - ' . __('is corrupted');
                    }
                } else {
                    $allSnmpTemplates[$io] = '⚠️ ' . __('Template') . ' ' . $io . ' - ' . __('is corrupted');
                }
            }
        }
        return ($allSnmpTemplates);
    }


/**
 * Returns list of all available switch models
 * 
 * @return string
 */
public function renderList() {
    
    $result = '';
    $this->initSwitchesDb();
    $this->initOnuDb();

    $allmodels = $this->getAll();
    $allSwitches = $this->switchesDb->getAll();
    $allSnmpTemplates = $this->getSnmpTemplatesAll();
    $modelsCount = array();

    //Switch devices count
    if (!empty($allSwitches)) {
        foreach ($allSwitches as $io => $eachSwitchData) {
            if (isset($modelsCount[$eachSwitchData['modelid']])) {
                $modelsCount[$eachSwitchData['modelid']]++;
            } else {
                $modelsCount[$eachSwitchData['modelid']] = 1;
            }
        }
    }

    //PON devices count
    $onuDevicesDb = new NyanORM('pononu');
    $onuDevicesDb->selectable(array('id', 'onumodelid'));
    $allOnu = $onuDevicesDb->getAll();
        if (!empty($allOnu)) {
            foreach ($allOnu as $io => $eachOnuData) {
                if (isset($modelsCount[$eachOnuData['onumodelid']])) {
                    $modelsCount[$eachOnuData['onumodelid']]++;
                } else {
                    $modelsCount[$eachOnuData['onumodelid']] = 1;
                }
            }
    }
    

    if (!empty($allmodels)) {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Model'));
        $tablecells .= wf_TableCell(__('Devices'));
        $tablecells .= wf_TableCell(__('Ports'));
        $tablecells .= wf_TableCell(__('SNMP template'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($allmodels as $io => $eachmodel) {
            $availDevicesCount = (isset($modelsCount[$eachmodel['id']])) ? $modelsCount[$eachmodel['id']] : 0;
            $snmpLabel = '';
            $snmpTemplate = $eachmodel['snmptemplate'];
            if (!empty($snmpTemplate)) {
                if (isset($allSnmpTemplates[$snmpTemplate])) {
                    $snmpLabel .= $allSnmpTemplates[$snmpTemplate];
                } else {
                    $snmpLabel .= __('Template') . ' ' . $snmpTemplate . ' - ' . __('Not exists');
                    show_error(__('Template') . ' ' . $snmpTemplate . ' ' . __('for') . ' ' . __('Equipment models') . ' ' . __('ID') . ' [' . $eachmodel['id'] . ']' . ' - ' . __('Not exists'));
                }
            }

            $tablecells = wf_TableCell($eachmodel['id']);
            $tablecells .= wf_TableCell($eachmodel['modelname']);
            $tablecells .= wf_TableCell($availDevicesCount);
            $tablecells .= wf_TableCell($eachmodel['ports']);
            $tablecells .= wf_TableCell($snmpLabel);
            $controls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $eachmodel['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $controls .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $eachmodel['id'], web_edit_icon());
            $tablecells .= wf_TableCell($controls);
            $tablerows .= wf_TableRow($tablecells, 'row5');
        }
        $result .= wf_TableBody($tablerows, '100%', '0', 'sortable');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }

    return ($result);
}


}