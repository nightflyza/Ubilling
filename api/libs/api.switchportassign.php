<?php

/**
 * Users to switch port assign basic implementation
 */
class SwitchPortAssign {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Assigns database abstraction layer
     * 
     * @var object
     */
    protected $assignsDb = '';

    /**
     * Contains all available switches data
     * 
     * @var array
     */
    protected $allSwitches = array();

    /**
     * Contains all available switchport assigns as id=>id/login/switchid/port
     * 
     * @var array
     */
    protected $allAssigns = array();

    /**
     * Some predefined stuff
     */
    const TABLE_ASSIGNS = 'switchportassign';
    const PROUTE_LOGIN = 'swassignlogin';
    const PROUTE_SWITCH = 'swassignswid';
    const PROUTE_PORT = 'swassignswport';
    const PROUTE_DELETE = 'swassigndelete';

    public function __construct() {
        $this->loadAlter();
        $this->initDb();
        $this->loadSwitches();
        $this->loadAssigns();
    }

    /**
     * Loads system alter config into protected prop
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
     * Inits assigns database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->assignsDb = new NyanORM(self::TABLE_ASSIGNS);
    }

    /**
     * 
     * @return void
     */
    protected function loadSwitches() {
        $this->allSwitches = $this->getAllSwitches();
    }

    /**
     * Returns coinfurable switches array
     * 
     * @return array
     */
    protected function getAllSwitches() {
        $result = array();
        //optional switches arrange
        switch ($this->altCfg['SWITCHPORT_IN_PROFILE']) {
            //switch selector arranged by id (default)
            case 1:
                $result = zb_SwitchesGetAll();
                break;
            //switch selector arranged by location
            case 2:
                $result = zb_SwitchesGetAll('ORDER BY `location` ASC');
                break;
            //switch selector arranged by ip
            case 3:
                $result = zb_SwitchesGetAll('ORDER BY `ip` ASC');
                break;
            //switch selector arranged by id (default)
            case 4:
                $result = zb_SwitchesGetAll();
                break;
            default :
                $result = zb_SwitchesGetAll();
                break;
        }
        return($result);
    }

    /**
     * Loads all assigns data into protected prop
     * 
     * @return void
     */
    protected function loadAssigns() {
        $this->allAssigns = $this->assignsDb->getAll('id');
    }

    /**
     * Returns some user assign data if it exists
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getAssignData($login) {
        $result = array();
        if (!empty($this->allAssigns)) {
            foreach ($this->allAssigns as $io => $each) {
                if ($each['login'] == $login) {
                    $result = $each;
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Checks is switch-port pair free or not
     * 
     * @param int $switchId
     * @param int $port
     * 
     * @return bool
     */
    protected function isPortFree($switchId, $port) {
        $result = true;
        if (!empty($this->allSwitches)) {
            if ((!empty($switchId)) AND (!empty($port))) {
                foreach ($this->allAssigns as $io => $each) {
                    if ($each['switchid'] == $switchId AND $each['port'] == $port) {
                        $result = false;
                    }
                }
            }
        } else {
            $result = false;
        }
        return($result);
    }

