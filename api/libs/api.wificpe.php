<?php

/**
 * Client side wireless equipment management subsystem
 */
class WifiCPE {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available APs as id=>APdata
     *
     * @var array
     */
    public $allAP = array();

    /**
     * Contains available AP SSIDs if exists as id=>ssid
     *
     * @var array
     */
    protected $allSSids = array();

    /**
     * Contains all available devices models as modelid=>name
     *
     * @var array
     */
    protected $deviceModels = array();

    /**
     * Contains all available CPEs as id=>CPEdata
     *
     * @var array
     */
    public $allCPE = array();

    /**
     * Contains all available user to CPE assigns as id=>assignData
     *
     * @var array
     */
    protected $allAssigns = array();

    /**
     * Contains array of all existing users data as login=>userData
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Is MTSIGMON enbaled?
     *
     * @var bool
     */
    protected $SigmonEnabled = false;

    /**
     * Placeholder for UbillingConfig object instance
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Sorting order of APs in lists and dropdowns
     * Possible values: id, ip, location
     *
     * @var string
     */
    protected $apSortOrder = "id";

    /**
     * Base module URL
     */
    const URL_ME = '?module=wcpe';

    /**
     * MTSIGMON module URL
     */
    const URL_SIGMON = '?module=mtsigmon';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadConfigs();
        $this->initMessages();
        $this->loadDeviceModels();
        $this->loadAps();
        $this->loadCPEs();
        $this->loadAssigns();

