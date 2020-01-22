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
     * Contains filtered devices which need to be backuped as switchid=>data
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
     * Contains all available previously stored device configs from db as id=>recordData
     *
     * @var array
     */
    protected $allConfigs = array();

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
     * Envy archive data model placeholder
     *
     * @var object
     */
    protected $archive = '';

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
    const DL_PREFIX = 'ENVYCONFIG_';
    const ROUTE_SCRIPTS = 'scriptsmgr';
    const ROUTE_DEVICES = 'devicesmgr';
    const ROUTE_DIFF = 'diff';
    const ROUTE_ARCHVIEW = 'viewarchiveid';
    const ROUTE_ARCHALL = 'archiveall';
    const ROUTE_ARCHIVE_AJ = 'ajarchive';

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
        $this->initArchive();
        $this->loadArchive();
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
     * Creates new archive data model instance
     * 
     * @return void
     */
    protected function initArchive() {
        $this->archive = new NyanORM('envydata');
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
     * Loads existing archive records from database
     * 
     * @return void
     */
    protected function loadArchive() {
        $this->archive->selectable(array('id', 'date', 'switchid'));
        $this->allConfigs = $this->archive->getAll('id');
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
        if (ubRouting::checkGet(self::ROUTE_SCRIPTS) OR ubRouting::checkGet(self::ROUTE_DEVICES) OR ubRouting::checkGet(self::ROUTE_DIFF)) {
            $result .= wf_BackLink(self::URL_ME) . ' ';
        } else {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SCRIPTS . '=true', wf_img('skins/switch_models.png') . ' ' . __('Scripts'), false, 'ubButton') . ' ';
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_DEVICES . '=true', wf_img('skins/ymaps/switchdir.png') . ' ' . __('Devices'), false, 'ubButton') . ' ';
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_DIFF . '=true', wf_img('skins/diff_icon.png') . ' ' . __('Changes'), false, 'ubButton') . ' ';
        }

        if (ubRouting::checkGet(self::ROUTE_SCRIPTS)) {
            $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new script'), __('Create new script'), $this->renderScriptCreateForm(), 'ubButton') . ' ';
        }

        if (ubRouting::checkGet(self::ROUTE_DEVICES)) {
            $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new device'), __('Create new device'), $this->renderDeviceCreateForm(), 'ubButton') . ' ';
            $saveAllNotice = $this->messages->getEditAlert() . ' ' . __('Store all devices configs into archive') . '?';
            $result .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_ARCHALL . '=true', wf_img('skins/icon_restoredb.png') . ' ' . __('Store all'), $saveAllNotice, '', 'ubButton');
        }

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
                $inputs .= wf_CheckInput('newdeviceactive', __('Active'), true, true);
                $inputs .= wf_delimiter(0);
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
     * Renders device editing form for existing envy-device
     * 
     * @param int $switchId
     * 
     * @return string
     */
    protected function renderDeviceEditForm($switchId) {
        $result = '';
        $deviceId = ubRouting::filters($switchId, 'int');
        if (isset($this->allDevices[$switchId])) {
            $deviceData = $this->allDevices[$switchId];
            $inputs = '';
            $inputs .= wf_HiddenInput('editdeviceid', $deviceData['id']);
            $inputs .= wf_HiddenInput('editdeviceswitchid', $deviceData['switchid']);
            $inputs .= wf_TextInput('editdevicelogin', __('Login'), $deviceData['login'], true, '');
            $inputs .= wf_TextInput('editdevicepassword', __('Password'), $deviceData['password'], true, '');
            $inputs .= wf_TextInput('editdeviceenablepassword', __('Enable password'), $deviceData['enablepassword'], true, '');
            $inputs .= wf_TextInput('editdevicecustom1', __('Custom field'), $deviceData['custom1'], true, '');
            $inputs .= wf_CheckInput('editdeviceactive', __('Active'), true, $deviceData['active']);
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NODEVICE', 'error');
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
            $active = (ubRouting::post('newdeviceactive')) ? 1 : 0;
            $login = ubRouting::post('newdevicelogin', 'mres');
            $password = ubRouting::post('newdevicepassword', 'mres');
            $enablepassword = ubRouting::post('newdeviceenablepassword', 'mres');
            $custom1 = ubRouting::post('newdevicecustom1', 'mres');
            if (!empty($switchId)) {
                if (!isset($this->allDevices[$switchId])) {
                    if (isset($this->allSwitches[$switchId])) {
                        $this->devices->data('switchid', $switchId);
                        $this->devices->data('active', $active);
                        $this->devices->data('login', $login);
                        $this->devices->data('password', $password);
                        $this->devices->data('enablepassword', $enablepassword);
                        $this->devices->data('custom1', $custom1);
                        $this->devices->create();
                        $newId = $this->devices->getLastId();
                        log_register('ENVY CREATE DEVICE [' . $newId . '] SWITCHID [' . $switchId . ']');
                    } else {
                        $result .= __('Something went wrong') . ': EX_WRONGSWITCHID [' . $switchId . ']';
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
     * Catches device editing request and saves changes in database
     * 
     * @return void/string on error
     */
    public function saveDevice() {
        if (ubRouting::checkPost(array('editdeviceswitchid', 'editdeviceid'))) {
            $deviceId = ubRouting::post('editdeviceid', 'int');
            $switchId = ubRouting::post('editdeviceswitchid', 'int');
            $active = (ubRouting::post('editdeviceactive')) ? 1 : 0;
            $login = ubRouting::post('editdevicelogin', 'mres');
            $password = ubRouting::post('editdevicepassword', 'mres');
            $enablepassword = ubRouting::post('editdeviceenablepassword', 'mres');
            $custom1 = ubRouting::post('editdevicecustom1', 'mres');
            if (!empty($switchId)) {
                if (isset($this->allDevices[$switchId])) {
                    if (isset($this->allSwitches[$switchId])) {
                        $this->devices->where('id', '=', $deviceId);
                        $this->devices->data('active', $active);
                        $this->devices->data('login', $login);
                        $this->devices->data('password', $password);
                        $this->devices->data('enablepassword', $enablepassword);
                        $this->devices->data('custom1', $custom1);
                        $this->devices->save();
                        log_register('ENVY EDIT DEVICE [' . $deviceId . '] SWITCHID [' . $switchId . ']');
                    } else {
                        $result .= __('Something went wrong') . ': EX_WRONGSWITCHID [' . $switchId . ']';
                    }
                } else {
                    $result .= __('Something went wrong') . ': EX_DEVICENOTEXISTS';
                }
            } else {
                $result .= __('Something went wrong') . ': EX_EMPTYSWITCHID';
            }
        }
    }

    /**
     * Deletes existing envy device from database
     * 
     * @param int $switchId
     * 
     * @return void/string on error
     */
    public function deleteDevice($switchId) {
        $result = '';
        $switchId = ubRouting::filters($switchId, 'int');
        if (!empty($switchId)) {
            if (isset($this->allDevices[$switchId])) {
                $devData = $this->allDevices[$switchId];
                $this->devices->where('switchid', '=', $switchId);
                $this->devices->delete();
                log_register('ENVY DELETE DEVICE [' . $devData['id'] . '] SWITCHID [' . $switchId . ']');
            } else {
                $result .= __('Something went wrong') . ': EX_WRONGSWITCHID [' . $switchId . ']';
            }
        } else {
            $result .= __('Something went wrong') . ': EX_EMPTYSWITCHID';
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
            $countActive = 0;
            $countInactive = 0;
            $allModelNames = array();
            if (!empty($this->allModels)) {
                foreach ($this->allModels as $io => $each) {
                    $allModelNames[$each['id']] = $each['modelname'];
                }
            }


            $cells = wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Switch'));
            $cells .= wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Active'));
            $cells .= wf_TableCell(__('Login'));
            $cells .= wf_TableCell(__('Password'));
            $cells .= wf_TableCell(__('Enable password'));
            $cells .= wf_TableCell(__('Custom field'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allDevices as $io => $each) {
                $switchData = $this->allSwitches[$each['switchid']];
                $cells = wf_TableCell($switchData['ip']);
                $cells .= wf_TableCell($switchData['location']);
                $cells .= wf_TableCell($allModelNames[$switchData['modelid']]);
                $cells .= wf_TableCell(web_bool_led($each['active']));
                $cells .= wf_TableCell($each['login']);
                $cells .= wf_TableCell($each['password']);
                $cells .= wf_TableCell($each['enablepassword']);
                $cells .= wf_TableCell($each['custom1']);
                $devControls = '';
                $devControls .= wf_JSAlert(self::URL_ME . '&deletedevice=' . $each['switchid'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $devControls .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $switchData['ip'], $this->renderDeviceEditForm($each['switchid'])) . ' ';
                $devControls .= wf_Link(self::URL_ME . '&previewdevice=' . $each['switchid'], web_icon_search('Preview')) . ' ';
                $storeAlert = $this->messages->getEditAlert() . ' ' . __('Backup device configuration to archive') . '?';
                $devControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DEVICES . '&=true' . '&storedevice=' . $each['switchid'], wf_img('skins/icon_restoredb.png', __('Backup device configuration to archive')), $storeAlert) . ' ';
                $devControls .= wf_Link('?module=switches&edit=' . $each['switchid'], wf_img('skins/menuicons/switches.png', __('Go to switch')));
                $cells .= wf_TableCell($devControls);

                $rows .= wf_TableRow($cells, 'row5');

                if ($each['active']) {
                    $countActive++;
                } else {
                    $countInactive++;
                }
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            $countersLabel = __('Total') . ': ' . ($countActive + $countInactive) . ' ' . __('Active') . ': ' . $countActive . ' ' . __('Inactive') . ': ' . $countInactive;
            $result .= wf_tag('br') . wf_tag('b', false) . $countersLabel . wf_tag('b', true);
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
                    $deviceData = $this->allDevices[$switchId];
                    $macroLogin = (!empty($deviceData['login'])) ? $deviceData['login'] : 'empty_login';
                    $macroPass = (!empty($deviceData['password'])) ? $deviceData['password'] : 'empty_password';
                    $macroEnPass = (!empty($deviceData['enablepassword'])) ? $deviceData['enablepassword'] : 'empty_enablepassword';
                    $macroCust1 = (!empty($deviceData['custom1'])) ? $deviceData['custom1'] : 'empty_custom1';
                    //some macro replacing here
                    $result = str_replace('{IP}', $this->allSwitches[$switchId]['ip'], $result);
                    $result = str_replace('{LOGIN}', $macroLogin, $result);
                    $result = str_replace('{PASSWORD}', $macroPass, $result);
                    $result = str_replace('{ENABLEPASSWORD}', $macroEnPass, $result);
                    $result = str_replace('{CUSTOM1}', $macroCust1, $result);
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

    /**
     * Renders script results preview
     * 
     * @param string $data
     * 
     * @return string
     */
    public function previewScriptsResult($data) {
        $result = '';
        if (!empty($data)) {
            $inputs = wf_tag('textarea', false, 'fileeditorarea', 'name="envypreview" cols="145" rows="30"');
            $inputs .= $data;
            $inputs .= wf_tag('textarea', true);
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Empty reply received'), 'warning');
        }
        return($result);
    }

    /**
     * Renders previously envy data arhive container
     * 
     * @return string
     */
    public function renderArchive() {
        $result = '';
        $columns = array('Date', 'IP', 'Device', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_ARCHIVE_AJ . '=true', false, __('Config'), 100, $opts);
        return($result);
    }

    /**
     * Renders background JSON data for existing configs archive
     * 
     * @return void
     */
    public function getAjArchive() {
        $json = new wf_JqDtHelper();

        if (!empty($this->allConfigs)) {
            foreach ($this->allConfigs as $io => $each) {
                @$switchData = $this->allSwitches[$each['switchid']];
                $data[] = $each['date'];
                $data[] = @$switchData['ip'];
                $data[] = @$switchData['location'];
                $archControls = '';
                $archControls .= wf_JSAlert(self::URL_ME . '&deletearchiveid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert() . ' ' . $each['date']) . ' ';
                $archControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_ARCHVIEW . '=' . $each['id'], web_icon_search('Config')) . ' ';
                $archControls .= wf_Link(self::URL_ME . '&downloadarchiveid=' . $each['id'], web_icon_download());

                $data[] = $archControls;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Saves device config data into archive
     * 
     * @param int $switchId
     * @param string $data
     * 
     * @return void/string on error
     */
    public function storeArchiveData($switchId, $data) {
        $result = '';
        $switchId = ubRouting::filters($switchId, 'int');
        if (!empty($switchId)) {
            if (isset($this->allDevices[$switchId])) {
                $curdate = curdatetime();
                $data = ubRouting::filters($data, 'mres');
                $this->archive->data('switchid', $switchId);
                $this->archive->data('date', $curdate);
                $this->archive->data('config', $data);
                $this->archive->create();
                log_register('ENVY STORE ARCHIVE SWITCHID [' . $switchId . ']');
            } else {
                $result .= __('Something went wrong') . ': EX_WRONGSWITCHID [' . $switchId . ']';
            }
        } else {
            $result .= __('Something went wrong') . ': EX_EMPTYSWITCHID';
        }
        return($result);
    }

    /**
     * Deletes existing archive record from daabase
     * 
     * @param int $recordId
     * 
     * @return void/string on error
     */
    public function deleteArchiveRecord($recordId) {
        $result = '';
        $recordId = ubRouting::filters($recordId, 'int');
        if (!empty($recordId)) {
            if (isset($this->allConfigs[$recordId])) {
                $recordData = $this->allConfigs[$recordId];
                $this->archive->where('id', '=', $recordId);
                $this->archive->delete();
                log_register('ENVY DELETE ARCHIVE RECORD [' . $recordId . '] SWITCHID [' . $recordData['switchid'] . '] DATE `' . $recordData['date'] . '`');
            } else {
                $result .= __('Something went wrong') . ': EX_WRONGRECORDID [' . $recordId . ']';
            }
        } else {
            $result .= __('Something went wrong') . ': EX_EMPTYRECORDID';
        }
        return($result);
    }

    /**
     * Returns device config saved in some archive record
     * 
     * @param int $recordId
     * 
     * @return string
     */
    public function renderArchiveRecordConfig($recordId) {
        $result = '';
        if (isset($this->allConfigs[$recordId])) {
            $this->archive->selectable('config');
            $this->archive->where('id', '=', $recordId);
            $rawConfig = $this->archive->getAll();
            $result = $rawConfig[0]['config'];
        }
        return($result);
    }

    /**
     * Downloads record file
     * 
     * @param int $recordId
     * 
     * @return void
     */
    public function downloadArchiveRecordConfig($recordId) {
        if (isset($this->allConfigs[$recordId])) {
            $recordData = $this->allConfigs[$recordId];
            $switchId = $recordData['switchid'];
            $switchIp = (isset($this->allSwitches[$switchId])) ? $this->allSwitches[$switchId]['ip'] : '';
            $this->archive->selectable('config');
            $this->archive->where('id', '=', $recordId);
            $rawConfig = $this->archive->getAll();
            $configContent = $rawConfig[0]['config'];
            $tmpFilePath = self::TMP_PATH . self::DL_PREFIX . $recordData['date'] . '_' . $switchId . '_' . $switchIp . '.txt';
            file_put_contents($tmpFilePath, $configContent);
            zb_DownloadFile($tmpFilePath);
        }
    }

    /**
     * Stores all available envy-devices configs into archive
     * 
     * @return void
     */
    public function storeArchiveAllDevices() {
        if (!empty($this->allScripts)) {
            if (!empty($this->allDevices)) {
                foreach ($this->allDevices as $io => $each) {
                    if ($each['active']) {
                        $this->storeArchiveData($each['switchid'], $this->runDeviceScript($each['switchid']));
                    }
                }
            }
        }
    }

    /**
     * Renders diff search form
     * 
     * @return string
     */
    public function renderDiffForm() {
        $result = '';
        if (!empty($this->allConfigs)) {
            $confTmp = array();
            foreach ($this->allConfigs as $io => $each) {
                if (isset($this->allSwitches[$each['switchid']])) {
                    $switchData = $this->allSwitches[$each['switchid']];
                    $swichLabel = $switchData['ip'] . ' - ' . $switchData['location'];
                } else {
                    $swichLabel = $each['switchid'] . ' - ' . __('Unknown');
                }

                $confTmp[$each['id']] = $each['date'] . ' ' . $swichLabel;
            }

            $currDiffOne = (ubRouting::checkPost('diffone')) ? ubRouting::post('diffone') : '';
            $currDiffTwo = (ubRouting::checkPost('difftwo')) ? ubRouting::post('difftwo') : '';

            $inputs = wf_HiddenInput('rundiff', 'true');
            $inputs .= __('Compare') . ' ';
            $inputs .= wf_Selector('diffone', $confTmp, '', $currDiffOne, false) . ' ';
            $inputs .= __('and') . ' ';
            $inputs .= wf_Selector('difftwo', $confTmp, '', $currDiffTwo, false) . ' ';
            $inputs .= wf_Submit(__('Show'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Compares some two existing configs from archive and displays diff results
     * 
     * @param int $configIdOne
     * @param int $configIdTwo
     * 
     * @return string
     */
    public function renderDiff($configIdOne, $configIdTwo) {
        $result = '';
        $configIdOne = ubRouting::filters($configIdOne, 'int');
        $configIdTwo = ubRouting::filters($configIdTwo, 'int');
        if (!empty($configIdOne) AND ! empty($configIdTwo)) {
            //same config check
            if ($configIdOne != $configIdTwo) {
                if (isset($this->allConfigs[$configIdOne]) AND isset($this->allConfigs[$configIdTwo])) {
                    //gettin both configs from database
                    $this->archive->selectable('config');
                    $this->archive->where('id', '=', $configIdOne);
                    $rawConfig = $this->archive->getAll();
                    $configOne = $rawConfig[0]['config'];
                    $this->archive->where('id', '=', $configIdTwo);
                    $rawConfig = $this->archive->getAll();
                    $configTwo = $rawConfig[0]['config'];
                    if ($configOne == $configTwo) {
                        $result .= $this->messages->getStyledMessage(__('No difference between this two configurations'), 'success');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something is different in this two configurations'), 'warning');
                    }
                    $result .= wf_delimiter(0);
                    $result .= Diff::toTable(Diff::compare($configOne, $configTwo, false));
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_ARCHIVEID', 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Same configs selected'), 'info');
            }
        }
        return($result);
    }

}