    /**
     * Returns all other users with assigned same port on the same switch
     * 
     * @param string $login
     * @param int $currentSwitchPort
     * @param int $currentSwitchId
     * 
     * @return array
     */
    protected function getSameUsers($login, $currentSwitchPort, $currentSwitchId) {
        $result = array();
        $currentSwitchId = ubRouting::filters($currentSwitchId, 'int');
        $currentSwitchPort = ubRouting::filters($currentSwitchPort, 'int');
        if (!empty($login) AND !empty($currentSwitchId) AND !empty($currentSwitchPort)) {
            if (!empty($this->allAssigns)) {
                foreach ($this->allAssigns as $io => $each) {
                    if ($each['switchid'] == $currentSwitchId AND $each['port'] == $currentSwitchPort) {
                        if ($each['login'] != $login) {
                            $result[$each['login']] = $each;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Deletes existing assign database record by user login
     * 
     * @param string $login
     * 
     * @return void
     */
    public function delete($login) {
        $login = ubRouting::filters($login, 'mres');
        $this->assignsDb->where('login', '=', $login);
        $this->assignsDb->delete();
        log_register('SWITCHPORT DELETE (' . $login . ')');
        // Rebuild DHCP configuration if switch used on option82
        if ($this->altCfg['OPT82_ENABLED']) {
            $loginNetType = multinet_get_network_params_by_login($login);
            if (!empty($loginNetType) and $loginNetType['nettype'] = 'dhcp82') {
                multinet_rebuild_all_handlers();
            }
        }
    }

    /**
     * Creates new or updates existing assign record
     * 
     * @param string $login
     * @param int $switchId
     * @param int $port
     * 
     * @return void/string on error
     */
    public function save($login, $switchId, $port) {
        $result = '';
        $newAssignLoging = ubRouting::filters($login, 'mres');
        $newSwitchId = ubRouting::filters($switchId, 'int');
        $newPort = ubRouting::filters($port, 'int');

        if ($this->isPortFree($newSwitchId, $newPort)) {
            $currenAssignData = $this->getAssignData($login);
            //creating new assign
            if (empty($currenAssignData)) {
                $this->assignsDb->data('login', $login);
                $this->assignsDb->data('switchid', $newSwitchId);
                $this->assignsDb->data('port', $newPort);
                $this->assignsDb->create();
            } else {
                //updating existing record
                $this->assignsDb->where('login', '=', $login);
                $this->assignsDb->data('switchid', $newSwitchId);
                $this->assignsDb->data('port', $newPort);
                $this->assignsDb->save();
            }
            log_register('SWITCHPORT CHANGE (' . $login . ') ON SWITCHID [' . $newSwitchId . '] PORT [' . $newPort . ']');

            // Rebuild DHCP configs if switch used on option82
            if ($this->altCfg['OPT82_ENABLED']) {
                $loginNetType = multinet_get_network_params_by_login($login);
                if (!empty($loginNetType) and $loginNetType['nettype'] = 'dhcp82') {
                    multinet_rebuild_all_handlers();
                }
            }
        } else {
            log_register('SWITCHPORT FAIL (' . $login . ') ON SWITCHID [' . $newSwitchId . '] PORT [' . $newPort . ']');
            $result .= __('Port already assigned for another user');
        }

        return($result);
    }

    /**
     * Switchport modification controller
     * 
     * @param string $returnUrl
     * 
     * @return void
     */
    public function catchChangeRequest($returnUrl = '') {
        //update
        if (ubRouting::checkPost(array(self::PROUTE_LOGIN, self::PROUTE_SWITCH, self::PROUTE_PORT))) {
            $updateResult = $this->save(ubRouting::post(self::PROUTE_LOGIN), ubRouting::post(self::PROUTE_SWITCH), ubRouting::post(self::PROUTE_PORT));
            if (empty($updateResult)) {
                if (!empty($returnUrl)) {
                    ubRouting::nav($returnUrl);
                }
            } else {
                show_error($updateResult);
            }
        }

        //delete
        if (ubRouting::post(self::PROUTE_DELETE)) {
            $assignToDelete = ubRouting::post(self::PROUTE_LOGIN);
            $this->delete($assignToDelete);
            if (!empty($returnUrl)) {
                ubRouting::nav($returnUrl);
            }
        }
    }

    /**
     * Returns users switch port assign form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderEditForm($login) {
        $result = '';
        $login = ubRouting::filters($login, 'mres');

        $switcharr = array();
        $switcharrFull = array();
        $switchswpoll = array();
        $switchgeo = array();
        $sameArr = array();
        $sameUsers = '';
        $currentSwitchPort = '';
        $currentSwitchId = '';
        $assignData = $this->getAssignData($login);

        if (!empty($this->allSwitches)) {
            foreach ($this->allSwitches as $io => $eachswitch) {
                $switcharrFull[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
                if (mb_strlen($eachswitch['location']) > 32) {
                    $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . mb_substr($eachswitch['location'], 0, 32, 'utf-8') . '...';
                } else {
                    $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
                }
                
                if (ispos($eachswitch['desc'], 'SWPOLL')) {
                    $switchswpoll[$eachswitch['id']] = $eachswitch['ip'];
                }

                if (!empty($eachswitch['geo'])) {
                    $switchgeo[$eachswitch['id']] = $eachswitch['geo'];
                }
            }
        }

        if (!empty($assignData)) {
            $currentSwitchPort = $assignData['port'];
            $currentSwitchId = $assignData['switchid'];
            $sameArr = $this->getSameUsers($login, $currentSwitchPort, $currentSwitchId);
        }


        //rendering other users with same switch+port 
        if ((!empty($currentSwitchId)) AND (!empty($currentSwitchPort))) {
            if (!empty($sameArr)) {
                foreach ($sameArr as $ip => $each) {
                    $sameUsers .= ' ' . wf_Link(UserProfile::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                }
            }
        }

        //control form construct
        $formStyle = 'glamour';
        $inputs = wf_HiddenInput(self::PROUTE_LOGIN, $login);
        if ($this->altCfg['SWITCHPORT_IN_PROFILE'] != 4) {
            $inputs .= wf_Selector(self::PROUTE_SWITCH, $switcharr, __('Switch'), $currentSwitchId, true);
        } else {
            $inputs .= wf_JuiComboBox(self::PROUTE_SWITCH, $switcharr, __('Switch'), $currentSwitchId, true);
            $formStyle = 'floatpanelswide';
        }
        $inputs .= wf_TextInput(self::PROUTE_PORT, __('Port'), $currentSwitchPort, false, 2, 'digits');
        $inputs .= wf_CheckInput(self::PROUTE_DELETE, __('Delete'), true, false);
        $inputs .= wf_Submit('Save');
        $controlForm = wf_Form('', "POST", $inputs, $formStyle);
        //form end

        $switchAssignController = wf_modalAuto(web_edit_icon(), __('Switch port assign'), $controlForm);

        //switch location and polling controls
        $switchLocators = '';

        if (!empty($currentSwitchId)) {
            $switchProfileIcon = wf_img_sized('skins/menuicons/switches.png', __('Switch'), 10, 10);
            $switchLocators .= wf_Link('?module=switches&edit=' . $currentSwitchId, $switchProfileIcon, false, '');
        }

        if (isset($switchswpoll[$currentSwitchId])) {
            $snmpSwitchLocatorIcon = wf_img_sized('skins/snmp.png', __('SNMP query'), 10, 10);
            $switchLocators .= wf_Link('?module=switchpoller&switchid=' . $currentSwitchId, $snmpSwitchLocatorIcon, false, '');
        }

        if (isset($switchgeo[$currentSwitchId])) {
            $geoSwitchLocatorIcon = wf_img_sized('skins/icon_search_small.gif', __('Find on map'), 10, 10);
            $switchLocators .= wf_Link('?module=switchmap&finddevice=' . $switchgeo[$currentSwitchId], $geoSwitchLocatorIcon, false, '');
        }

        $cells = wf_TableCell(__('Switch'), '30%', 'row2');
        $cells .= wf_TableCell(@$switcharrFull[$currentSwitchId] . ' ' . $switchLocators);
        $rows = wf_TableRow($cells, 'row3');
        $cells = wf_TableCell(__('Port'), '30%', 'row2');
        $cells .= wf_TableCell($currentSwitchPort);
        $rows .= wf_TableRow($cells, 'row3');
        $cells = wf_TableCell(__('Change'), '30%', 'row2');
        $cells .= wf_TableCell($switchAssignController . ' ' . $sameUsers);
        $rows .= wf_TableRow($cells, 'row3');

        $result = wf_TableBody($rows, '100%', '0');

        return($result);
    }
}
