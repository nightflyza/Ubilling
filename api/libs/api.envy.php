<?php

class Envy {

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Contains all available devices models
     *
     * @var array
     */
    protected $allModels = array();

    /**
     * Contains filtered devices which need to be provisioned as switchid=>data
     *
     * @var array
     */
    protected $allDevices = array();

    /**
     * Contains available envy-scripts as modelid=>data
     *
     * @var array
     */
    protected $allScripts = array();

    /**
     * Contain all available switches
     *
     * @var arrays
     */
    protected $allSwitches = array();

    /**
     * Envy devices data model placeholder
     *
     * @var object
     */
    protected $devices = '';

    /**
     * Envy scripts data model placeholder
     *
     * @var object
     */
    protected $scripts = '';

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some other required consts for routing etc
     */
    const URL_ME = '?module=envy';

    /**
     * Creates new envy sin instance
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->loadDeviceModels();
        $this->loadSwitches();
        $this->initScrips();
        $this->loadScripts();
        $this->initDevices();
        $this->loadDevices();
    }

    /**
     * Loads all required configs into protected props
     * 
     * @global object $ubillingConfig
     * 
     * @return
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Creates new message helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Creates new devices data model instance
     * 
     * @return void
     */
    protected function initDevices() {
        $this->devices = new NyanORM('envydevices');
    }

    /**
     * Creates new scrips data model instance
     * 
     * @return void
     */
    protected function initScrips() {
        $this->scripts = new NyanORM('envyscripts');
    }

    /**
     * Loads all available envy devices from database for further usage
     * 
     * @return void
     */
    protected function loadDevices() {
        $this->allDevices = $this->devices->getAll('switchid');
    }

    /**
     * Loads all available envy scripts from database for further usage
     * 
     * @return void
     */
    protected function loadScripts() {
        $this->allScripts = $this->scripts->getAll('modelid');
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadDeviceModels() {
        $this->allModels = zb_SwitchModelsGetAll();
    }

    /**
     * Loads all existing swithes directory from database into protected prop
     * 
     * @return void
     */
    protected function loadSwitches() {
        $this->allSwitches = zb_SwitchesGetAll();
    }

    /**
     * Renders envy-script creation form 
     * 
     * @return string
     */
    protected function renderScriptCreateForm() {
        $result = '';
        if (!empty($this->allModels)) {
            $inputs = '';
            $modelsTmp = array();
            foreach ($this->allModels as $io => $each) {
                if (!isset($this->allScripts[$each['id']])) {
                    $modelsTmp[$each['id']] = $each['modelname'];
                }
            }
            /**
             * I am the way and the truth and the life. No one comes to the Father except through me
             */
            $inputs .= wf_Selector('newscriptmodel', $modelsTmp, __('Model'), '', true);
            $inputs .= __('Script') . wf_tag('br');
            $inputs .= wf_TextArea('newscriptdata', '', '', true, '60x20');
            $inputs .= wf_Submit(__('Create'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            show_error(__('Available switch models') . ': ' . __('No'));
        }
        return($result);
    }

    /**
     * Renders envy-script editing form 
     * 
     * @return string
     */
    protected function renderScriptEditForm($modelId) {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        if (isset($this->allScripts[$modelId])) {
            $scriptData = $this->allScripts[$modelId];
            $inputs = '';
            $inputs .= wf_HiddenInput('editscriptid', $scriptData['id']);
            $inputs .= wf_HiddenInput('editscriptmodel', $scriptData['modelid'], __('Model'), '', true);
            $inputs .= __('Script') . wf_tag('br');
            $inputs .= wf_TextArea('editscriptdata', '', $scriptData['data'], true, '60x20');
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Creates new envy script preset in database
     * 
     * @param int $modelId
     * @param string $scriptData
     * 
     * @return void/string on error
     */
    public function createScript($modelId, $scriptData) {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        $scriptData = ubRouting::filters($scriptData, 'mres');
        if (!empty($modelId)) {
            if (!isset($this->allScripts[$modelId])) {
                $this->scripts->data('modelid', $modelId);
                $this->scripts->data('data', $scriptData);
                $this->scripts->create();
                $newId = $this->scripts->getLastId();

                log_register('ENVY CREATE SCRIPT [' . $newId . '] MODEL [' . $modelId . ']');
            } else {
                $result .= __('Something went wrong') . ': EX_ALREADY_EXISTS';
            }
        } else {
            $result .= __('Something went wrong') . ': EX_NOMODELID';
        }
        return($result);
    }

    /**
     * Deletes existing envy script from database
     * 
     * @param int $modelId
     * 
     * @return void/string on result
     */
    public function deleteScript($modelId) {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        if (!empty($modelId)) {
            if (isset($this->allScripts[$modelId])) {
                $scriptData = $this->allScripts[$modelId];
                $this->scripts->where('modelid', '=', $modelId);
                $this->scripts->delete();
                log_register('ENVY DELETE SCRIPT [' . $scriptData['id'] . '] MODEL [' . $modelId . ']');
            } else {
                $result .= __('Something went wrong') . ': EX_WRONGMODELID';
            }
        } else {
            $result .= __('Something went wrong') . ': EX_NOMODELID';
        }
        return($result);
    }

    /**
     * Renders available envy scripts and some controls
     * 
     * @return string
     */
    public function renderScriptsList() {
        $result = '';
        $allModelNames = array();
        if (!empty($this->allModels)) {
            foreach ($this->allModels as $io => $each) {
                $allModelNames[$each['id']] = $each['modelname'];
            }
        }
        if (!empty($this->allScripts)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Equipment models'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allScripts as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell(@$allModelNames[$each['modelid']]);
                $scriptControls = '';
                $scriptControls .= wf_JSAlert(self::URL_ME . '&deletescript=' . $each['modelid'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $scriptControls .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . @$allModelNames[$each['modelid']], $this->renderScriptEditForm($each['modelid']));
                $cells .= wf_TableCell($scriptControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders default controls panel for module
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new script'), __('Create new script'), $this->renderScriptCreateForm(), 'ubButton');
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new device'), __('Create new device'), $this->renderDeviceCreateForm(), 'ubButton');
        return($result);
    }

    /**
     * Renders new device creation form
     * 
     * @return string
     */
    public function renderDeviceCreateForm() {
        $result = '';
        //TODO
        return($result);
    }

}
