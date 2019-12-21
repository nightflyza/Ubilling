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
    const URL_ME = '?module=testing';

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
    public function renderScriptCreateForm() {
        $result = '';
        if (!empty($this->allModels)) {
            $inputs = '';
            $modelsTmp = array();
            foreach ($this->allModels as $io => $each) {
                if (!isset($this->allScripts[$each['id']])) {
                    $modelsTmp[$each['id']] = $each['modelname'];
                }
            }


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

}