        $this->SigmonEnabled = $this->ubConfig->getAlterParam('MTSIGMON_ENABLED');
        $this->apSortOrder = ($this->ubConfig->getAlterParam('SIGMON_WCPE_AP_LIST_SORT')) ? $this->ubConfig->getAlterParam('SIGMON_WCPE_AP_LIST_SORT') : 'id';
    }

    /**
     * Loads system alter config to protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        $this->altCfg = $this->ubConfig->getAlter();
    }

    /**
     * Initalizes system message helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all available AP devices from switches directory
     * 
     * @return void
     */
    protected function loadAps() {
        $query = "SELECT * from `switches` WHERE `desc` LIKE '%AP%' ORDER BY `location` ASC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allAP[$each['id']] = $each;
                $apSsid = $this->extractSsid($each['desc']);
                if (!empty($apSsid)) {
                    $this->allSSids[$each['id']] = $apSsid;
                }
            }
        }
    }

    /**
     * Ectracts SSID if exists from AP description
     * 
     * @param string $desc
     * 
     * @return string
     */
    protected function extractSsid($desc) {
        $result = '';
        if (!empty($desc)) {
            $rawDesc = explode(' ', $desc);
            if (!empty($rawDesc)) {
                foreach ($rawDesc as $io => $each) {
                    if (ispos($each, 'ssid:')) {
                        $result = $each;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Loads all available CPE to protected property
     * 
     * @return void
     */
    protected function loadCPEs() {
        $query = "SELECT * from `wcpedevices`;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCPE[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing CPE assigns from database
     * 
     * @return void
     */
    protected function loadAssigns() {
        $query = "SELECT * from `wcpeusers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allAssigns[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all existing users data into protected property
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllDataCache();
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadDeviceModels() {
        $this->deviceModels = zb_SwitchModelsGetAllTag();
    }

    public function getAllCPE() {
        if (empty($this->allCPE)) {
            $this->loadCPEs();
        }

        return $this->allCPE;
    }

    /**
     * Returns CPE ID from database if record with CPEMAC found or false if not
     *
     * @param string $CPEMAC
     *
     * @return bool/int
     */
    public function getCPEIDByMAC($CPEMAC) {
        if (empty($this->allCPE) or empty($CPEMAC)) {
            return false;
        }

        $query = "SELECT * from `wcpedevices` WHERE `mac` = '" . strtolower($CPEMAC) . "';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            return $all[0]['id'];
        } else {
            return false;
        }
    }

    /**
     * Returns array with all CPE data from database if record with CPEID found or false if not
     *
     * @param $CPEID
     *
     * @return array|bool
     */
    public function getCPEData($CPEID) {
        if (empty($this->allCPE) or empty($CPEID)) {
            return false;
        }

        $query = "SELECT * from `wcpedevices` WHERE `id` = " . $CPEID . ";";
        $all = simple_queryall($query);
        if (!empty($all)) {
            return $all[0];
        } else {
            return false;
        }
    }

    /**
     * Creates new CPE in database
     * 
     * @param int $modelId
     * @param string $ip
     * @param string $mac
     * @param string $location
     * @param bool $bridgeMode
     * @param int $uplinkApId
     * @param string $geo
     * 
     * @return void/string on error
     */
    public function createCPE($modelId, $ip, $mac, $snmp, $location, $bridgeMode, $uplinkApId, $geo) {
        $result = '';
        $modelId = vf($modelId, 3);
        $ipF = mysql_real_escape_string($ip);
        $mac = strtolower_utf8($mac);
        $macF = mysql_real_escape_string($mac);
        $snmpF = mysql_real_escape_string($snmp);
        $loactionF = mysql_real_escape_string($location);
        $bridgeMode = ($bridgeMode) ? 1 : 0;
        $uplinkApId = vf($uplinkApId, 3);
        $geoF = mysql_real_escape_string($geo);

        if (isset($this->deviceModels[$modelId])) {
            if (empty($macF)) {
                $macCheckFlag = true;
            } else {
                $macCheckFlag = check_mac_format($macF);
            }
            if ($macCheckFlag) {
                $query = "INSERT INTO `wcpedevices` (`id`, `modelid`, `ip`, `mac`, `snmp`, `location`, `bridge`, `uplinkapid`, `uplinkcpeid`, `geo`) "
                        . "VALUES (NULL, '" . $modelId . "', '" . $ipF . "', '" . $macF . "', '" . $snmpF . "', '" . $loactionF . "', '" . $bridgeMode . "', '" . $uplinkApId . "', NULL, '" . $geoF . "');";
                nr_query($query);
                $newId = simple_get_lastid('wcpedevices');
                log_register('WCPE CREATE [' . $newId . ']');
                $this->loadCPEs();
            } else {
                $result .= $this->messages->getStyledMessage(__('This MAC have wrong format'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': MODELID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Deletes existing CPE from database
     * 
     * @param int $cpeId
     * 
     * @return void/string
     */
    public function deleteCPE($cpeId) {
        $result = '';
        $cpeId = vf($cpeId, 3);
        if (isset($this->allCPE[$cpeId])) {
            if (!$this->isCPEProtected($cpeId)) {
                $query = "DELETE from `wcpedevices` WHERE `id`='" . $cpeId . "';";
                nr_query($query);
                log_register('WCPE DELETE [' . $cpeId . ']');
            } else {
                $result .= $this->messages->getStyledMessage(__('Some users is assigned to this CPE'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Checks is CPE assigned with some users or not?
     * 
     * @param int $cpeId
     * 
     * @return string
     */
    protected function isCPEProtected($cpeId) {
        $result = false;
        if (!empty($this->allAssigns)) {
            foreach ($this->allAssigns as $io => $each) {
                if ($each['cpeid'] == $cpeId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns user CPE assign ID or 0 if assign not exists
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    protected function userHaveCPE($userLogin) {
        $result = 0;
        if (!empty($this->allAssigns)) {
            foreach ($this->allAssigns as $io => $each) {
                if ($each['login'] == $userLogin) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns user CPE assign ID or 0 if assign not exists
     * 
     * @param int $cpeId
     * 
     * @return int
     */
    protected function cpeHaveUser($cpeId) {
        $result = 0;
        if (!empty($this->allAssigns)) {
            foreach ($this->allAssigns as $io => $each) {
                if ($each['cpeid'] == $cpeId) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders CPE creation form
     *
     * @param string $userLogin
     * @param string $CPEMAC
     * @param string $CPEIP
     * @param string $APID
     * @param bool $RenderedOutside
     * @param bool $PageReloadAfterDone
     * @param string $CtrlIDToReplaceAfterDone
     * @param string $ModalWindowID
     *
     * @return string
     */
    public function renderCPECreateForm($userLogin = '', $CPEMAC = '', $CPEIP = '', $APID = '', $RenderedOutside = false, $PageReloadAfterDone = false, $CtrlIDToReplaceAfterDone = '', $ModalWindowID = ''
    ) {
        $result = '';
        if (!empty($this->deviceModels)) {
            $apTmp = array('' => __('No'));

            if (!empty($this->allAP)) {
                foreach ($this->allAP as $io => $each) {
                    switch ($this->apSortOrder) {
                        case "id":
                        case "location":
                            $apTmp[$each['id']] = $each['location'] . ' - ' . $each['ip'] . '  ' . @$this->allSSids[$each['id']];
                            break;

                        case "ip":
                            $apTmp[$each['id']] = $each['ip'] . ' - ' . $each['location'] . '  ' . @$this->allSSids[$each['id']];
                    }
                }
            }

            $inputs = wf_HiddenInput('createnewcpe', 'true');
            $inputs .= wf_Selector('newcpemodelid', $this->deviceModels, __('Model'), '', true);
            $inputs .= wf_CheckInput('newcpebridge', __('Bridge mode'), true, false);
            $inputs .= wf_TextInput('newcpeip', __('IP'), $CPEIP, true, 15);
            $inputs .= wf_TextInput('newcpemac', __('MAC'), $CPEMAC, true, 15);
            $inputs .= wf_TextInput('newcpesnmp', __('SNMP community'), '', true, 15);
            $inputs .= wf_TextInput('newcpelocation', __('Location'), '', true, 25);
            $inputs .= wf_TextInput('newcpegeo', __('Geo location'), '', true, 25);
            $inputs .= wf_Selector('newcpeuplinkapid', $apTmp, __('Connected to AP'), $APID, true);

            if (!empty($userLogin)) {
                $TmpBlockID = 'UsrLogin_' . wf_InputId();
                $inputs .= wf_HiddenInput('assignoncreate', $userLogin);
                $inputs .= wf_tag('br', true);
                $inputs .= wf_tag('span', false, '__UsrAssignBlock', 'id="' . $TmpBlockID . '"');
                $inputs .= __('Assign WiFi equipment to user') . ':  ';
                $inputs .= wf_tag('b', false);
                $inputs .= $userLogin . '&nbsp&nbsp';
                $inputs .= wf_tag('b', true);

                $TmpLnkID = 'DelUsrLogin_' . wf_InputId();
                $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
                $inputs .= '$(function() {
                                $(\'#' . $TmpLnkID . '\').click(function(evt) {                            
                                    $("[name=assignoncreate]").val("");
                                    $(\'#' . $TmpBlockID . '\').html("' . __('Do not assign WiFi equipment to any user') . '");
                                    evt.preventDefault();
                                    return false;
                                });
                            });
                          ';
                $inputs .= wf_tag('script', true);

                $inputs .= wf_tag('a', false, '__UsrDelAssignButton', 'id="' . $TmpLnkID . '" href="#" style="vertical-align: sub;"');
                $inputs .= web_delete_icon();
                $inputs .= wf_tag('a', true);
                $inputs .= wf_tag('span', true);
                $inputs .= wf_tag('br', true);
            }

            $NoRedirChkID = 'NoRedirChk_' . wf_InputId();
            $ReloadChkID = 'ReloadChk_' . wf_InputId();
            $SubmitID = 'Submit_' . wf_InputId();
            $FormID = 'Form_' . wf_InputId();
            $HiddenReplID = 'ReplaceCtrlID_' . wf_InputId();
            $HiddenModalID = 'ModalWindowID_' . wf_InputId();

            $inputs .= wf_tag('br');
            $inputs .= ( ($RenderedOutside) ? wf_CheckInput('NoRedirect', __('Do not redirect anywhere: just add & close'), true, true, $NoRedirChkID, '__CPEAACFormNoRedirChck') : '' );
            $inputs .= ( ($PageReloadAfterDone) ? wf_CheckInput('', __('Reload page after action'), true, true, $ReloadChkID, '__CPEAACFormPageReloadChck') : '' );

            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Create'), $SubmitID);

            $result = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __CPEAssignAndCreateForm', '', $FormID);

            $result .= wf_HiddenInput('', $CtrlIDToReplaceAfterDone, $HiddenReplID, '__CPEAACFormReplaceCtrlID');
            $result .= wf_HiddenInput('', $ModalWindowID, $HiddenModalID, '__CPEAACFormModalWindowID');

            $result .= wf_tag('script', false, '', 'type="text/javascript"');
            $result .= '
                        $(\'#' . $FormID . '\').submit(function(evt) {
                            if ( $(\'#' . $NoRedirChkID . '\').is(\':checked\') ) {
                                var FrmData = $(\'#' . $FormID . '\').serialize();
                                evt.preventDefault();
                                
                                $.ajax({
                                    type: "POST",
                                    url: "' . self::URL_ME . '",
                                    data: FrmData,
                                    success: function() {
                                                if ( $(\'#' . $ReloadChkID . '\').is(\':checked\') ) { location.reload(); }
                                                $( \'#\'+$(\'#' . $HiddenReplID . '\').val() ).replaceWith(\'' . web_ok_icon() . '\');
                                                $( \'#\'+$(\'#' . $HiddenModalID . '\').val() ).dialog("close");
                                            }
                                });
                            }
                        });
                        ';
            $result .= wf_tag('script', true);
        } else {
            $result = $this->messages->getStyledMessage(__('No') . ' ' . __('Equipment models'), 'error');
        }
        return ($result);
    }

    /**
     * Renders available CPE list container
     * 
     * @return string
     */
    public function renderCPEList($userLogin = '') {
        $result = '';
        if (!empty($userLogin)) {
            $assignPostfix = '&assignpf=' . $userLogin;
        } else {
            $assignPostfix = '';
        }
        if (!empty($this->allCPE)) {
            $columns = array('ID', 'Model', 'IP', 'MAC', 'SNMP community', 'Location', 'Geo location', 'Connected to AP', 'Bridge mode', 'Actions');
            $opts = '"order": [[ 0, "desc" ]]';
            $result = wf_JqDtLoader($columns, self::URL_ME . '&ajcpelist=true' . $assignPostfix, false, __('CPE'), 100, $opts);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

    /**
     * Renders JSON data of available CPE devices
     * 
     * @return void
     */
    public function getCPEListJson($userLogin = '') {
        $json = new wf_JqDtHelper();
        if (!empty($this->allCPE)) {
            foreach ($this->allCPE as $io => $each) {
                $data[] = $each['id'];
                $data[] = @$this->deviceModels[$each['modelid']];
                $data[] = $each['ip'];
                $data[] = $each['mac'];
                $data[] = $each['snmp'];
                $data[] = $each['location'];
                $data[] = $each['geo'];
                if (isset($this->allSSids[$each['uplinkapid']])) {
                    $apLabel = @$this->allAP[$each['uplinkapid']]['ip'] . ' - ' . @$this->allSSids[$each['uplinkapid']];
                } else {
                    $apLabel = @$this->allAP[$each['uplinkapid']]['ip'] . ' - ' . @$this->allAP[$each['uplinkapid']]['location'];
                }
                $data[] = $apLabel;
                $data[] = web_bool_led($each['bridge']);

                $actLinks = '';

                if (!empty($each['ip'])) {
                    $cpeWebIfaceLink = wf_tag('a', false, '', 'href="http://' . $each['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                    $cpeWebIfaceLink .= wf_img('skins/ymaps/network.png');
                    $cpeWebIfaceLink .= wf_tag('a', true);
                    $actLinks .= $cpeWebIfaceLink . '&nbsp';
                }

                if (empty($userLogin)) {
                    $actLinks .= wf_JSAlert(self::URL_ME . '&deletecpeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $actLinks .= wf_link(self::URL_ME . '&editcpeid=' . $each['id'], web_edit_icon('Edit'));
                } else {
                    $actLinks = wf_link(self::URL_ME . '&newcpeassign=' . $each['id'] . '&assignuslo=' . $userLogin, web_icon_create('Assign'));
                }

                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Checks is user available for CPE assign?
     * 
     * @param string $userLogin
     * 
     * @return bool
     */
    protected function isUserUnassigned($userLogin) {
        $result = true;
        if (!empty($this->allAssigns)) {
            foreach ($this->allAssigns as $io => $each) {
                if ($each['login'] == $userLogin) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Assigns existing CPE to some user login
     * 
     * @param int $cpeId
     * @param string $userLogin
     * 
     * @return void/string
     */
    public function assignCPEUser($cpeId, $userLogin) {
        $result = '';
        $cpeId = vf($cpeId, 3);
        $userLoginF = mysql_real_escape_string($userLogin);
        if (isset($this->allCPE[$cpeId])) {
            if ($this->isUserUnassigned($userLogin)) {
                $query = "INSERT INTO `wcpeusers` (`id`,`cpeid`,`login`) VALUES (NULL,'" . $cpeId . "','" . $userLoginF . "');";
                nr_query($query);
                $newId = simple_get_lastid('wcpeusers');
                log_register('WCPE [' . $cpeId . '] ASSIGN (' . $userLogin . ') ID [' . $newId . ']');
            } else {
                $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': USERLOGIN_ALREADY_ASSIGNED', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Deletes CPE to user assign from database
     * 
     * @param int $assignId
     * 
     * @return void/string
     */
    public function deassignCPEUser($assignId) {
        $result = '';
        $assignId = vf($assignId, 3);
        if (isset($this->allAssigns[$assignId])) {
            $assignData = $this->allAssigns[$assignId];
            $query = "DELETE from `wcpeusers` WHERE `id`='" . $assignId . "';";
            nr_query($query);
            log_register('WCPE [' . $assignData['cpeid'] . '] DEASSIGN (' . $assignData['login'] . ') ID [' . $assignId . ']');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': ASSIGNID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Renders CPE edit form
     * 
     * @param int $cpeId
     * 
     * @return string
     */
    public function renderCPEEditForm($cpeId) {
        $result = '';
        $cpeId = vf($cpeId, 3);
        if (isset($this->allCPE[$cpeId])) {
            if (!empty($this->deviceModels)) {
                $cpeData = $this->allCPE[$cpeId];
                $apTmp = array('' => __('No'));
                if (!empty($this->allAP)) {
                    foreach ($this->allAP as $io => $each) {
                        switch ($this->apSortOrder) {
                            case "id":
                            case "location":
                                $apTmp[$each['id']] = $each['location'] . ' - ' . $each['ip'] . '  ' . @$this->allSSids[$each['id']];
                                break;

                            case "ip":
                                $apTmp[$each['id']] = $each['ip'] . ' - ' . $each['location'] . '  ' . @$this->allSSids[$each['id']];
                        }
                    }

                    $inputs = wf_HiddenInput('editcpe', $cpeId);
                    $inputs .= wf_Selector('editcpemodelid', $this->deviceModels, __('Model'), $cpeData['modelid'], true);
                    $inputs .= wf_CheckInput('editcpebridge', __('Bridge mode'), true, $cpeData['bridge']);
                    $inputs .= wf_TextInput('editcpeip', __('IP'), $cpeData['ip'], true, 15);
                    $inputs .= wf_TextInput('editcpemac', __('MAC'), $cpeData['mac'], true, 15);
                    $inputs .= wf_TextInput('editcpesnmp', __('SNMP community'), $cpeData['snmp'], true, 15);
                    $inputs .= wf_TextInput('editcpelocation', __('Location'), $cpeData['location'], true, 25);
                    $inputs .= wf_TextInput('editcpegeo', __('Geo location'), $cpeData['geo'], true, 25);
                    $inputs .= wf_Selector('editcpeuplinkapid', $apTmp, __('Connected to AP'), $cpeData['uplinkapid'], true);
                    $inputs .= wf_tag('br');
                    $inputs .= wf_Submit(__('Save'));

                    $result = wf_Form('', 'POST', $inputs, 'glamour');
                    $result .= wf_tag('br');

                    if ($this->SigmonEnabled) {
                        $SigMon = new MTsigmon();
                        $CtrlID = wf_InputId();

                        $APSignalContainerID = 'APSignal_' . $CtrlID;
                        $APPollDTContainerID = 'APSignalPollDT_' . $CtrlID;
                        $APSignalControls = $this->getAPCPESignalControls($cpeData['mac'], '#' . $APSignalContainerID, '#' . $APPollDTContainerID, $cpeData['uplinkapid']);

                        if (empty($APSignalControls)) {
                            $LastPollDateAP = '';
                            $SignalLevelLabelAP = '';
                            $RefreshButtonAP = '';
                        } else {
                            $LastPollDateAP = $APSignalControls['LastPollDate'];
                            $SignalLevelLabelAP = $APSignalControls['SignalLevelLabel'];
                            $RefreshButtonAP = $APSignalControls['RefreshButton'];
                        }


                        $CPESignalContainerID = 'CPESignal_' . $CtrlID;
                        $CPEPollDTContainerID = 'CPESignalPollDT_' . $CtrlID;
                        $CPESignalControls = $this->getAPCPESignalControls($cpeData['mac'], '#' . $CPESignalContainerID, '#' . $CPEPollDTContainerID, 0, $cpeData['ip'], $cpeData['snmp']);

                        if (empty($CPESignalControls)) {
                            $LastPollDateCPE = '';
                            $SignalLevelLabelCPE = '';
                            $RefreshButtonCPE = '';
                        } else {
                            $LastPollDateCPE = $CPESignalControls['LastPollDate'];
                            $SignalLevelLabelCPE = $CPESignalControls['SignalLevelLabel'];
                            $RefreshButtonCPE = $CPESignalControls['RefreshButton'];
                        }

                        $cells = wf_TableCell(__('Signal level on AP'), '20%', 'row2');
                        $cells .= wf_TableCell($SignalLevelLabelAP, '55%', '', 'id="' . $APSignalContainerID . '"');
                        $cells .= wf_TableCell($RefreshButtonAP);
                        $cells .= wf_TableCell($LastPollDateAP, '25%', '', 'id="' . $APPollDTContainerID . '"');
                        $rows = wf_TableRow($cells, 'row3');

                        $cells = wf_TableCell(__('Signal level on CPE'), '20%', 'row2');
                        $cells .= wf_TableCell($SignalLevelLabelCPE, '55%', '', 'id="' . $CPESignalContainerID . '"');
                        $cells .= wf_TableCell($RefreshButtonCPE);
                        $cells .= wf_TableCell($LastPollDateCPE, '25%', '', 'id="' . $CPEPollDTContainerID . '"');
                        $rows .= wf_TableRow($cells, 'row3');

                        $result .= wf_TableBody($rows, '100%', 0, '');

                        $SignalGraphAP = $SigMon->renderSignalGraphs($cpeData['mac'], true, true, false, true, true);
                        $SignalGraphCPE = $SigMon->renderSignalGraphs($cpeData['mac'], false, true, false, true, true);
                        $SignalGraphs = '';
                        $Hyphen = ' - ';

                        if (empty($SignalGraphAP)) {
                            $GraphContainerSelector = 'NoAPDataBlck_' . $CtrlID;
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeData['mac'], '#' . $GraphContainerSelector, true, true, true);
                            $SignalGraphs .= wf_tag('div', false, '', 'id="' . $GraphContainerSelector . '" style="margin: 10px auto; display: table; font-size: 14px; font-weight: 600;"');
                            $SignalGraphs .= __('No data from AP yet') . (( empty($GraphRefreshButton) ) ? '' : $Hyphen);
                            $SignalGraphs .= $GraphRefreshButton;
                            $SignalGraphs .= wf_tag('div', true);
                        } else {
                            $GraphContainerID = 'SpoilerAP_' . $CtrlID;
                            $GraphContainerSelector = '#' . $GraphContainerID . ' .spoiler_body';
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeData['mac'], $GraphContainerSelector, true, false);
                            $SignalGraphs .= wf_Spoiler($SignalGraphAP, $GraphRefreshButton . '&nbsp&nbsp' . __('Signal data from AP'), true, $GraphContainerID, '', '', '', 'style="margin: 10px auto; display: table;"');
                        }

                        if (empty($SignalGraphCPE)) {
                            $GraphContainerSelector = 'NoCPEDataBlck_' . $CtrlID;
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeData['mac'], '#' . $GraphContainerSelector, false, true, true);
                            $SignalGraphs .= wf_tag('div', false, '', 'id="' . $GraphContainerSelector . '" style="margin: 10px auto; display: table; font-size: 14px; font-weight: 600;"');
                            $SignalGraphs .= __('No data from CPE yet') . (( empty($GraphRefreshButton) ) ? '' : $Hyphen);
                            $SignalGraphs .= $GraphRefreshButton;
                            $SignalGraphs .= wf_tag('div', true);
                        } else {
                            $GraphContainerID = 'SpoilerCPE_' . $CtrlID;
                            $GraphContainerSelector = '#' . $GraphContainerID . ' .spoiler_body';
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeData['mac'], $GraphContainerSelector, false, false);
                            $SignalGraphs .= wf_Spoiler($SignalGraphCPE, $GraphRefreshButton . '&nbsp&nbsp' . __('Signal data from CPE'), true, $GraphContainerID, '', '', '', 'style="margin: 10px auto; display: table;"');
                        }

                        $result .= wf_Spoiler($SignalGraphs, __('Signal levels history graphs'), true);

                        $result .= wf_tag('script', false, '', 'type="text/javascript"');
                        $result .= $this->getSignalRefreshJS();
                        $result .= $this->getGraphRefreshJS();
                        $result .= wf_tag('script', true);

                        if (!empty($cpeData['uplinkapid'])) {
                            $result .= $this->renderAPEssentialData($cpeData['uplinkapid'], $SigMon);
                        }
                    }

                    if (!empty($cpeData['ip'])) {
                        $cpeWebIfaceLink = wf_tag('a', false, 'ubButton', 'href="http://' . $cpeData['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                        $cpeWebIfaceLink .= wf_img('skins/ymaps/network.png') . ' CPE - ' . __('Go to the web interface');
                        $cpeWebIfaceLink .= wf_tag('a', true);
                        $result .= $cpeWebIfaceLink . '&nbsp&nbsp&nbsp';
                    }

                    if (!empty($cpeData['uplinkapid']) and ! empty($this->allAP[$cpeData['uplinkapid']])) {
                        $apWebIfaceLink = wf_tag('a', false, 'ubButton', 'href="http://' . $this->allAP[$cpeData['uplinkapid']]['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                        $apWebIfaceLink .= wf_img('skins/ymaps/network.png') . ' AP - ' . __('Go to the web interface');
                        $apWebIfaceLink .= wf_tag('a', true);
                        $result .= $apWebIfaceLink . '&nbsp&nbsp&nbsp';

                        $result .= wf_Link('?module=switches&edit=' . $cpeData['uplinkapid'], web_edit_icon('Navigate to AP') . ' ' . __('Navigate to AP'), false, 'ubButton');
                        $result .= '&nbsp&nbsp&nbsp';
                    }

                    if (!empty($cpeData['geo'])) {
                        $result .= wf_Link('?module=switchmap&finddevice=' . $cpeData['geo'], web_icon_search('Find on map') . ' ' . __('Find on map'), false, 'ubButton');
                        $result .= '&nbsp&nbsp&nbsp';
                    }

                    if ($this->isCPEProtected($cpeId)) {
                        $result .= wf_JSAlert('', web_delete_icon() . ' ' . __('Delete'), __('Some users is assigned to this CPE'), '', 'ubButton');
                    } else {
                        $result .= wf_Link(self::URL_ME . '&deletecpeid=' . $cpeId, web_delete_icon('Delete') . ' ' . __('Delete'), false, 'ubButton');
                    }
                }
            } else {
                $result = $this->messages->getStyledMessage(__('No') . ' ' . __('Equipment models'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Performs CPE changes, return string on error
     * 
     * @return void/string
     */
    public function saveCPE() {
        $result = '';
        if (wf_CheckPost(array('editcpe', 'editcpemodelid'))) {
            $cpeId = vf($_POST['editcpe']);
            if (isset($this->allCPE[$cpeId])) {
                $cpeData = $this->allCPE[$cpeId];
                $where = "WHERE `id`='" . $cpeId . "'";
                //model changing
                if ($_POST['editcpemodelid'] != $cpeData['modelid']) {
                    if (isset($this->deviceModels[$_POST['editcpemodelid']])) {
                        simple_update_field('wcpedevices', 'modelid', $_POST['editcpemodelid'], $where);
                        log_register('WCPE [' . $cpeId . '] CHANGE MODEL [' . $_POST['editcpemodelid'] . ']');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': MODELID_NOT_EXISTS [' . $_POST['editcpemodelid'] . ']', 'error');
                    }
                }

                //bridge mode flag
                $bridgeFlag = (wf_CheckPost(array('editcpebridge'))) ? 1 : 0;
                if ($bridgeFlag != $cpeData['bridge']) {
                    simple_update_field('wcpedevices', 'bridge', $bridgeFlag, $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE BRIDGE `' . $bridgeFlag . '`');
                }

                //ip change
                if ($_POST['editcpeip'] != $cpeData['ip']) {
                    simple_update_field('wcpedevices', 'ip', $_POST['editcpeip'], $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE IP `' . $_POST['editcpeip'] . '`');
                }

                //mac editing
                if ($_POST['editcpemac'] != $cpeData['mac']) {
                    $clearMac = trim($_POST['editcpemac']);
                    $clearMac = strtolower_utf8($clearMac);
                    if (empty($clearMac)) {
                        $macCheckFlag = true;
                    } else {
                        $macCheckFlag = check_mac_format($clearMac);
                    }
                    if ($macCheckFlag) {
                        simple_update_field('wcpedevices', 'mac', $clearMac, $where);
                        log_register('WCPE [' . $cpeId . '] CHANGE MAC `' . $clearMac . '`');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('This MAC have wrong format') . ' ' . $clearMac, 'error');
                    }
                }

                //SNMP community change
                if ($_POST['editcpesnmp'] != $cpeData['snmp']) {
                    simple_update_field('wcpedevices', 'snmp', $_POST['editcpesnmp'], $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE SNMP COMMUNITY `' . $_POST['editcpesnmp'] . '`');
                }

                //location changing
                if ($_POST['editcpelocation'] != $cpeData['location']) {
                    simple_update_field('wcpedevices', 'location', $_POST['editcpelocation'], $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE LOC `' . $_POST['editcpelocation'] . '`');
                }


                //location changing
                if ($_POST['editcpegeo'] != $cpeData['geo']) {
                    simple_update_field('wcpedevices', 'geo', $_POST['editcpegeo'], $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE GEO `' . $_POST['editcpegeo'] . '`');
                }
                //changing uplink AP
                if ($_POST['editcpeuplinkapid'] != $cpeData['uplinkapid']) {
                    simple_update_field('wcpedevices', 'uplinkapid', $_POST['editcpeuplinkapid'], $where);
                    log_register('WCPE [' . $cpeId . '] CHANGE UPLINKAP [' . $_POST['editcpeuplinkapid'] . ']');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS [' . $cpeId . ']', 'error');
            }
        }
        return ($result);
    }

    /**
     * Returns user array in table view
     * 
     * @global object $ubillingConfig
     * @param array $usersarr
     * @return string
     */
    function renderAssignedUsersArray($usersarr) {
        if (!empty($usersarr)) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            $allrealnames = zb_UserGetAllRealnames();
            $alltariffs = zb_TariffsGetAllUsers();
            $allusercash = zb_CashGetAllUsers();
            $allusercredits = zb_CreditGetAllUsers();
            $alluserips = zb_UserGetAllIPs();

            if ($this->altCfg['ONLINE_LAT']) {
                $alluserlat = zb_LatGetAllUsers();
            }


            //additional finance links
            if ($this->altCfg['FAST_CASH_LINK']) {
                $fastcash = true;
            } else {
                $fastcash = false;
            }

            $tablecells = wf_TableCell(__('Login'));
            $tablecells .= wf_TableCell(__('Address'));
            $tablecells .= wf_TableCell(__('Real Name'));
            $tablecells .= wf_TableCell(__('IP'));
            $tablecells .= wf_TableCell(__('Tariff'));
            // last activity time
            if ($this->altCfg['ONLINE_LAT']) {
                $tablecells .= wf_TableCell(__('LAT'));
            }
            $tablecells .= wf_TableCell(__('Active'));
            //online detect
            if ($this->altCfg['DN_ONLINE_DETECT']) {
                $tablecells .= wf_TableCell(__('Users online'));
            }
            $tablecells .= wf_TableCell(__('Balance'));
            $tablecells .= wf_TableCell(__('Credit'));
            $tablecells .= wf_TableCell(__('Actions'));



            $tablerows = wf_TableRow($tablecells, 'row1');

            foreach ($usersarr as $assignId => $eachlogin) {
                @$usercash = $allusercash[$eachlogin];
                @$usercredit = $allusercredits[$eachlogin];
                //finance check
                $activity = web_green_led();
                $activity_flag = 1;
                if ($usercash < '-' . $usercredit) {
                    $activity = web_red_led();
                    $activity_flag = 0;
                }

                //fast cash link
                if ($fastcash) {
                    $financelink = wf_Link('?module=addcash&username=' . $eachlogin, wf_img('skins/icon_dollar.gif', __('Finance operations')), false, '');
                } else {
                    $financelink = '';
                }

                $profilelink = $financelink . wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin);
                $tablecells = wf_TableCell($profilelink);
                $tablecells .= wf_TableCell(@$alladdress[$eachlogin]);
                $tablecells .= wf_TableCell(@$allrealnames[$eachlogin]);
                $tablecells .= wf_TableCell(@$alluserips[$eachlogin], '', '', 'sorttable_customkey="' . ip2int(@$alluserips[$eachlogin]) . '"');
                $tablecells .= wf_TableCell(@$alltariffs[$eachlogin]);
                if ($this->altCfg['ONLINE_LAT']) {
                    if (isset($alluserlat[$eachlogin])) {
                        $cUserLat = date("Y-m-d H:i:s", $alluserlat[$eachlogin]);
                    } else {
                        $cUserLat = __('No');
                    }
                    $tablecells .= wf_TableCell($cUserLat);
                }
                $tablecells .= wf_TableCell($activity, '', '', 'sorttable_customkey="' . $activity_flag . '"');
                if ($this->altCfg['DN_ONLINE_DETECT']) {
                    if (file_exists(DATA_PATH . 'dn/' . $eachlogin)) {
                        $online_flag = 1;
                    } else {
                        $online_flag = 0;
                    }
                    $tablecells .= wf_TableCell(web_bool_star($online_flag), '', '', 'sorttable_customkey="' . $online_flag . '"');
                }
                $tablecells .= wf_TableCell($usercash);
                $tablecells .= wf_TableCell($usercredit);
                $actLinks = wf_JSAlert(self::URL_ME . '&deleteassignid=' . $assignId . '&tocpe=' . $this->allAssigns[$assignId]['cpeid'], web_delete_icon(), $this->messages->getDeleteAlert());
                $tablecells .= wf_TableCell($actLinks);

                $tablerows .= wf_TableRow($tablecells, 'row5');
            }

            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
            $result .= wf_tag('b') . __('Total') . ': ' . wf_tag('b', true) . sizeof($usersarr);
        } else {
            $result = $this->messages->getStyledMessage(__('Any users found'), 'info');
        }

        return ($result);
    }

    /**
     * Renders list of users assigned with some CPE
     * 
     * @param int $cpeId
     * 
     * @return string
     */
    public function renderCPEAssignedUsers($cpeId) {
        $result = '';
        if (!empty($this->allAssigns)) {
            $assignedUsers = array();
            foreach ($this->allAssigns as $io => $each) {
                if (!empty($each)) {
                    if ($each['cpeid'] == $cpeId) {
                        $assignedUsers[$each['id']] = $each['login'];
                    }
                }
            }

            if (!empty($assignedUsers)) {
                $result = $this->renderAssignedUsersArray($assignedUsers);
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }

        return ($result);
    }

    /**
     * Returns CPE assign controls to user profile, if no CPE is attached to user yet
     *
     * @param $userLogin
     * @param string $userIP
     * @param string $userMAC
     *
     * @return string
     */
    protected function renderCPEAssignControl($userLogin, $userIP = '', $userMAC = '') {
        $result = '';
        $result .= wf_tag('br') . wf_tag('b') . __('Users WiFi equipment') . wf_tag('b', true) . wf_tag('br');
        $result .= wf_Link(self::URL_ME . '&userassign=' . $userLogin, wf_img('skins/icon_link.gif') . ' ' . __('Assign WiFi equipment to user'), false, 'ubButton') . '&nbsp';

        //$createForm = $this->renderCPECreateForm($userLogin);
        //$result.= wf_modalAuto(web_icon_create() . ' ' . __('Create new CPE'), __('Create new CPE'), $createForm, 'ubButton');

        $LnkID = wf_InputId();
        // the line below HAS to be commented to create "CPE create&assign form" dynamically
        $result .= wf_modalAutoForm(__('Create new CPE'), '', 'dialog-modal_' . $LnkID, 'body_dialog-modal_' . $LnkID);
        $result .= wf_tag('a', false, 'ubButton', 'id="' . $LnkID . '" href="#"');
        $result .= web_icon_create() . ' ' . __('Create new CPE');
        $result .= wf_tag('a', true);
        $result .= wf_tag('script', false, '', 'type="text/javascript"');


        // just an example for creating delegated JS event bindings for dynamically created objects that aren't exist on the page yet
        // this particular example is for "DelUserAssignment" button("red cross" near user's login) on "CPE create&assign form"
        // it's needed when the "CPE create&assign form" is created DYNAMICALLY
        // it's located here, 'cause this part of page already exists on the moment of "CPE create&assign form" creation
        // we're binding event to class name ".UsrDelAssignButton" here, 'cause it's ID we will never know from here - it simply does not exists yet
        // so, be sure to add that class to your control when you create it
        //
        /* $result.= '
          $(document).on("click", ".__UsrDelAssignButton", function() {
          alert(\'lalala\');
          return false;
          });
          '; */


        // below is an example of how to create "CPE create&assign form" dynamically
        /* $result.=  '
          $(\'#' . $LnkID . '\').click(function(evt) {
          $.ajax({
          type: "GET",
          url: "' . self::URL_ME . '",
          data: {renderCreateForm:true, renderDynamically:true, userLogin:"' . $userLogin . '", ModalWID:"dialog-modal_' . $LnkID . '", ModalWBID:"body_dialog-modal_' . $LnkID .'"},
          success: function(result) {
          $(document.body).append(result);
          $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
          }
          });

          evt.preventDefault();
          return false;
          });
          '; */

        $result .= '                    
                    $(\'#' . $LnkID . '\').click(function(evt) {
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_ME . '",                              
                            data: {
                                renderCreateForm:true,
                                renderedOutside:true,
                                reloadPageAfterDone:true,
                                userLogin:"' . $userLogin . '",
                                wcpeMAC:"' . $userMAC . '",
                                wcpeIP:"' . $userIP . '",
                                wcpeAPID:"",
                                ActionCtrlID:"' . $LnkID . '",
                                ModalWID:"dialog-modal_' . $LnkID . '"
                            },
                            success: function(result) {                                        
                                        $(\'#body_dialog-modal_' . $LnkID . '\').html(result);
                                        $(\'#dialog-modal_' . $LnkID . '\').dialog("open");                                 
                                     }
                        });
                        
                        evt.preventDefault();
                        return false;
                    });
                    ';
        $result .= wf_tag('script', true);
        $result .= wf_delimiter();
        //$result.=wf_tag('br');

        return ($result);
    }

    /**
     * Renders user profile CPE controls
     * 
     * @param string $userLogin
     * @param array $allUserData
     * 
     * @return string
     */
    public function renderCpeUserControls($userLogin, $allUserData) {
        $result = '';
        $assignId = $this->userHaveCPE($userLogin);
        //debarr($allUserData);
        if ($assignId) {
            //user have some CPE assigned
            $assignedCpeId = $this->allAssigns[$assignId]['cpeid'];
            if (isset($this->allCPE[$assignedCpeId])) {
                $assignedCpeData = $this->allCPE[$assignedCpeId];

                if (!empty($assignedCpeData)) {
                    $CPESNMPCommunity = ( empty($assignedCpeData['snmp']) ) ? 'public' : $assignedCpeData['snmp'];

                    $actLinks = '';
                    $telepathySup = wf_tag('abbr', false, '', 'title="' . __('Taken from the user, because the router mode is used') . '"') . '(?)' . wf_tag('abbr', true);
                    $telepathySup = ' ' . wf_tag('sup') . $telepathySup . wf_tag('sup', true);
                    $result .= wf_tag('br', true) . wf_tag('b') . __('Users WiFi equipment') . wf_tag('b', true);
                    $cpeModel = $this->deviceModels[$assignedCpeData['modelid']];
                    $cpeBridge = $assignedCpeData['bridge'];

                    $cpeIp = $assignedCpeData['ip'];
                    $cpeIpLabel = $cpeIp;
                    if ((empty($cpeIp)) AND ( !$cpeBridge)) {
                        $cpeIp = $allUserData[$userLogin]['ip'];
                        $cpeIpLabel = $cpeIp . $telepathySup;
                    }

                    $cpeMac = $assignedCpeData['mac'];
                    $cpeMacLabel = $cpeMac;
                    if ((empty($cpeMac)) AND ( !$cpeBridge)) {
                        $cpeMac = $allUserData[$userLogin]['mac'];
                        $cpeMacLabel = $cpeMac . $telepathySup;
                    }

                    $cpeLocation = $assignedCpeData['location'];
                    if ((empty($cpeLocation)) AND ( !$cpeBridge)) {
                        $cpeLocation = $allUserData[$userLogin]['fulladress'] . $telepathySup;
                    }

                    $cpeGeo = $assignedCpeData['geo'];
                    if ((empty($cpeGeo)) AND ( !$cpeBridge)) {
                        $cpeGeoCoords = $allUserData[$userLogin]['geo'];
                        if (!empty($cpeGeoCoords)) {
                            $cpeGeo = $cpeGeoCoords . $telepathySup;
                        }
                    }


                    $APSysInfo = '';
                    $APSigLvlCells = '';
                    $CPESigLvlCells = '';
                    $SignalGraphsBlock = '';

                    if ($this->SigmonEnabled) {
                        $SigMon = new MTsigmon();
                        $CtrlID = wf_InputId();

                        if (!empty($this->allAP) and ! empty($assignedCpeData['uplinkapid']) and ! empty($this->allAP[$assignedCpeData['uplinkapid']])) {
                            $APID = $this->allAP[$assignedCpeData['uplinkapid']]['id'];
                        } else {
                            $APID = 0;
                        }

                        $APSignalContainerID = 'APSignal_' . $CtrlID;
                        $APPollDTContainerID = 'APSignalPollDT_' . $CtrlID;
                        $APSignalControls = $this->getAPCPESignalControls($cpeMac, '#' . $APSignalContainerID, '#' . $APPollDTContainerID, $APID);

                        $LastPollDateAP = $APSignalControls['LastPollDate'];
                        $SignalLevelLabelAP = $APSignalControls['SignalLevelLabel'];
                        $RefreshButtonAP = $APSignalControls['RefreshButton'];


                        $CPESignalContainerID = 'CPESignal_' . $CtrlID;
                        $CPEPollDTContainerID = 'CPESignalPollDT_' . $CtrlID;
                        $CPESignalControls = $this->getAPCPESignalControls($cpeMac, '#' . $CPESignalContainerID, '#' . $CPEPollDTContainerID, 0, $cpeIp, $CPESNMPCommunity);

                        $LastPollDateCPE = $CPESignalControls['LastPollDate'];
                        $SignalLevelLabelCPE = $CPESignalControls['SignalLevelLabel'];
                        $RefreshButtonCPE = $CPESignalControls['RefreshButton'];

                        $APSigLvlCells = wf_TableCell(__('Signal level on AP'), '20%', 'row2');
                        $APSigLvlCells .= wf_TableCell($SignalLevelLabelAP, '55%', '', 'id="' . $APSignalContainerID . '"');
                        $APSigLvlCells .= wf_TableCell($RefreshButtonAP);
                        $APSigLvlCells .= wf_TableCell($LastPollDateAP, '25%', '', 'id="' . $APPollDTContainerID . '"');

                        $CPESigLvlCells = wf_TableCell(__('Signal level on CPE'), '20%', 'row2');
                        $CPESigLvlCells .= wf_TableCell($SignalLevelLabelCPE, '55%', '', 'id="' . $CPESignalContainerID . '"');
                        $CPESigLvlCells .= wf_TableCell($RefreshButtonCPE);
                        $CPESigLvlCells .= wf_TableCell($LastPollDateCPE, '25%', '', 'id="' . $CPEPollDTContainerID . '"');


                        $SignalGraphAP = $SigMon->renderSignalGraphs($cpeMac, true, true, false, true, true);
                        $SignalGraphCPE = $SigMon->renderSignalGraphs($cpeMac, false, true, false, true, true);
                        $SignalGraphs = '';
                        $Hyphen = ' - ';

                        if (empty($SignalGraphAP)) {
                            $GraphContainerSelector = 'NoAPDataBlck_' . $CtrlID;
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeMac, '#' . $GraphContainerSelector, true, true, true);
                            $SignalGraphs .= wf_tag('div', false, '', 'id="' . $GraphContainerSelector . '" style="margin: 10px auto; display: table; font-size: 14px; font-weight: 600;"');
                            $SignalGraphs .= __('No data from AP yet') . (( empty($GraphRefreshButton) ) ? '' : $Hyphen);
                            $SignalGraphs .= $GraphRefreshButton;
                            $SignalGraphs .= wf_tag('div', true);
                        } else {
                            $GraphContainerID = 'SpoilerAP_' . $CtrlID;
                            $GraphContainerSelector = '#' . $GraphContainerID . ' .spoiler_body';
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeMac, $GraphContainerSelector, true, false);
                            $SignalGraphs .= wf_Spoiler($SignalGraphAP, $GraphRefreshButton . '&nbsp&nbsp' . __('Signal data from AP'), true, $GraphContainerID, '', '', '', 'style="margin: 10px auto; display: table;"');
                        }

                        if (empty($SignalGraphCPE)) {
                            $GraphContainerSelector = 'NoCPEDataBlck_' . $CtrlID;
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeMac, '#' . $GraphContainerSelector, false, true, true);
                            $SignalGraphs .= wf_tag('div', false, '', 'id="' . $GraphContainerSelector . '" style="margin: 10px auto; display: table; font-size: 14px; font-weight: 600;"');
                            $SignalGraphs .= __('No data from CPE yet') . (( empty($GraphRefreshButton) ) ? '' : $Hyphen);
                            $SignalGraphs .= $GraphRefreshButton;
                            $SignalGraphs .= wf_tag('div', true);
                        } else {
                            $GraphContainerID = 'SpoilerCPE_' . $CtrlID;
                            $GraphContainerSelector = '#' . $GraphContainerID . ' .spoiler_body';
                            $GraphRefreshButton = $this->getAPCPEGraphRefreshButton($cpeMac, $GraphContainerSelector, false, false);
                            $SignalGraphs .= wf_Spoiler($SignalGraphCPE, $GraphRefreshButton . '&nbsp&nbsp' . __('Signal data from CPE'), true, $GraphContainerID, '', '', '', 'style="margin: 10px auto; display: table;"');
                        }

                        $SignalGraphsBlock .= wf_Spoiler($SignalGraphs, __('Signal levels history graphs'), true);

                        $SignalGraphsBlock .= wf_tag('script', false, '', 'type="text/javascript"');
                        $SignalGraphsBlock .= $this->getSignalRefreshJS();
                        $SignalGraphsBlock .= $this->getGraphRefreshJS();
                        $SignalGraphsBlock .= wf_tag('script', true);

                        $APSysInfo = $this->renderAPEssentialData($APID, $SigMon);
                    }

                    $bridgeLabel = ($cpeBridge) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');
                    $cpeLink = wf_Link(self::URL_ME . '&editcpeid=' . $assignedCpeId, web_edit_icon(__('Show') . ' ' . __('CPE')), false, '');

                    $cpeWebIfaceLink = wf_tag('a', false, '', 'href="http://' . $cpeIp . '" target="_blank" title="' . __('Go to the web interface') . '"');
                    $cpeWebIfaceLink .= wf_img('skins/ymaps/network.png');
                    $cpeWebIfaceLink .= wf_tag('a', true);

                    $cells = wf_TableCell(__('Model'), '20%', 'row2');
                    $cells .= wf_TableCell($cpeModel . '&nbsp&nbsp&nbsp' . $cpeLink);
                    $rows = wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('IP'), '20%', 'row2');
                    $cells .= wf_TableCell($cpeIpLabel . '&nbsp&nbsp&nbsp' . $cpeWebIfaceLink);
                    $rows .= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('MAC'), '20%', 'row2');
                    $cells .= wf_TableCell($cpeMacLabel);
                    $rows .= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Location'), '20%', 'row2');
                    $cells .= wf_TableCell($cpeLocation);
                    $rows .= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Geo location'), '20%', 'row2');
                    $cells .= wf_TableCell($cpeGeo);
                    $rows .= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Bridge mode'), '20%', 'row2');
                    $cells .= wf_TableCell($bridgeLabel);
                    $rows .= wf_TableRow($cells, 'row3');

                    if (!empty($assignedCpeData['uplinkapid'])) {
                        if (isset($this->allAP[$assignedCpeData['uplinkapid']])) {
                            $apLabel = $this->allAP[$assignedCpeData['uplinkapid']]['ip'];

                            $apWebIfaceLink = wf_tag('a', false, '', 'href="http://' . $this->allAP[$assignedCpeData['uplinkapid']]['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                            $apWebIfaceLink .= wf_img('skins/ymaps/network.png');
                            $apWebIfaceLink .= wf_tag('a', true);

                            if (isset($this->allSSids[$assignedCpeData['uplinkapid']])) {
                                $apLabel .= ' - ' . $this->allSSids[$assignedCpeData['uplinkapid']];
                            } else {
                                $apLabel .= ' - ' . $this->allAP[$assignedCpeData['uplinkapid']]['location'];
                            }
                            $apLink = wf_Link('?module=switches&edit=' . $assignedCpeData['uplinkapid'], web_edit_icon(__('Navigate to AP')), false, '');
                            $cells = wf_TableCell(__('Connected to AP'), '20%', 'row2');
                            $cells .= wf_TableCell($apLabel . '&nbsp&nbsp&nbsp' . $apLink . '&nbsp&nbsp&nbsp' . $apWebIfaceLink);
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    } else {
                        $cells = wf_TableCell(__('Connected to AP'), '20%', 'row2');
                        $cells .= wf_TableCell(__('No'));
                        $rows .= wf_TableRow($cells, 'row3');
                    }

                    $rows .= wf_TableRow($APSigLvlCells, 'row3');
                    $rows .= wf_TableRow($CPESigLvlCells, 'row3');

                    $result .= wf_TableBody($rows, '100%', 0, '');

                    $result .= $APSysInfo;
                    $result .= $SignalGraphsBlock;
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS [' . $assignedCpeId . ']', 'error');
            }
        } else {
            $result .= $this->renderCPEAssignControl($userLogin, $allUserData[$userLogin]['ip'], $allUserData[$userLogin]['mac']);
        }
        return ($result);
    }

    /**
     * Renders main module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result .= wf_modalAuto(web_add_icon() . ' ' . __('Create new CPE'), __('Create new CPE'), $this->renderCPECreateForm(), 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME, wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available CPE list'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&rendermap=true', wf_img('skins/ymaps/network.png') . ' ' . __('Map') . ' ' . __('CPE') . '/' . __('AP'), false, 'ubButton');
        return ($result);
    }

    /**
     * Renders wireless devices map
     * 
     * @return string
     */
    public function renderDevicesMap() {
        global $ubillingConfig;
        $ymconf = $ubillingConfig->getYmaps();
        $this->loadUserData();
        $deadSwitches = zb_SwitchesGetAllDead();
        $result = '';
        $placemarks = '';
        $result = wf_tag('div', false, '', 'id="ubmap" style="width: 100%; height:800px;"');
        $result .= wf_tag('div', true);

        if (!empty($this->allAP)) {
            foreach ($this->allAP as $io => $each) {
                if (!empty($each['geo'])) {
                    $apName = $each['location'] . ' - ' . $each['ip'] . ' ' . @$this->allSSids[$each['id']];
                    $apLink = trim(wf_Link('?module=switches&edit=' . $each['id'], web_edit_icon() . ' ' . __('Navigate to AP')));
                    $apLink = str_replace('"', '', $apLink);
                    $apIcon = sm_MapGoodIcon();
                    if (isset($deadSwitches[$each['ip']])) {
                        $apIcon = sm_MapBadIcon();
                    }
                    $placemarks .= sm_MapAddMark($each['geo'], $apName, $apLink, '', $apIcon);
                }
            }
        }

        if (!empty($this->allCPE)) {
            foreach ($this->allCPE as $io => $each) {
                $cpeCoords = '';
                if (!empty($each['geo'])) {
                    $cpeCoords = $each['geo'];
                } else {
                    //try extract from user geo
                    $assignId = $this->cpeHaveUser($each['id']);
                    if ($assignId) {
                        if (isset($this->allAssigns[$assignId])) {
                            if (isset($this->allUsersData[$this->allAssigns[$assignId]['login']])) {
                                if (!empty($this->allUsersData[$this->allAssigns[$assignId]['login']]['geo'])) {
                                    $cpeCoords = $this->allUsersData[$this->allAssigns[$assignId]['login']]['geo'];
                                }
                            }
                        }
                    }
                }
                //drawing CPE on map
                if (!empty($cpeCoords)) {
                    $cpeName = $each['id'] . ': ' . @$this->deviceModels[$each['modelid']];
                    $cpeLink = trim(wf_Link(self::URL_ME . '&editcpeid=' . $each['id'], web_edit_icon() . ' ' . __('Show') . ' ' . __('CPE')));
                    $cpeLink = str_replace('"', '', $cpeLink);
                    $placemarks .= sm_MapAddMark($cpeCoords, $cpeName, $cpeLink, '', um_MapBuildIcon(1));

                    //drawing CPE uplinks
                    if (!empty($each['uplinkapid'])) {
                        if (isset($this->allAP[$each['uplinkapid']])) {
                            if (!empty($this->allAP[$each['uplinkapid']]['geo'])) {
                                $lineColor = '#00FF00';
                                $lineWidth = 2;
                                if (isset($deadSwitches[$this->allAP[$each['uplinkapid']]['ip']])) {
                                    $lineColor = '#FF0000';
                                    $lineWidth = 3;
                                }
                                $placemarks .= sm_MapAddLine($cpeCoords, $this->allAP[$each['uplinkapid']]['geo'], $lineColor, '', $lineWidth);
                            }
                        }
                    }
                }
            }
        }


        $result .= generic_MapInit($ymconf['CENTER'], $ymconf['ZOOM'], $ymconf['TYPE'], $placemarks, '', $ymconf['LANG']);
        return ($result);
    }

    /**
     * Returns signal show&repoll controls
     * $SignalContainerSelector and $PollDateContainerSelector must be a valid JQuery selectors where returned data will be stored in
     *
     * @param string $CPEMAC
     * @param string $SignalContainerSelector
     * @param string $PollDateContainerSelector
     * @param int $UplinkAPID
     * @param string $CPEIP
     * @param string $CPESNMPCommunity
     *
     * @return array
     */
    public function getAPCPESignalControls($CPEMAC, $SignalContainerSelector, $PollDateContainerSelector, $UplinkAPID = 0, $CPEIP = '', $CPESNMPCommunity = '') {
        $ReturnedControlsArray = array();

        if (empty($CPEMAC) || empty($SignalContainerSelector) || empty($PollDateContainerSelector)) {
            $ReturnedControlsArray = array('LastPollDate' => '',
                'SignalLevelLabel' => '',
                'RefreshButton' => ''
            );
            return $ReturnedControlsArray;
        }

        $SigMon = new MTsigmon();
        $CtrlID = wf_InputId();

        if (empty($UplinkAPID)) {
            // return CPE signal&poll controls
            $SignalDataArray = $SigMon->getCPESignalData($CPEMAC, 0, $CPEIP, $CPESNMPCommunity, false, false);
            $LnkID = 'CPESigUpd_' . $CtrlID;
            $LnkTitle = __('Refresh data for this CPE');
        } else {
            // return AP signal&poll controls
            $SignalDataArray = $SigMon->getCPESignalData($CPEMAC, $UplinkAPID, '', '', true, false);
            $LnkID = 'APSigUpd_' . $CtrlID;
            $LnkTitle = __('Refresh data for this AP');
        }

        $LastPollDate = (empty($SignalDataArray[0])) ? __('Device is not polled yet') : __('Cache state at time') . ':  ' . $SignalDataArray[0];
        $SignalLevelLabel = (empty($SignalDataArray[1])) ? '' : $SignalDataArray[1];

        $RefreshButton = wf_tag('a', false, '', 'href="#" id="' . $LnkID . '" title="' . $LnkTitle . '"');
        $RefreshButton .= wf_img('skins/refresh.gif');
        $RefreshButton .= wf_tag('a', true);
        $RefreshButton .= wf_tag('script', false, '', 'type="text/javascript"');
        $RefreshButton .= '$(\'#' . $LnkID . '\').click(function(evt) {
                                        $(this).find(\'img\').toggleClass("image_rotate");
                                        APCPESignalRefresh("' . $CPEMAC . '", "' . $SignalContainerSelector . '", "' . $PollDateContainerSelector . '", "'
                . $UplinkAPID . '", "' . $CPEIP . '", "' . $CPESNMPCommunity . '", "#' . $LnkID . '");                                        
                                        evt.preventDefault();
                                        return false;
                                    });';
        $RefreshButton .= wf_tag('script', true);

        $ReturnedControlsArray = array('LastPollDate' => $LastPollDate,
            'SignalLevelLabel' => $SignalLevelLabel,
            'RefreshButton' => $RefreshButton
        );

        return $ReturnedControlsArray;
    }

    /**
     * Returns signal history graph for given CPE.
     * $GraphContainerSelector must be a valid JQuery selector where returned data will be stored in
     * $ReplaceContainerWithGraph:
     *      if true - returned data will replace $GraphContainerSelector with JQuery's "replaceWith()" method
     *      otherwise - $GraphContainerSelector's inner HTML will be replaced with returned data (JQuery's "html()" method will be used)
     *
     * @param string $CPEMAC
     * @param string $GraphContainerSelector
     * @param bool $GraphFromAP
     * @param bool $ReturnGraphInSpoiler
     * @param bool $ReplaceContainerWithGraph
     *
     * @return string
     */
    public function getAPCPEGraphRefreshButton($CPEMAC, $GraphContainerSelector, $GraphFromAP = false, $ReturnGraphInSpoiler = false, $ReplaceContainerWithGraph = false) {
        if (empty($GraphContainerSelector) || empty($CPEMAC)) {
            return '';
        }

        $CtrlID = wf_InputId();

        $LnkTitle = __('Refresh') . ' ' . __('data');
        $LnkID = ( ($GraphFromAP) ? 'APGraphUpd_' : 'CPEGraphUpd_') . $CtrlID;

        $GraphRefreshButton = wf_tag('a', false, '', 'href="#" id="' . $LnkID . '" style="vertical-align: sub;" title="' . $LnkTitle . '"');
        $GraphRefreshButton .= wf_img('skins/refresh.gif');
        $GraphRefreshButton .= wf_tag('a', true);
        $GraphRefreshButton .= wf_tag('script', false, '', 'type="text/javascript"');
        $GraphRefreshButton .= '$(\'#' . $LnkID . '\').click(function(evt) {
                                            evt.stopImmediatePropagation();            
                                            $(this).find(\'img\').toggleClass("image_rotate");
                                            SignalGraphRefresh("' . $CPEMAC . '", "' . $GraphContainerSelector . '", ' . var_export($GraphFromAP, true) . ', true, false, true, true, '
                . var_export($ReturnGraphInSpoiler, true) . ', ' . var_export($ReplaceContainerWithGraph, true) . ', "#' . $LnkID . '");                                                                                            
                                            evt.preventDefault();
                                            return false;                
                                        });';
        $GraphRefreshButton .= wf_tag('script', true);

        return $GraphRefreshButton;
    }

    /**
     * Returns JS code for controls returned by "getAPCPESignalControls()" function
     * Without this code controls returned by "getAPCPESignalControls()" function will not work properly
     *
     * @param bool $PutInsideScriptTag
     *
     * @return string
     */
    public function getSignalRefreshJS($PutInsideScriptTag = false) {
        $SignalRefreshJS = ($PutInsideScriptTag) ? wf_tag('script', false, '', 'type="text/javascript"') : '';
        $SignalRefreshJS .= '
                            function APCPESignalRefresh(MACCPE, SignalContainerSelector, PollDateContainerSelector, APID = \'\', IPCPE = \'\', SNMPCCPE = \'public\', RefreshButtonID = \'\') {
                                var SignalContainerObj = $(SignalContainerSelector);                        
                                if ( !SignalContainerObj.length || !(SignalContainerObj instanceof jQuery)) {return false;}
                                
                                var PollDateContainerObj = $(PollDateContainerSelector);                        
                                if ( !PollDateContainerObj.length || !(PollDateContainerObj instanceof jQuery)) {return false;}
                                
                                $.ajax({
                                    type: "GET",
                                    url: "' . self::URL_SIGMON . '",
                                    data: {IndividualRefresh:true, cpeMAC:MACCPE, apid:APID, cpeIP:IPCPE, cpeCommunity:SNMPCCPE},
                                    success: function(result) {
                                        var RefreshButtonObj = $(RefreshButtonID);
                                        if ( RefreshButtonObj.length && (RefreshButtonObj instanceof jQuery)) {
                                            RefreshButtonObj.find(\'img\').toggleClass("image_rotate");
                                        }
                                        
                                        try {                                            
                                            var jsonObj = $.parseJSON(result);                                            
                                            SignalContainerObj.html(jsonObj.SignalLevel);
                                            PollDateContainerObj.html("' . __('Cache state at time') . ':  " + ' . 'jsonObj.LastPollDate);                                                
                                        } catch (e) {
                                           return false;
                                        }                                      
                                    }
                                });
                            }
                            ';
        $SignalRefreshJS .= ($PutInsideScriptTag) ? wf_tag('script', true) : '';
        return $SignalRefreshJS;
    }

    /**
     * Returns JS code for controls returned by "getAPCPEGraphRefreshButton()" function
     * Without this code controls returned by "getAPCPEGraphRefreshButton()" function will not work properly
     *
     * @param bool $PutInsideScriptTag
     *
     * @return string
     */
    public function getGraphRefreshJS($PutInsideScriptTag = false) {
        $GraphRefreshJS = ($PutInsideScriptTag) ? wf_tag('script', false, '', 'type="text/javascript"') : '';
        $GraphRefreshJS .= '
                            function SignalGraphRefresh(CPEMAC, GraphContainerSelector, FromAP = false, ShowTitle = false, ShowXLabel = false, ShowYLabel = false, ShowRangeSelector = false, ReturnInSpoiler = false, ReplaceContainerWithGraph = false, RefreshButtonID = \'\') {                               
                                var GraphContainerObj = $(GraphContainerSelector);                                
                                if ( !GraphContainerObj.length || !(GraphContainerObj instanceof jQuery)) {return false;}                 
                                                                                                                                
                                $.ajax({
                                    type: "GET",
                                    url: "' . self::URL_SIGMON . '",
                                    data: { IndividualRefresh:true, 
                                            getGraphs:true,
                                            cpeMAC:CPEMAC,
                                            fromAP:FromAP,
                                            showTitle:ShowTitle,
                                            showXLabel:ShowXLabel,
                                            showYLabel:ShowYLabel,
                                            showRangeSelector:ShowRangeSelector,
                                            returnInSpoiler:ReturnInSpoiler
                                          },
                                    success: function(result) {
                                        var RefreshButtonObj = $(RefreshButtonID);
                                        if ( RefreshButtonObj.length && (RefreshButtonObj instanceof jQuery)) {
                                            RefreshButtonObj.find(\'img\').toggleClass("image_rotate");
                                        }
                                                                                
                                        if (empty(result)) {return false;}
                                        
                                        if (ReplaceContainerWithGraph) {
                                            GraphContainerObj.replaceWith(result);
                                        } else {                                            
                                            GraphContainerObj.html(result);
                                        }
                                    }
                                });
                            };
                                                            
                            function empty (mixed_var) {
                             // version: 909.322
                             // discuss at: http://phpjs.org/functions/empty
                             var key;
                             if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || mixed_var === undefined ) {
                              return true;
                             }
                             if (typeof mixed_var == \'object\') {
                              for (key in mixed_var) {
                               return false;
                              }
                              return true;
                             }
                             return false;
                            }
                          ';
        $GraphRefreshJS .= ($PutInsideScriptTag) ? wf_tag('script', true) : '';
        return $GraphRefreshJS;
    }

    private function renderAPEssentialData($APID, $SigMonObj) {
        $InfoButtonID = 'InfID_' . $APID;
        $InfoBlockID = 'InfBlck_' . $APID;

        $APInfoBlock = wf_tag('div', false, '', 'id="' . $InfoBlockID . '"');
        $APInfoBlock .= ( isset($this->altCfg['USERPROFILE_APINFO_AUTOLOAD']) and $this->altCfg['USERPROFILE_APINFO_AUTOLOAD'] ) ? $SigMonObj->getAPEssentialData($APID, true, false, false) : '';
        $APInfoBlock .= wf_tag('div', true);

        $APInfoButton = wf_tag('a', false, '', 'href="#" id="' . $InfoButtonID . '" title="' . __('Get system info for this AP') . '"');
        $APInfoButton .= wf_tag('img', false, '', 'src="skins/icn_alert_info.png" border="0" style="vertical-align: bottom;"');
        $APInfoButton .= wf_tag('a', true);
        $APInfoButton .= wf_tag('script', false, '', 'type="text/javascript"');
        $APInfoButton .= '$(\'#' . $InfoButtonID . '\').click(function(evt) {
                                        $(\'img\', this).toggleClass("image_rotate");
                                        getAPInfo(' . $APID . ', "#' . $InfoBlockID . '", true, false, ' . $InfoButtonID . ');                                        
                                        evt.preventDefault();
                                        return false;                
                                    });';
        $APInfoButton .= wf_tag('script', true);


        $Result = wf_Spoiler($APInfoBlock, $APInfoButton . '&nbsp&nbsp' . __('System AP info'), true, '', '', '', '', 'style="margin: 10px auto;"');
        $Result .= wf_tag('script', false, '', 'type="text/javascript"');
        $Result .= 'function getAPInfo(APID, InfoBlckSelector, ReturnHTML = false, InSpoiler = false, RefreshButtonSelector) {                        
                        $.ajax({
                            type: "GET",
                            url: "' . self::URL_SIGMON . '",
                            data: { IndividualRefresh:true, 
                                    GetAPInfo:true, 
                                    apid:APID,
                                    returnAsHTML:ReturnHTML,
                                    returnInSpoiler:InSpoiler
                                  },
                            success: function(result) {                       
                                        if ($.type(RefreshButtonSelector) === \'string\') {
                                            $("#"+RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        } else {
                                            $(RefreshButtonSelector).find(\'img\').toggleClass("image_rotate");
                                        }
                                        
                                        var InfoBlck = $(InfoBlckSelector);                                        
                                        if ( !InfoBlck.length || !(InfoBlck instanceof jQuery)) {return false;}
                                              
                                        $(InfoBlck).html(result);
                                     }
                        });
                    }
                    ';
        $Result .= wf_tag('script', true);

        return $Result;
    }

}

?>