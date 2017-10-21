<?php

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
    protected $allAP = array();

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
    protected $allCPE = array();

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
     * Base module URL
     */
    const URL_ME = '?module=wcpe';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadDeviceModels();
        $this->loadAps();
        $this->loadCPEs();
        $this->loadAssigns();
    }

    /**
     * Loads system alter config to protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
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
    public function createCPE($modelId, $ip, $mac, $location, $bridgeMode = false, $uplinkApId, $geo) {
        $result = '';
        $modelId = vf($modelId, 3);
        $ipF = mysql_real_escape_string($ip);
        $mac = strtolower_utf8($mac);
        $macF = mysql_real_escape_string($mac);
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
                $query = "INSERT INTO `wcpedevices` (`id`, `modelid`, `ip`, `mac`, `location`, `bridge`, `uplinkapid`, `uplinkcpeid`, `geo`) "
                        . "VALUES (NULL, '" . $modelId . "', '" . $ipF . "', '" . $macF . "', '" . $loactionF . "', '" . $bridgeMode . "', '" . $uplinkApId . "', NULL, '" . $geoF . "');";
                nr_query($query);
                $newId = simple_get_lastid('wcpedevices');
                log_register('WCPE CREATE [' . $newId . ']');
                $this->loadCPEs();
            } else {
                $result.=$this->messages->getStyledMessage(__('This MAC have wrong format'), 'error');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': MODELID_NOT_EXISTS', 'error');
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
                $result.=$this->messages->getStyledMessage(__('Some users is assigned to this CPE'), 'warning');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
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
     * 
     * @return string
     */
    public function renderCPECreateForm($userLogin = '') {
        $result = '';
        if (!empty($this->deviceModels)) {
            $apTmp = array('' => __('No'));

            if (!empty($this->allAP)) {
                foreach ($this->allAP as $io => $each) {
                    $apTmp[$each['id']] = $each['location'] . ' - ' . $each['ip'] . ' ' . @$this->allSSids[$each['id']];
                }
            }

            $inputs = wf_HiddenInput('createnewcpe', 'true');
            $inputs.= wf_Selector('newcpemodelid', $this->deviceModels, __('Model'), '', true);
            $inputs.= wf_CheckInput('newcpebridge', __('Bridge mode'), true, false);
            $inputs.= wf_TextInput('newcpeip', __('IP'), '', true, 15);
            $inputs.= wf_TextInput('newcpemac', __('MAC'), '', true, 15);
            $inputs.= wf_TextInput('newcpelocation', __('Location'), '', true, 25);
            $inputs.= wf_TextInput('newcpegeo', __('Geo location'), '', true, 25);
            $inputs.= wf_Selector('newcpeuplinkapid', $apTmp, __('Connected to AP'), '', true);
            if (!empty($userLogin)) {
                $inputs.=wf_HiddenInput('assignoncreate', $userLogin);
                $inputs.=__('Assign user WiFi equipment') . ': ' . $userLogin;
            }
            $inputs.=wf_tag('br');
            $inputs.= wf_Submit(__('Create'));

            $result = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
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
            $columns = array('ID', 'Model', 'IP', 'MAC', 'Location', 'Geo location', 'Connected to AP', 'Bridge mode', 'Actions');
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
                $data[] = $each['location'];
                $data[] = $each['geo'];
                if (isset($this->allSSids[$each['uplinkapid']])) {
                    $apLabel = @$this->allAP[$each['uplinkapid']]['ip'] . ' - ' . @$this->allSSids[$each['uplinkapid']];
                } else {
                    $apLabel = @$this->allAP[$each['uplinkapid']]['ip'] . ' - ' . @$this->allAP[$each['uplinkapid']]['location'];
                }
                $data[] = $apLabel;
                $data[] = web_bool_led($each['bridge']);
                if (empty($userLogin)) {
                    $actLinks = wf_JSAlert(self::URL_ME . '&deletecpeid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $actLinks.= wf_JSAlert(self::URL_ME . '&editcpeid=' . $each['id'], web_edit_icon(), $this->messages->getEditAlert() . ' ' . __('Edit') . '?');
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
                $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': USERLOGIN_ALREADY_ASSIGNED', 'error');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
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
            $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': ASSIGNID_NOT_EXISTS', 'error');
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
                        $apTmp[$each['id']] = $each['location'] . ' - ' . $each['ip'] . ' ' . @$this->allSSids[$each['id']];
                    }

                    $inputs = wf_HiddenInput('editcpe', $cpeId);
                    $inputs.= wf_Selector('editcpemodelid', $this->deviceModels, __('Model'), $cpeData['modelid'], true);
                    $inputs.= wf_CheckInput('editcpebridge', __('Bridge mode'), true, $cpeData['bridge']);
                    $inputs.= wf_TextInput('editcpeip', __('IP'), $cpeData['ip'], true, 15);
                    $inputs.= wf_TextInput('editcpemac', __('MAC'), $cpeData['mac'], true, 15);
                    $inputs.= wf_TextInput('editcpelocation', __('Location'), $cpeData['location'], true, 25);
                    $inputs.= wf_TextInput('editcpegeo', __('Geo location'), $cpeData['geo'], true, 25);
                    $inputs.= wf_Selector('editcpeuplinkapid', $apTmp, __('Connected to AP'), $cpeData['uplinkapid'], true);
                    $inputs.=wf_tag('br');
                    $inputs.= wf_Submit(__('Save'));

                    $result = wf_Form('', 'POST', $inputs, 'glamour');
                    $result.=wf_tag('br');
                    if (!empty($cpeData['uplinkapid'])) {
                        $result.=wf_Link('?module=switches&edit=' . $cpeData['uplinkapid'], web_edit_icon('Navigate to AP') . ' ' . __('Navigate to AP'), false, 'ubButton');
                    }
                    if (!empty($cpeData['geo'])) {
                        $result.=wf_Link('?module=switchmap&finddevice=' . $cpeData['geo'], web_icon_search('Find on map') . ' ' . __('Find on map'), false, 'ubButton');
                    }
                }
            } else {
                $result = $this->messages->getStyledMessage(__('No') . ' ' . __('Equipment models'), 'error');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS', 'error');
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
                        $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': MODELID_NOT_EXISTS [' . $_POST['editcpemodelid'] . ']', 'error');
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
                        $result.=$this->messages->getStyledMessage(__('This MAC have wrong format') . ' ' . $clearMac, 'error');
                    }
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
                $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS [' . $cpeId . ']', 'error');
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
            $tablecells.=wf_TableCell(__('Address'));
            $tablecells.=wf_TableCell(__('Real Name'));
            $tablecells.=wf_TableCell(__('IP'));
            $tablecells.=wf_TableCell(__('Tariff'));
            // last activity time
            if ($this->altCfg['ONLINE_LAT']) {
                $tablecells.=wf_TableCell(__('LAT'));
            }
            $tablecells.=wf_TableCell(__('Active'));
            //online detect
            if ($this->altCfg['DN_ONLINE_DETECT']) {
                $tablecells.=wf_TableCell(__('Users online'));
            }
            $tablecells.=wf_TableCell(__('Balance'));
            $tablecells.=wf_TableCell(__('Credit'));
            $tablecells.=wf_TableCell(__('Actions'));



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
                $tablecells.=wf_TableCell(@$alladdress[$eachlogin]);
                $tablecells.=wf_TableCell(@$allrealnames[$eachlogin]);
                $tablecells.=wf_TableCell(@$alluserips[$eachlogin], '', '', 'sorttable_customkey="' . ip2long(@$alluserips[$eachlogin]) . '"');
                $tablecells.=wf_TableCell(@$alltariffs[$eachlogin]);
                if ($this->altCfg['ONLINE_LAT']) {
                    if (isset($alluserlat[$eachlogin])) {
                        $cUserLat = date("Y-m-d H:i:s", $alluserlat[$eachlogin]);
                    } else {
                        $cUserLat = __('No');
                    }
                    $tablecells.=wf_TableCell($cUserLat);
                }
                $tablecells.=wf_TableCell($activity, '', '', 'sorttable_customkey="' . $activity_flag . '"');
                if ($this->altCfg['DN_ONLINE_DETECT']) {
                    if (file_exists(DATA_PATH . 'dn/' . $eachlogin)) {
                        $online_flag = 1;
                    } else {
                        $online_flag = 0;
                    }
                    $tablecells.=wf_TableCell(web_bool_star($online_flag), '', '', 'sorttable_customkey="' . $online_flag . '"');
                }
                $tablecells.=wf_TableCell($usercash);
                $tablecells.=wf_TableCell($usercredit);
                $actLinks = wf_JSAlert(self::URL_ME . '&deleteassignid=' . $assignId . '&tocpe=' . $this->allAssigns[$assignId]['cpeid'], web_delete_icon(), $this->messages->getDeleteAlert());
                $tablecells.=wf_TableCell($actLinks);

                $tablerows.=wf_TableRow($tablecells, 'row5');
            }

            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
            $result.= wf_tag('b') . __('Total') . ': ' . wf_tag('b', true) . sizeof($usersarr);
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
                $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }

        return ($result);
    }

    /**
     * Returns link to CPE assign directory, if 
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function renderCPEAssignControl($userLogin) {
        $result = '';
        $result.=wf_tag('b') . __('Users WiFi equipment') . wf_tag('b', true) . wf_tag('br');
        $result.= wf_Link(self::URL_ME . '&userassign=' . $userLogin, wf_img('skins/icon_link.gif') . ' ' . __('Assign user WiFi equipment'), false, 'ubButton').' ';
        $createForm = $this->renderCPECreateForm($userLogin);
        $result.= wf_modalAuto(web_icon_create() . ' ' . __('Create new CPE'), __('Create new CPE'), $createForm, 'ubButton');
        $result.=wf_tag('br');
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
                    $actLinks = '';
                    $telepathySup = wf_tag('abbr', false, '', 'title="' . __('Taken from the user, because the router mode is used') . '"') . '(?)' . wf_tag('abbr', true);
                    $telepathySup = ' ' . wf_tag('sup') . $telepathySup . wf_tag('sup', true);
                    $result.=wf_tag('b') . __('Users WiFi equipment') . wf_tag('b', true);
                    $cpeModel = $this->deviceModels[$assignedCpeData['modelid']];
                    $cpeBridge = $assignedCpeData['bridge'];
                    $cpeIp = $assignedCpeData['ip'];
                    if ((empty($cpeIp)) AND ( !$cpeBridge)) {
                        $cpeIp = $allUserData[$userLogin]['ip'] . $telepathySup;
                    }

                    $cpeMac = $assignedCpeData['mac'];
                    if ((empty($cpeMac)) AND ( !$cpeBridge)) {
                        $cpeMac = $allUserData[$userLogin]['mac'] . $telepathySup;
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

                    $bridgeLabel = ($cpeBridge) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');
                    $cpeLink = wf_Link(self::URL_ME . '&editcpeid=' . $assignedCpeId, web_edit_icon(__('Show') . ' ' . __('CPE')), false, '');

                    $cells = wf_TableCell(__('Model'), '20%', 'row2');
                    $cells.= wf_TableCell($cpeModel . ' ' . $cpeLink);
                    $rows = wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('IP'), '20%', 'row2');
                    $cells.= wf_TableCell($cpeIp);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('MAC'), '20%', 'row2');
                    $cells.= wf_TableCell($cpeMac);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Location'), '20%', 'row2');
                    $cells.= wf_TableCell($cpeLocation);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Geo location'), '20%', 'row2');
                    $cells.= wf_TableCell($cpeGeo);
                    $rows.= wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Bridge mode'), '20%', 'row2');
                    $cells.= wf_TableCell($bridgeLabel);
                    $rows.= wf_TableRow($cells, 'row3');

                    if (!empty($assignedCpeData['uplinkapid'])) {
                        if (isset($this->allAP[$assignedCpeData['uplinkapid']])) {
                            $apLabel = $this->allAP[$assignedCpeData['uplinkapid']]['ip'];
                            if (isset($this->allSSids[$assignedCpeData['uplinkapid']])) {
                                $apLabel.=' - ' . $this->allSSids[$assignedCpeData['uplinkapid']];
                            } else {
                                $apLabel.=' - ' . $this->allAP[$assignedCpeData['uplinkapid']]['location'];
                            }
                            $apLink = wf_Link('?module=switches&edit=' . $assignedCpeData['uplinkapid'], web_edit_icon(__('Navigate to AP')), false, '');
                            $cells = wf_TableCell(__('Connected to AP'), '20%', 'row2');
                            $cells.= wf_TableCell($apLabel . ' ' . $apLink);
                            $rows.= wf_TableRow($cells, 'row3');
                        }
                    } else {
                        $cells = wf_TableCell(__('Connected to AP'), '20%', 'row2');
                        $cells.= wf_TableCell(__('No'));
                        $rows.= wf_TableRow($cells, 'row3');
                    }

                    $result.= wf_TableBody($rows, '100%', 0, '');
                }
            } else {
                $result.=$this->messages->getStyledMessage(__('Strange exeption') . ': CPEID_NOT_EXISTS [' . $assignedCpeId . ']', 'error');
            }
        } else {
            $result.=$this->renderCPEAssignControl($userLogin);
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
        $result.=wf_modalAuto(web_add_icon() . ' ' . __('Create new CPE'), __('Create new CPE'), $this->renderCPECreateForm(), 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME, wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available CPE list'), false, 'ubButton');
        $result.=wf_Link(self::URL_ME . '&rendermap=true', wf_img('skins/ymaps/network.png') . ' ' . __('Map').' '.__('CPE').'/'.__('AP'), false, 'ubButton');
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
        $result.=wf_tag('div', true);

        if (!empty($this->allAP)) {
            foreach ($this->allAP as $io => $each) {
                if (!empty($each['geo'])) {
                    $apName = $each['location'] . ' - ' . $each['ip'] . ' ' . @$this->allSSids[$each['id']];
                    $apLink = trim(wf_Link('?module=switches&edit=' . $each['id'], web_edit_icon() . ' ' . __('Navigate to AP')));
                    $apLink = str_replace('"', '\"', $apLink);
                    $apIcon = sm_MapGoodIcon();
                    if (isset($deadSwitches[$each['ip']])) {
                        $apIcon = sm_MapBadIcon();
                    }
                    $placemarks.=sm_MapAddMark($each['geo'], $apName, $apLink, '', $apIcon);
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
                    $cpeLink = str_replace('"', '\"', $cpeLink);
                    $placemarks.=sm_MapAddMark($cpeCoords, $cpeName, $cpeLink, '', um_MapBuildIcon(1));

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
                                $placemarks.=sm_MapAddLine($cpeCoords, $this->allAP[$each['uplinkapid']]['geo'], $lineColor, '', $lineWidth);
                            }
                        }
                    }
                }
            }
        }

        sm_MapInit($ymconf['CENTER'], $ymconf['ZOOM'], $ymconf['TYPE'], $placemarks, '', $ymconf['LANG']);
        return ($result);
    }

}

?>