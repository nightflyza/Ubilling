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
     * Contain all available switches as switchid=>data
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
    const TMP_PATH = 'exports/';
    const SCRIPT_PREFIX = 'ENVYSCRIPT_';

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
        $switchesTmp = zb_SwitchesGetAll();
        if (!empty($switchesTmp)) {
            foreach ($switchesTmp as $io => $each) {
                $this->allSwitches[$each['id']] = $each;
            }
        }
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
     * Saves changes in envy script
     * 
     * @return void/string on error
     */
    public function saveScript() {
        $result = '';
        if (ubRouting::checkPost(array('editscriptid', 'editscriptmodel'))) {
            $scriptId = ubRouting::post('editscriptid', 'int');
            $modelId = ubRouting::post('editscriptmodel', 'int');
            $scriptData = ubRouting::post('editscriptdata', 'mres');
            if (isset($this->allScripts[$modelId])) {
                $this->scripts->where('id', '=', $scriptId);
                $this->scripts->data('data', $scriptData);
                $this->scripts->save();
                log_register('ENVY CHANGE SCRIPT [' . $scriptId . '] MODEL [' . $modelId . ']');
            } else {
                $result .= __('Something went wrong') . ': EX_WRONGMODELID';
            }
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

            $cells = wf_TableCell(__('Equipment models'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allScripts as $io => $each) {

                $cells = wf_TableCell(@$allModelNames[$each['modelid']]);
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
        $switchesTmp = array();
        if (!empty($this->allSwitches)) {
            if (!empty($this->allScripts)) {
                foreach ($this->allSwitches as $io => $each) {
                    if (!isset($this->allDevices[$each['id']])) {
                        if (isset($this->allScripts[$each['modelid']])) {
                            $switchesTmp[$each['id']] = $each['ip'] . ' - ' . $each['location'];
                        }
                    }
                }
                $inputs = '';
                $inputs .= wf_Selector('newdeviceswitchid', $switchesTmp, __('Switch'), '', true);
                $inputs .= wf_TextInput('newdevicelogin', __('Login'), '', true, '');
                $inputs .= wf_TextInput('newdevicepassword', __('Password'), '', true, '');
                $inputs .= wf_TextInput('newdeviceenablepassword', __('Enable password'), '', true, '');
                $inputs .= wf_TextInput('newdevicecustom1', __('Custom field'), '', true, '');
                $inputs .= wf_Submit(__('Create'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Available envy scripts') . ': ' . __('No'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Available switches') . ': ' . __('No'), 'error');
        }
        return($result);
    }

    /**
     * Creates new device in database
     * 
     * @return string
     */
    public function createDevice() {
        $result = '';
        if (ubRouting::checkPost(array('newdeviceswitchid'))) {
            $switchId = ubRouting::post('newdeviceswitchid', 'int');
            $login = ubRouting::post('newdevicelogin', 'mres');
            $password = ubRouting::post('newdevicepassword', 'mres');
            $enablepassword = ubRouting::post('newdeviceenablepassword', 'mres');
            $custom1 = ubRouting::post('newdevicecustom1', 'mres');
            if (!empty($switchId)) {
                if (!isset($this->allDevices[$switchId])) {
                    if (isset($this->allSwitches[$switchId])) {
                        $this->devices->data('switchid', $switchId);
                        $this->devices->data('login', $login);
                        $this->devices->data('password', $password);
                        $this->devices->data('enablepassword', $enablepassword);
                        $this->devices->data('custom1', $custom1);
                        $this->devices->create();
                        $newId = $this->devices->getLastId();
                        log_register('ENVY CREATE DEVICE [' . $newId . '] SWITCHID [' . $switchId . ']');
                    } else {
                        $result .= __('Something went wrong') . ': EX_WRONGSWITCHID [' . $switchId . ']';
                        debarr($this->allSwitches);
                    }
                } else {
                    $result .= __('Something went wrong') . ': EX_DEVICEALREADYEXISTS';
                }
            } else {
                $result .= __('Something went wrong') . ': EX_EMPTYSWITCHID';
            }
        }
        return($result);
    }

    /**
     * Renders available envy-devices with some their params
     * 
     * @return string
     */
    public function renderDevicesList() {
        $result = '';
        if (!empty($this->allDevices)) {
            $allModelNames = array();
            if (!empty($this->allModels)) {
                foreach ($this->allModels as $io => $each) {
                    $allModelNames[$each['id']] = $each['modelname'];
                }
            }

            $cells = wf_TableCell(__('Switch'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Login'));
            $cells .= wf_TableCell(__('Password'));
            $cells .= wf_TableCell(__('Enable password'));
            $cells .= wf_TableCell(__('Custom field'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allDevices as $io => $each) {
                $switchData = $this->allSwitches[$each['switchid']];
                $cells = wf_TableCell($switchData['location']);
                $cells .= wf_TableCell($switchData['ip']);
                $cells .= wf_TableCell($allModelNames[$switchData['modelid']]);
                $cells .= wf_TableCell($each['login']);
                $cells .= wf_TableCell($each['password']);
                $cells .= wf_TableCell($each['enablepassword']);
                $cells .= wf_TableCell($each['custom1']);
                $devControls = wf_Link(self::URL_ME . '&previewdevice=' . $each['switchid'], web_icon_search('Preview'));
                $cells .= wf_TableCell($devControls);
                
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('No envy devices available'), 'warning');
        }
        return($result);
    }

    /**
     * Returns deivice polling script with preprocessed macro data
     * 
     * @param int $switchId
     * 
     * @return string
     */
    protected function parseMacro($switchId) {
        $result = '';
        if (isset($this->allDevices[$switchId])) {
            if (isset($this->allSwitches[$switchId])) {
                if (isset($this->allScripts[$this->allSwitches[$switchId]['modelid']])) {
                    $result = $this->allScripts[$this->allSwitches[$switchId]['modelid']]['data'];
                    //some macro replacing here
                    $result = str_replace('{IP}', $this->allSwitches[$switchId]['ip'], $result);
                    $result = str_replace('{LOGIN}', $this->allDevices[$switchId]['login'], $result);
                    $result = str_replace('{PASSWORD}', $this->allDevices[$switchId]['password'], $result);
                    $result = str_replace('{ENABLEPASSWORD}', $this->allDevices[$switchId]['enablepassword'], $result);
                    $result = str_replace('{CUSTOM1}', $this->allDevices[$switchId]['custom1'], $result);
                }
            }
        }
        return($result);
    }

    /**
     * Runs envy script for some envy device and returns script result
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function runDeviceScript($switchId) {
        $result = '';
        if (isset($this->allDevices[$switchId])) {
            $scriptData = $this->parseMacro($switchId);
            if (!empty($scriptData)) {
                $filePath = self::TMP_PATH . self::SCRIPT_PREFIX . $switchId;
                file_put_contents($filePath, $scriptData);
                $result .= shell_exec($this->billCfg['EXPECT_PATH'] . ' ' . $filePath);
            }
        }
        return($result);
    }

}
