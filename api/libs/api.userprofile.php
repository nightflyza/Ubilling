<?php

class UserProfile {

    /**
     * System config alter.ini content as array key=>value
     *
     * @var array
     */
    protected $alterCfg = array();

    /**
     * Current login stargazer user data
     *
     * @var array
     */
    protected $userdata = array();

    /**
     * Array of all available non cached login=>address mappings
     *
     * @var array
     */
    protected $alladdress = array();

    /**
     * Current user phone data as 'phone' and 'mobile' keys array
     *
     * @var array
     */
    protected $phonedata = array();

    /**
     * Current user apartment data array
     *
     * @var array
     */
    protected $aptdata = array();

    /**
     * Array of all available non cached login=>data
     *
     * @var array
     */
    protected $AllUserData = '';

    /**
     * Important profile fields highlighting start
     *
     * @var string
     */
    protected $highlightStart = '';

    /**
     * Important profile fields highlighting end
     *
     * @var string
     */
    protected $highlightEnd = '';

    /**
     * Available preloaded profile plugins
     *
     * @var string
     */
    protected $plugins = '';

    /**
     * Current user login. Must be set in constructor
     *
     * @var string
     */
    protected $login = '';

    /**
     * Current user full address
     *
     * @var string
     */
    protected $useraddress = '';

    /**
     * Current user real name
     *
     * @var string
     */
    protected $realname = '';

    /**
     * Current user phone
     *
     * @var string
     */
    protected $phone = '';

    /**
     * Current user mobile phone
     *
     * @var string
     */
    protected $mobile = '';

    /**
     * Current user contract number
     *
     * @var string
     */
    protected $contract = '';

    /**
     * Current user e-mail address
     *
     * @var string
     */
    protected $mail = '';

    /**
     * Current user tariff speed override value
     *
     * @var string
     */
    protected $speedoverride = '';

    /**
     * MAC address associated with network host record, via current user IP
     *
     * @var string
     */
    protected $mac = '';

    /**
     * Build geo location
     *
     * @var string
     */
    protected $buildgeo = '';

    /**
     * Payment ID of current user
     *
     * @var string
     */
    protected $paymentid = '';

    const EX_EMPTY_LOGIN = 'EMPTY_USERNAME_RECEIVED';
    const EX_EMPTY_USERDATA = 'EMPTY_DATABASE_USERDATA';
    const MAIN_ROW_HEADER_WIDTH = '30%';
    const MAIN_CONTROLS_SIZE = '90px';
    const MAIN_PLUGINS_SIZE = '64';
    const MAIN_OVERLAY_DISTANCE = '150px';
    const MAIN_TABLE_STYLE = 'style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2"';

    /**
     * Creates an user profile object instance and sets/preloads all of required data
     * 
     * @param string $login Existing user login
     * @throws Exception
     */
    public function __construct($login) {
        if (!empty($login)) {
            $this->login = $login;
            $this->loadAlter();
            $this->loadHighlight();
            $this->loadUserdata();
            if (empty($this->userdata)) {
                throw new Exception(self::EX_EMPTY_USERDATA . ' ' . print_r($this, true));
            }
            $this->loadAptdata();
            $this->loadUserAlldata();
            $this->extractUserAllData();
            $this->loadSpeedoverride();
            $this->loadPaymentID();
            $this->loadPlugins();
        } else {
            throw new Exception(self::EX_EMPTY_LOGIN . ' ' . print_r($this, true));
        }
    }

    /**
     * loads current alter.ini config into private prop, once at start
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->alterCfg = $ubillingConfig->getAlter();
    }

    /**
     * loads highlight properties if needed
     * 
     * @return void
     */
    protected function loadHighlight() {
        if (isset($this->alterCfg['HIGHLIGHT_IMPORTANT'])) {
            if ($this->alterCfg['HIGHLIGHT_IMPORTANT']) {
                $this->highlightStart = wf_tag('b', false);
                $this->highlightEnd = wf_tag('b', true);
            }
        }
    }

    /**
     * loads stargazer user data from database in pricate data property
     * 
     * @return void
     */
    protected function loadUserdata() {
        if (!empty($this->login)) {
            $this->userdata = zb_ProfileGetStgData($this->login);
        }
    }

    /**
     * loads All user data from database in pricate data property
     * 
     * @return void
     */
    protected function loadUserAlldata() {
        if (!empty($this->login)) {
            $this->AllUserData = zb_UserGetAllData($this->login);
        }
    }

    /**
     * returns private all userdata property to external scope
     * 
     * @return array
     */
    protected function extractUserAllData() {
        $this->useraddress = $this->AllUserData[$this->login]['fulladress'];
        $this->realname = $this->AllUserData[$this->login]['realname'];
        $this->phone = $this->AllUserData[$this->login]['phone'];
        $this->mobile = $this->AllUserData[$this->login]['mobile'];
        $this->contract = $this->AllUserData[$this->login]['contract'];
        $this->mail = $this->AllUserData[$this->login]['email'];
        //$this->apt = $this->AllUserData[$this->login]['apt'];
        $this->mac = $this->AllUserData[$this->login]['mac'];
        $this->buildgeo = $this->AllUserData[$this->login]['geo'];
    }

    /**
     * loads user apartment data like floor or entrance from database
     * 
     * @return void
     */
    protected function loadAptdata() {
        $this->aptdata = zb_AddressGetAptData($this->login);
    }

    /**
     * loads user speed override from database
     * 
     * @return void
     */
    protected function loadSpeedoverride() {
        $this->speedoverride = zb_UserGetSpeedOverride($this->login);
    }

    /**
     * returns vendor by MAC search control if this enabled in config
     * 
     * @return string
     */
    protected function getSearchmacControl() {
        $result = '';
        if ($this->alterCfg['MACVEN_ENABLED']) {
            $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $this->mac . '&username=' . $this->login . '" width="360" height="160" frameborder="0"');
            $vendorframe .= wf_tag('iframe', true);
            $result = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
        }
        return ($result);
    }

    /**
     * Returns FDB cache search control if FDB_SEARCH_IN_PROFILE option enabled
     * 
     * @return string
     */
    protected function getProfileFdbSearchControl() {
        $result = '';
        if (isset($this->alterCfg['FDB_SEARCH_IN_PROFILE'])) {
            if ($this->alterCfg['FDB_SEARCH_IN_PROFILE']) {
                $result = wf_Link('?module=switchpoller&macfilter=' . $this->mac, wf_img('skins/fdbmacsearch.png', __('Current FDB cache')), false);
            }
        }

        return ($result);
    }

    /**
     * returns catv backlinks if enabled 
     * 
     * @return string
     */
    protected function getCatvBacklinks() {
        $result = '';
        if ($this->alterCfg['UKV_ENABLED']) {
            $catv_backlogin_q = "SELECT * from `ukv_users` WHERE `inetlogin`='" . $this->login . "'";
            $catv_backlogin = simple_query($catv_backlogin_q);
            if (!empty($catv_backlogin)) {
                $catv_backlink = wf_Link("?module=ukv&users=true&showuser=" . $catv_backlogin['id'], web_profile_icon() . ' ' . $catv_backlogin['street'] . ' ' . $catv_backlogin['build'] . '/' . $catv_backlogin['apt'], false);
                $result = $this->addRow(__('UKV'), $catv_backlink);
            } else {
                $result = $this->addRow(__('UKV'), __('No'));
            }
        } else {
            if ($this->alterCfg['CATV_ENABLED']) {
                $catv_backlogin_q = "SELECT * from `catv_users` WHERE `inetlink`='" . $this->login . "'";
                $catv_backlogin = simple_query($catv_backlogin_q);
                if (!empty($catv_backlogin)) {
                    $catv_backlink = wf_Link("?module=catv_profile&userid=" . $catv_backlogin['id'], web_profile_icon() . ' ' . $catv_backlogin['street'] . ' ' . $catv_backlogin['build'] . '/' . $catv_backlogin['apt'], false);
                    $result = $this->addRow(__('CaTV'), $catv_backlink);
                } else {
                    $result = $this->addRow(__('CaTV'), __('No'));
                }
            }
        }
        return ($result);
    }

    /**
     * Returns ADcomments indicator
     * 
     * @return string
     */
    protected function getAdcommentsIndicator() {
        $result = '';
        if (!isset($this->alterCfg['NO_ADCOMMENTS_IN_PROFILE'])) {
            if ($this->alterCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('USERNOTES');
                $result = ' ' . wf_Link('?module=notesedit&username=' . $this->login, $adcomments->getCommentsIndicator($this->login), false, '');
            } else {
                $result = '';
            }
        }
        return ($result);
    }

    /**
     * Returns raw plugins data. Plugins initialization files must be stored in CONFIG_PATH
     * 
     * @return array
     */
    protected function loadPluginsRaw($filename) {
        $result = array();
        if (file_exists(CONFIG_PATH . $filename)) {
            $result = rcms_parse_ini_file(CONFIG_PATH . $filename, true);
        }
        return ($result);
    }

    /**
     * load plugins overlay data
     * 
     * @return string
     */
    protected function loadPluginsOverlay($filename) {
        $plugins = $this->loadPluginsRaw($filename);

        $result = wf_tag('table', false, '', 'width="100%" border="0"');
        $result .= wf_tag('tr', false);
        $result .= wf_tag('td', false, '', 'valign="middle" align="center"');

        if (!empty($plugins)) {
            foreach ($plugins as $modulename => $eachplugin) {
                if (isset($eachplugin['need_option'])) {
                    if (@$this->alterCfg[$eachplugin['need_option']]) {
                        $result .= wf_tag('div', false, '', 'style="width: ' . self::MAIN_OVERLAY_DISTANCE . '; height: ' . self::MAIN_OVERLAY_DISTANCE . '; float: left; font-size: 8pt;"');
                        $result .= wf_Link('?module=' . $modulename . '&username=' . $this->login, wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', ''), false, '');
                        $result .= wf_tag('br') . __($eachplugin['name']);
                        $result .= wf_tag('div', true);
                    }
                } else {
                    $result .= wf_tag('div', false, '', 'style="width: ' . self::MAIN_OVERLAY_DISTANCE . '; height: ' . self::MAIN_OVERLAY_DISTANCE . '; float: left; font-size: 8pt;"');
                    $result .= wf_Link('?module=' . $modulename . '&username=' . $this->login, wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', ''), false, '');
                    $result .= wf_tag('br') . __($eachplugin['name']);
                    $result .= wf_tag('div', true);
                }
            }
        }

        $result .= wf_tag('td', true);
        $result .= wf_tag('tr', true);
        $result .= wf_tag('table', true);

        return($result);
    }

    /**
     * loads pofile plugins if enabled into private plugins property
     * 
     * @return void
     */
    protected function loadPlugins() {
        if (!empty($this->login)) {
            $rawPlugins = $this->loadPluginsRaw('plugins.ini');
            if (!empty($rawPlugins)) {
                foreach ($rawPlugins as $modulename => $eachplugin) {
                    if (isset($eachplugin['overlay'])) {
                        $overlaydata = $this->loadPluginsOverlay($eachplugin['overlaydata']) . wf_delimiter();
                        $this->plugins .= wf_modal(wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', self::MAIN_PLUGINS_SIZE), __($eachplugin['name']), $overlaydata, '', 850, 650);
                    } else {
                        $this->plugins .= wf_Link('?module=' . $modulename . '&username=' . $this->login, wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', self::MAIN_PLUGINS_SIZE), false, '') . wf_delimiter();
                    }
                }
            }
        }
    }

    /**
      Give a little try, give a little more try
      Never fall in line for a fleeting moment
      Be and end all, I am aiming high
      Climb a little higher
     */

    /**
     * calculates PaymentID or extract from database as is
     * 
     * @return void
     */
    protected function loadPaymentID() {
        if ($this->alterCfg['OPENPAYZ_REALID']) {
            $this->paymentid = zb_PaymentIDGet($this->login);
        } else {
            $this->paymentid = ip2int($this->userdata['IP']);
        }
    }

    /**
     * returns private userdata property to external scope
     * 
     * @return array
     */
    public function extractUserData() {
        return ($this->userdata);
    }

    /**
     * returns private useraddress property to external scope
     * 
     * @return array
     */
    public function extractUserAddress() {
        return ($this->useraddress);
    }

    /**
     * returns private realname property to external scope
     *
     * @return array
     */
    public function extractUserRealName() {
        return ($this->realname);
    }

    /**
     * returns private contract property to external scope
     *
     * @return array
     */
    public function extractUserContract() {
        return ($this->contract);
    }

    /**
     * returns prepared main profile body row with two data cells
     * 
     * @param string $header Header cell data that will be displayed left
     * @param string $data   Row data that will be displayed right
     * @param bool   $highlight Highlight row as "important"?
     * 
     * @return string
     */
    protected function addRow($header, $data, $highlight = false, $cellwidth = self::MAIN_ROW_HEADER_WIDTH) {
        if ($highlight) {
            $cells = wf_TableCell($this->highlightStart . $header . $this->highlightEnd, $cellwidth, 'row2');
            $cells .= wf_TableCell($this->highlightStart . $data . $this->highlightEnd, '', 'row3');
        } else {
            $cells = wf_TableCell($header, $cellwidth, 'row2');
            $cells .= wf_TableCell($data, '', 'row3');
        }
        $result = wf_TableRow($cells);
        return ($result);
    }

    /**
     * returns task control for getMainControls
     * 
     * @return string 
     */
    protected function getControl($link, $icon, $title, $shorttitle, $right = '') {
        $result = '';
        if (($right != '')) {
            if (cfr($right)) {
                $result = wf_tag('div', false, 'dashtask', 'style="height:' . self::MAIN_CONTROLS_SIZE . '; width:' . self::MAIN_CONTROLS_SIZE . ';"');
                $result .= wf_Link($link, wf_img_sized($icon, __($title), '', '64'), false, '');
                $result .= wf_tag('br');
                $result .= __($shorttitle);
                $result .= wf_tag('div', true);
            }
        } else {
            $result = wf_tag('div', false, 'dashtask', 'style="height:' . self::MAIN_CONTROLS_SIZE . '; width:' . self::MAIN_CONTROLS_SIZE . ';"');
            $result .= wf_Link($link, wf_img_sized($icon, __($title), '', '64'), false, '');
            $result .= wf_tag('br');
            $result .= __($shorttitle);
            $result .= wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Returns primary prifile controls with most used actions
     * 
     * @param string $login Existing Ubilling user login
     * 
     * @return string
     */
    protected function getMainControls() {
        $result = wf_tag('table', false, '', 'width="100%"  border="0"');
        $result .= wf_tag('tbody');
        $result .= wf_tag('tr');
        $result .= wf_tag('td');

        $result .= $this->getControl('?module=lifestory&username=' . $this->login, 'skins/icon_orb_big.png', 'User lifestory', 'Details', 'LIFESTORY');
        $result .= $this->getControl('?module=traffstats&username=' . $this->login, 'skins/icon_stats_big.png', 'Traffic stats', 'Traffic stats', 'TRAFFSTATS');
        $result .= $this->getControl('?module=addcash&username=' . $this->login . '#profileending', 'skins/icon_cash_big.png', 'Finance operations', 'Cash', 'CASH');
        $result .= $this->getControl('?module=macedit&username=' . $this->login, 'skins/icon_ether_big.png', 'Change MAC', 'Change MAC', 'MAC');
        $result .= $this->getControl('?module=binder&username=' . $this->login, 'skins/icon_build_big.png', 'Address', 'Address', 'BINDER');
        $result .= $this->getControl('?module=tariffedit&username=' . $this->login, 'skins/icon_money_time.png', 'Tariff', 'Tariff', 'TARIFFEDIT');
        $result .= $this->getControl('?module=useredit&username=' . $this->login, 'skins/icon_user_edit_big.png', 'Edit user', 'Edit', 'USEREDIT');
        $result .= $this->getControl('?module=jobs&username=' . $this->login, 'skins/worker.png', 'Jobs', 'Jobs', 'EMPLOYEE');
        $result .= $this->getControl('?module=reset&username=' . $this->login, 'skins/icon_reset_big.png', 'Reset user', 'Reset user', 'RESET');
        //optional asterisk controls
        if (isset($this->alterCfg['ASTERISK_ENABLED'])) {
            if ($this->alterCfg['ASTERISK_ENABLED']) {
                $result .= $this->getControl('?module=asterisk&username=' . $this->login . '#profileending', 'skins/asterisk_small.png', 'Asterisk logging', 'Asterisk', 'ASTERISK');
            }
        }

        //sms history button
        $result .= $this->getSMSHistoryControls();

        $result .= wf_tag('td', true);
        $result .= wf_tag('tbody', true);
        $result .= wf_tag('table', true);

        return($result);
    }

    /**
     * returns user password and masks it if needed
     * 
     * @return string
     */
    protected function getUserPassword() {
        if ($this->alterCfg['PASSWORDSHIDE']) {
            $result = '';
        } else {
            $result = $this->addRow(__('Password'), $this->userdata['Password'], true);
        }
        return ($result);
    }

    /**
     * processing of old user linking with redirects to parent user
     * 
     * @return string
     */
    protected function getUserLinking() {
        $result = '';
        if ($this->alterCfg['USER_LINKING_ENABLED']) {
            $alllinkedusers = cu_GetAllLinkedUsers();
            if (isset($alllinkedusers[$this->login])) {
                $parent_login = cu_GetParentUserLogin($alllinkedusers[$this->login]);
                $result = wf_Link('?module=corporate&userlink=' . $alllinkedusers[$this->login], wf_img('skins/corporate_small.gif') . __('User linked with') . ': ' . @$this->alladdress[$parent_login], false, '');
            }
        }

//check is user corporate parent?
        if ($this->alterCfg['USER_LINKING_ENABLED']) {
            $allparentusers = cu_GetAllParentUsers();
            if (isset($allparentusers[$this->login])) {
                if (($_GET['module'] != 'corporate') AND ( $_GET['module'] != 'addcash')) {
                    rcms_redirect("?module=corporate&userlink=" . $allparentusers[$this->login]);
                }
            }
        }
        return ($result);
    }

    /**
     * Processing of task creation in profile feature
     * 
     * @return string
     */
    protected function getTaskCreateControl() {
//profile task creation icon
        if ($this->alterCfg['CREATETASK_IN_PROFILE']) {
            @$shortAddress = $this->useraddress;
            $createForm = ts_TaskCreateFormProfile($shortAddress, $this->mobile, $this->phone, $this->login);
            $result = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), $createForm, '', '450', '540');
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * gets build location control and neighbors cache lister
     * 
     * @return string
     */
    protected function getBuildControls() {
        $buildLocator = '';
        if ($this->alterCfg['SWYMAP_ENABLED']) {
//getting build locator
            if (isset($this->aptdata['buildid'])) {
                $thisUserBuildGeo = $this->buildgeo;
                if (!empty($thisUserBuildGeo)) {
                    $locatorIcon = wf_img_sized('skins/icon_search_small.gif', __('Find on map'), 10);
                    $buildLocator = ' ' . wf_Link("?module=usersmap&findbuild=" . $thisUserBuildGeo, $locatorIcon, false);
                } else {
                    $buildLocator .= ' ' . wf_Link('?module=usersmap&locfinder=true&placebld=' . $this->aptdata['buildid'], wf_img_sized('skins/ymaps/target.png', __('Place on map'), '10'), false, '');
                }
//and neighbors state cache
                if (!empty($this->aptdata['buildid'])) {
                    if (file_exists('exports/' . $this->aptdata['buildid'] . '.inbuildusers')) {
                        $inbuildNeigbors_raw = file_get_contents('exports/' . $this->aptdata['buildid'] . '.inbuildusers');
                        $inbuildNeigbors_raw = unserialize($inbuildNeigbors_raw);
                        if (!empty($inbuildNeigbors_raw)) {
                            $inbuildNeigborsStat = '';
                            $inbuildNeigborsStat .= wf_TableBody($inbuildNeigbors_raw['rows'], '100%', '0', 'sortable');
                            $inbuildNeigborsStat .= wf_tag('br') . __('Active') . ' ' . $inbuildNeigbors_raw['aliveusers'] . '/' . $inbuildNeigbors_raw['userscount'];
                            $buildNeighborsIcon = wf_img_sized('skins/icon_build.gif', __('Neighbours'), 12);
                            $buildLocator .= ' ' . wf_modal($buildNeighborsIcon, __('Neighbours'), $inbuildNeigborsStat, '', 400, 400);
                        }
                    }
                }
            }
        }
        return ($buildLocator);
    }

    /**
     * returns passport data controls
     * 
     * @return string
     */
    protected function getPassportDataControl() {
        $result = '';
        if ($this->alterCfg['PASSPDATA_IN_PROFILE']) {
            $passportdata = web_UserPassportDataShow($this->login);
            $result = ' ' . wf_modal(wf_img_sized('skins/icon_passport.gif', __('Passport data'), '', 10), __('Passport data'), $passportdata, '', '600', '300');
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * returns user cash data with round and colorize if needed
     * 
     * @return string
     */
    protected function getUserCash() {
//rounding cash if needed
        if ($this->alterCfg['ROUND_PROFILE_CASH']) {
            $Cash = web_roundValue($this->userdata['Cash'], 2);
        } else {
            $Cash = $this->userdata['Cash'];
        }

//optional cash colorizing
        if (isset($this->alterCfg['COLORIZE_PROFILE_CASH'])) {
            if ($this->alterCfg['COLORIZE_PROFILE_CASH']) {
                if ($this->userdata['Cash'] >= 0) {
                    $color = '#0e7600';
                } else {
                    $color = '#c80000';
                }
                $Cash = wf_tag('font', false, '', 'color="' . $color . '"') . $Cash . wf_tag('font', true);
            }
        }
        return ($Cash);
    }

    /**
     * gets and formats credit expiration date
     * 
     * @retun string
     */
    protected function getUserCreditExpire() {
//user credit expiration date
        if ($this->userdata['CreditExpire'] != 0) {
            $result = date("Y-m-d", $this->userdata['CreditExpire']);
        } else {
            if ($this->userdata['Credit'] > 0) {
                $result = __('Forever and ever');
            } else {
                $result = __('No');
            }
        }
        return ($result);
    }

    /**
     * Returns user connection details with optional controls inside if enabled
     * 
     * @return string
     */
    protected function getUserConnectionDetails() {
        $result = '';
        if ($this->alterCfg['CONDET_IN_PROFILE']) {
            if ($this->alterCfg['CONDET_ENABLED']) {
                $conDet = new ConnectionDetails();
                $data = $conDet->renderData($this->login);
                if (cfr('CONDET')) {
                    $data .= ' ' . wf_Link('?module=condetedit&username=' . $this->login, wf_img_sized('skins/cableseal_small.png', __('Change') . ' ' . __('Connection details'), '12'), false);
                }
                $result = $this->addRow(__('Connection details'), $data);
            }
        }
        return ($result);
    }

    /**
     * Renders users available deal with it tasks notification
     * 
     * @return string
     */
    protected function getUserDealWithItNotification() {
        $result = '';
        if ($this->alterCfg['DEALWITHIT_IN_PROFILE']) {
            $notification = '';
            $query = "SELECT `login`,`action` from `dealwithit` WHERE `login`='" . $this->login . "';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                $actionNames = array(
                    'addcash' => __('Add cash'),
                    'corrcash' => __('Correct saldo'),
                    'setcash' => __('Set cash'),
                    'credit' => __('Change') . ' ' . __('credit'),
                    'creditexpire' => __('Change') . ' ' . __('credit expire date'),
                    'tariffchange' => __('Change') . ' ' . __('tariff'),
                    'tagadd' => __('Add tag'),
                    'tagdel' => __('Delete tag'),
                    'freeze' => __('Freeze user'),
                    'unfreeze' => __('Unfreeze user'),
                    'reset' => __('User reset'),
                    'setspeed' => __('Change speed override'),
                    'down' => __('Set user down'),
                    'undown' => __('Enable user'),
                    'ao' => __('Enable AlwaysOnline'),
                    'unao' => __('Disable AlwaysOnline')
                );

                $actionIcons = array(
                    'addcash' => 'skins/icon_dollar.gif',
                    'corrcash' => 'skins/icon_dollar.gif',
                    'setcash' => 'skins/icon_dollar.gif',
                    'credit' => 'skins/icon_credit.gif',
                    'creditexpire' => 'skins/icon_calendar.gif',
                    'tariffchange' => 'skins/icon_tariff.gif',
                    'tagadd' => 'skins/tagiconsmall.png',
                    'tagdel' => 'skins/tagiconsmall.png',
                    'freeze' => 'skins/icon_passive.gif',
                    'unfreeze' => 'skins/icon_passive.gif',
                    'reset' => 'skins/refresh.gif',
                    'setspeed' => 'skins/icon_speed.gif',
                    'down' => 'skins/icon_down.gif',
                    'undown' => 'skins/icon_down.gif',
                    'ao' => 'skins/icon_online.gif',
                    'unao' => 'skins/icon_online.gif'
                );

                foreach ($all as $io => $each) {
                    if ((isset($actionNames[$each['action']])) AND ( isset($actionIcons[$each['action']]))) {
                        $icon = wf_img_sized($actionIcons[$each['action']], $actionNames[$each['action']], '10', '10');
                        $notification .= wf_Link('?module=pl_dealwithit&username=' . $this->login, $icon, false) . ' ';
                    } else {
                        $notification .= $each['action'] . ' ';
                    }
                }
            }
            $result = $this->addRow(__('Held jobs for this user'), $notification);
        }
        return ($result);
    }

    /**
     * gets and preformats last activity time
     * 
     * @return string
     */
    protected function getUserLat() {
        $result = '';
        if (isset($this->alterCfg['PROFILE_LAT'])) {
            if ($this->alterCfg['PROFILE_LAT']) {
                if ($this->userdata['LastActivityTime'] != 0) {
                    $data = date("Y-m-d H:i:s", $this->userdata['LastActivityTime']);
                    $result = $this->addRow(__('Last activity time'), $data);
                } else {
                    $result = $this->addRow(__('Last activity time'), __('No'));
                }
            }
        }
        return ($result);
    }

    /**
     * Returns Optional contract date row
     * 
     * @return string
     */
    protected function getContractDate() {
        $result = '';
        if (isset($this->alterCfg['CONTRACTDATE_IN_PROFILE'])) {
            if ($this->alterCfg['CONTRACTDATE_IN_PROFILE']) {
                if (!empty($this->contract)) {
                    $allContractDates = zb_UserContractDatesGetAll();
                    $contractDate = (isset($allContractDates[$this->contract])) ? $allContractDates[$this->contract] : __('No');
                    $result .= $this->addRow(__('Contract date'), $contractDate);
                } else {
                    $result .= $this->addRow(__('Contract date'), __('No'));
                }
            }
        }
        return ($result);
    }

    /**
     * gets switch assing profile controls
     * 
     * @return string
     */
    protected function getSwitchAssignControls() {
//switchport section
        if ($this->alterCfg['SWITCHPORT_IN_PROFILE']) {
            $result = web_ProfileSwitchControlForm($this->login);
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * Returns user Vlan assign controls
     * 
     * @return string
     */
    protected function getVlanAssignControls() {
        if ($this->alterCfg['VLAN_IN_PROFILE']) {
            $result = web_ProfileVlanControlForm($this->login);
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * Returns Vlan online detection form
     * 
     * @return string
     */
    protected function getVlanOnline() {
        $result = '';
        if ($this->alterCfg['VLAN_ONLINE_IN_PROFILE']) {
            $vlanGen = new VlanGen();
            $vlan = $vlanGen->GetVlan($this->login);
            if (!empty($vlan)) {
                $history = new VlanMacHistory;
                $cells = wf_TableCell(__('Detect online'), '30%', 'row2');
                $cells .= wf_TableCell($history->GetUserVlanOnline($this->login, $vlanGen->GetVlan($this->login)));
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', '0');
            }
        }
        return ($result);
    }

    /**
     * Renders PON signal from cache
     * 
     * @return string
     */
    protected function getPonSignalControl() {
        $result = '';
        $searched = __('No');
        $sigColor = '#000000';
        if ($this->alterCfg['SIGNAL_IN_PROFILE']) {
            $query = "SELECT `id`,`mac`,`oltid`,`serial` FROM `pononu` WHERE `login`='" . $this->login . "'";
            $onu_data = simple_query($query);
            if (!empty($onu_data)) {
                $availCacheData = rcms_scandir(PONizer::SIGCACHE_PATH, $onu_data['oltid'] . "_" . PONizer::SIGCACHE_EXT);
                if (!empty($availCacheData)) {
                    foreach ($availCacheData as $io => $each) {
                        $raw = file_get_contents(PONizer::SIGCACHE_PATH . $each);
                        $raw = unserialize($raw);
                        foreach ($raw as $mac => $signal) {
                            if ($mac == $onu_data['mac'] or $mac == $onu_data['serial']) {
                                if (($signal > 0) OR ( $signal < -25)) {
                                    $sigColor = '#ab0000';
                                } else {
                                    $sigColor = '#005502';
                                }
                                $searched = $signal;
                            }
                        }
                    }
                }
                $cells = wf_TableCell(__("ONU Signal"), '30%', 'row2');
                $cells .= wf_TableCell(wf_tag('strong') . wf_tag('font color=' . $sigColor, false) . $searched . wf_tag('font', true) . wf_tag('strong', true) .
                                        wf_nbsp(2) . wf_Link('?module=ponizer&editonu=' . $onu_data['id'], web_edit_icon()) );
                $rows = wf_TableRow($cells, 'row3');
                $result = wf_TableBody($rows, '100%', '0');
            }
        }
        return($result);
    }

    /**
     * returns DN online detect aka "star"
     * 
     * @return string
     */
    protected function getUserOnlineDN() {
        $result = '';
        if ($this->alterCfg['DN_ONLINE_DETECT']) {
            if (file_exists(DATA_PATH . 'dn/' . $this->login)) {
                $onlineDnFlag = wf_img_sized('skins/icon_star.gif', '', '', '12') . ' ' . __('Yes');
            } else {
                $onlineDnFlag = wf_img_sized('skins/icon_nostar.gif', '', '', '12') . ' ' . __('No');
            }

            $result = $this->addRow(__('Online'), $onlineDnFlag);
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * gets corporate users handling controls
     * 
     * @return string
     */
    protected function getCorporateControls() {
        $result = '';
        if ($this->alterCfg['CORPS_ENABLED']) {
            $corps = new Corps();
            $corpsCheck = $corps->userIsCorporate($this->login);
            if ($corpsCheck) {
                $corpPreview = $corps->corpPreview($corpsCheck);
                $corpPreviewControl = wf_modal(wf_img('skins/folder_small.png', __('Show')), __('Preview'), $corpPreview, '', '800', '600');
                $result = $this->addRow(__('User type'), __('Corporate user') . ' ' . $corpPreviewControl);
            } else {
                $result = $this->addRow(__('User type'), __('Private user'));
            }
        }
        return ($result);
    }

    /**
     * Returns mobile controls if required
     * 
     * @return string
     */
    protected function getMobileControls() {
        global $ubillingConfig;
        $result = '';

        if (isset($this->alterCfg['EASY_SMS'])) {
            if ($this->alterCfg['EASY_SMS']) {
                if ($this->alterCfg['SENDDOG_ENABLED']) {
                    //perform sending
                    if (wf_CheckPost(array('neweasysmsnumber', 'neweasysmstext'))) {
                        $sms = new UbillingSMS();
                        $targetNumber = $_POST['neweasysmsnumber'];
                        $targetText = $_POST['neweasysmstext'];
                        $translitFlag = (wf_CheckPost(array('neweasysmstranslit'))) ? true : false;
                        $SMSSrvID = ( wf_CheckPost(array('preferredsmssrvid')) ) ? $_POST['preferredsmssrvid'] : '';
                        $QueueFile = $sms->sendSMS($targetNumber, $targetText, $translitFlag, 'EASYSMS');
                        $sms->setDirection($QueueFile, '', '', $SMSSrvID);
                        rcms_redirect('?module=userprofile&username=' . $this->login);
                    }

                    if (!empty($this->mobile)) {
                        //cleaning mobile number
                        $userMobile = trim($this->mobile);
                        $userMobile = str_replace(' ', '', $userMobile);
                        $userMobile = str_replace('-', '', $userMobile);
                        $userMobile = str_replace('(', '', $userMobile);
                        $userMobile = str_replace(')', '', $userMobile);
                        if (isset($this->alterCfg['REMINDER_PREFIX'])) {
                            $prefix = $this->alterCfg['REMINDER_PREFIX']; //trying to support defferent formats
                            if (!empty($prefix)) {
                                $userMobile = str_replace($prefix, '', $userMobile);
                                $userMobile = $prefix . $userMobile;
                            }
                            $sendInputs = '';
                            $sendInputs .= wf_TextInput('neweasysmsnumber', __('Mobile'), $userMobile, true, '15', 'mobile');
                            $sendInputs .= wf_TextArea('neweasysmstext', '', '', true, '40x5');
                            $sendInputs .= wf_CheckInput('neweasysmstranslit', __('Forced transliteration'), true, true);
                            $sendInputs .= wf_tag('br');
                            $sendInputs .= wf_Submit(__('Send SMS'));

                            if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
                                $SMSDirections = new SMSDirections();
                                $SMSSrvID = $SMSDirections->getDirection('user_login', $this->login);
                                $sendInputs .= wf_HiddenInput('preferredsmssrvid', $SMSSrvID);
                            }

                            $sendingForm = wf_Form('', 'POST', $sendInputs, 'glamour');

                            $result = ' ' . wf_modalAuto(wf_img_sized('skins/icon_sms_micro.gif', __('Send SMS'), '10', '10'), __('Send SMS'), $sendingForm, '');
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Checks agent assing and return controls if needed
     * 
     * @return string
     */
    protected function getAgentsControls() {
        $result = '';
        if ($this->alterCfg['AGENTS_ASSIGN'] == 2) {
            $assignedAgentData = zb_AgentAssignedGetDataFast($this->login, $this->useraddress);
            $result = $this->addRow(__('Contrahent name'), @$assignedAgentData['contrname']);
        }
        return ($result);
    }

    /**
     * signup prices controller
     * 
     * @return string
     */
    protected function getSignupPricing() {
        $result = '';
        if (isset($this->alterCfg['SIGNUP_PAYMENTS']) && !empty($this->alterCfg['SIGNUP_PAYMENTS'])) {
            $result = $this->addRow(__('Signup paid'), zb_UserGetSignupPricePaid($this->login) . '/' . zb_UserGetSignupPrice($this->login));
        }
        return ($result);
    }

    /**
     * returns easy credit controller if feature is enabled
     * 
     * @return
     */
    protected function getEasyCreditController() {
        $result = '';
        if ($this->alterCfg['EASY_CREDIT']) {
            if ((cfr('CREDIT')) AND ( cfr('CREDITEXPIRE'))) {
                $result = web_EasyCreditForm($this->login, $this->userdata['Cash'], $this->userdata['Credit'], $this->userdata['Tariff'], $this->alterCfg['EASY_CREDIT']);
            } else {
                $result = '';
            }
        }
        return ($result);
    }

    /**
     * extended network pools controller
     * 
     * @return string
     */
    protected function getExtNetsControls() {
        $result = '';
        if ($this->alterCfg['NETWORKS_EXT']) {
            $extNets = new ExtNets();
//pool linking controller
            if (wf_CheckPost(array('extnetspoollinkid', 'extnetspoollinklogin'))) {
                $extNets->poolLinkLogin($_POST['extnetspoollinkid'], $_POST['extnetspoollinklogin']);
                rcms_redirect('?module=userprofile&username=' . $_POST['extnetspoollinklogin']);
            }
            $result = $extNets->poolsExtractByLogin($this->login);
            $result .= ' ' . wf_modal(wf_img('skins/icon_ip.gif'), __('IP associated with pool'), $extNets->poolLinkingForm($this->login), '', '500', '120');
        }
        return ($result);
    }

    /**
     * Photostorage controls
     * 
     * @return string
     */
    protected function getPhotostorageControls() {
        $result = '';
        if ($this->alterCfg['PHOTOSTORAGE_ENABLED']) {
            $photostorageUrl = '?module=photostorage&scope=USERPROFILE&itemid=' . $this->login . '&mode=list';
            $result .= ' ' . wf_Link($photostorageUrl, wf_img_sized('skins/photostorage.png', __('Upload images'), '10', '10'), false);
        }
        return ($result);
    }

    /**
     * If branches enabled - returns user current branch name
     * 
     * @return string
     */
    protected function getUserBranchName() {
        $result = '';
        if ($this->alterCfg['BRANCHES_ENABLED']) {
            global $branchControl;
            $branchName = $branchControl->userGetBranchName($this->login);
            $result = $this->addRow(__('Branch'), $branchName);
        }
        return ($result);
    }

    /**
     * Cemetery controls 
     * 
     * @return string
     */
    protected function getCemeteryControls() {
        $result = '';
        if (isset($this->alterCfg['CEMETERY_ENABLED'])) {
            if ($this->alterCfg['CEMETERY_ENABLED']) {
                $cemetery = new Cemetery();
//integrated controller
                if (wf_CheckPost(array('cemeterysetasundead'))) {
                    $cemetery->setUndead($_POST['cemeterysetasundead']);
                    rcms_redirect('?module=userprofile&username=' . $this->login);
                }

                if (wf_CheckPost(array('cemeterysetasdead'))) {
                    $cemetery->setDead($_POST['cemeterysetasdead']);
                    rcms_redirect('?module=userprofile&username=' . $this->login);
                }

//activity view
                if ($cemetery->isUserDead($this->login)) {
                    $log = wf_modalAuto(wf_img_sized('skins/dead_icon.png', '', '12', '12'), __('User lifestory'), $cemetery->renderCemeteryLog($this->login));
                    $result = ' / ' . __('Subscriber is not connected') . ' ' . $log;
                } else {
                    $log = wf_modalAuto(wf_img_sized('skins/pigeon_icon.png', '', '12', '12'), __('User lifestory'), $cemetery->renderCemeteryLog($this->login));
                    $result = ' / ' . __('Subscriber is connected') . ' ' . $log;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns tariff info container for data display
     * 
     * @return string
     */
    protected function getTariffInfoContrainer() {
        $result = '';
        if (@$this->alterCfg['TARIFFINFO_IN_PROFILE']) {
            $containerId = 'TARIFFINFO_CONTAINER';
            $result = wf_tag('div', false, '', 'id="' . $containerId . '" style="display:block;"') . wf_tag('div');
        }
        return ($result);
    }

    /**
     * Returns tariff info ajax controls
     * 
     * @param string $tariffName
     * @return string
     */
    protected function getTariffInfoControls($tariffName) {
        $result = '';
        if (@$this->alterCfg['TARIFFINFO_IN_PROFILE']) {
            $containerId = 'TARIFFINFO_CONTAINER';
            if (!empty($tariffName)) {
                $result .= wf_AjaxLoader();
                $result .= wf_AjaxLink('?module=tariffinfo&tariff=' . $tariffName, wf_img('skins/tariffinfo.gif', __('Tariff info')), $containerId, false, '');
            }
        }
        return ($result);
    }

    /**
     * Returns WiFi CPE user controls
     * 
     * @return string
     */
    protected function getUserCpeControls() {
        $result = '';

        if ($this->alterCfg['WIFICPE_ENABLED']) {
            $wcpeFlag = true;
            if (isset($this->alterCfg['WIFICPE_TARIFFMASK'])) {
                if (!empty($this->alterCfg['WIFICPE_TARIFFMASK'])) {
                    if (!ispos($this->userdata['Tariff'], $this->alterCfg['WIFICPE_TARIFFMASK'])) {
                        $wcpeFlag = false;
                    }
                }
            }

            if ($wcpeFlag) {
                $wcpe = new WifiCPE();
                $result .= $wcpe->renderCpeUserControls($this->login, $this->AllUserData);
            }
        }

        return ($result);
    }

    /**
     * Renders additional user mobile numbers
     * 
     * @return string
     */
    protected function getMobilesExtControl() {
        $result = '';
        if (isset($this->alterCfg['MOBILES_EXT'])) {
            if ($this->alterCfg['MOBILES_EXT']) {
                $extMob = new MobilesExt();
                $allExtRaw = $extMob->getUserMobiles($this->login);
                $allExt = array();
                if (!empty($allExtRaw)) {
                    foreach ($allExtRaw as $io => $each) {
                        $allExt[] = $each['mobile'];
                    }
                }

                if (!empty($allExt)) {
                    $additionalNumbers = implode(', ', $allExt);
                } else {
                    $additionalNumbers = '';
                }
                $fastLinkControl = (cfr('MOBILE')) ? wf_Link('?module=mobileedit&username=' . $this->login, wf_img_sized('skins/add_icon.png', __('Add new'), '10', '10'), false) : '';
                $result .= $this->addRow(__('Additional mobile') . ' ' . $fastLinkControl, $additionalNumbers);
            }
        }
        return ($result);
    }

    /**
     * Returns cached user districts list row
     * 
     * @return string
     */
    protected function getDistrictControls() {
        $result = '';
        if ((isset($this->alterCfg['DISTRICTS_ENABLED'])) AND ( $this->alterCfg['DISTRICTS_ENABLED'])) {
            if ((isset($this->alterCfg['DISRTICTS_IN_PROFILE'])) AND ( $this->alterCfg['DISRTICTS_IN_PROFILE'])) {
                $districts = new Districts(false);
                $result .= $this->addRow(__('Districts'), $districts->getUserDistrictsListFast($this->login), false);
            }
        }
        return ($result);
    }

    /**
     * Returns user contracts row
     * 
     * @return string
     */
    protected function getContractControls() {
        if (isset($this->alterCfg['CONTRACT_PROFILE_HIDE']) AND $this->alterCfg['CONTRACT_PROFILE_HIDE']) {
            $result = '';
        } else {
            $result = $this->addRow(__('Contract'), $this->contract, false);
        }
        return ($result);
    }

    /**
     * Returns FreeMb profile row
     * 
     * @return string
     */
    protected function getFreeMbControls() {
        $result = '';
        if (isset($this->alterCfg['FREEMB_IN_PROFILE'])) {
            if ($this->alterCfg['FREEMB_IN_PROFILE']) {
                $result = $this->addRow(__('Prepayed traffic'), $this->userdata['FreeMb']);
            }
        }
        return ($result);
    }

    /**
     * Returns SMS history button if appropriate alter.ini options are set
     *
     * @return string
     */
    protected function getSMSHistoryControls() {
        $result = '';

        if (isset($this->alterCfg['SMS_HISTORY_ON']) AND $this->alterCfg['SMS_HISTORY_ON'] AND cfr('SMSHIST')) {
            $SMSHist = new SMSHistory();
            $JQDT = $SMSHist->renderJQDT($this->login);

            $result = wf_tag('div', false, 'dashtask', 'style="height:' . self::MAIN_CONTROLS_SIZE . '; width:' . self::MAIN_CONTROLS_SIZE . ';"');
            $result .= wf_modal(wf_img_sized('skins/taskbar/sms_hist_big.png', __('SMS messages history'), '', self::MAIN_PLUGINS_SIZE), __('SMS messages history for current user') . '  ' . $this->login, $JQDT, '', '1000', '400');
            $result .= wf_tag('br');
            $result .= __('SMS messages history');
            $result .= wf_tag('div', true);
        }

        return $result;
    }

    /**
     * Returns SMS services selector if appropriate alter.ini option is set
     *
     * @return string
     */
    protected function getSMSserviceSelectorControls() {
        global $ubillingConfig;
        $row = '';

        if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
            if ( wf_CheckPost(array('ajax')) and wf_CheckPost(array('action')) == 'BindSMSSrv' ) {
                if ( wf_CheckPost(array('createrec')) ) {
                    $query = "INSERT INTO `sms_services_relations` (`sms_srv_id`, `user_login`)
                                  VALUES ('" . $_POST['smssrvid'] . "', '" . $_GET['username'] . "')";
                    nr_query($query);
                } else {
                    simple_update_field('sms_services_relations', 'sms_srv_id', $_POST['smssrvid'], "WHERE `user_login`='" . $_GET['username'] . "' ");
                }

                log_register("Prefered SMS service changed from [" . $_POST['oldsmssrvid'] . "] to [" . $_POST['smssrvid'] . "] for user (" . $_GET['username'] . ")");
            }

            $preferredSMSSrv = zb_getUsersPreferredSMSService($this->userdata['login']);
            $preferredSMSSrvId = $preferredSMSSrv[0];

            $row.= $this->addRow(__('Preferred SMS service'), wf_Selector('sms_srv', zb_getSMSServicesList(), '', $preferredSMSSrvId, false, false, 'related_sms_srv') .
                wf_HiddenInput('sms_srv_create', empty($preferredSMSSrvId), 'related_sms_srv_create') .
                wf_tag('span', false, '', 'id="sms_srv_change_flag" style="color: darkred"') .
                wf_tag('span', true)
            );
            $row.= wf_tag('script', false, '', 'type="text/javascript"');
            $row.= '$(\'#related_sms_srv\').change(function() {
                            var SMSSrvID = $(this).val(); 
                            var CreateRec = $(\'#related_sms_srv_create\').val();
                            
                            $.ajax({
                                    type: "POST",
                                    url: "?module=userprofile&username=' . $this->userdata['login'] . '",
                                    data: { action: "BindSMSSrv",
                                            ajax:true,                                            
                                            smssrvid: SMSSrvID,                                                                                                                 
                                            ' . (( empty($preferredSMSSrvId) ) ? 'createrec: CreateRec, ' : '') . '
                                            oldsmssrvid: "' . $preferredSMSSrvId . '"
                                           },
                                    success: function() {
                                                $(\'#sms_srv_change_flag\').text(" ' . __('Changed') . '");
                                             }
                                });
                        });
                        ';
            $row .= wf_tag('script', true);
        }

        return $row;
    }

    /**
      Брат, братан, братишка Когда меня отпустит?
     */

    /**
     * Renders user profile with all loaded data
     * 
     * @return string
     */
    public function render() {
//all configurable features must be received via getters
        $profile = '';

//activity and other flags
        $passiveicon = ($this->userdata['Passive']) ? wf_img_sized('skins/icon_passive.gif', '', '', '12') . ' ' : '';
        $downicon = ($this->userdata['Down']) ? wf_img_sized('skins/icon_down.gif', '', '', '12') . ' ' : '';
        $activity = ($this->userdata['Cash'] < '-' . $this->userdata['Credit']) ? wf_img_sized('skins/icon_inactive.gif', '', '', '12') . ' ' . __('No') : wf_img_sized('skins/icon_active.gif', '', '', '12') . ' ' . __('Yes');

// user linking controller
        $profile .= $this->getUserLinking();

        $profile .= wf_tag('table', false, '', self::MAIN_TABLE_STYLE); //external profile container
        $profile .= wf_tag('tbody', false);

        $profile .= wf_tag('tr', false);

        $profile .= wf_tag('td', false, '', 'valign="top"');
        $profile .= wf_tag('table', false, '', self::MAIN_TABLE_STYLE); //main profile data
        $profile .= wf_tag('tbody', false);

//address row and controls
        if (!$this->alterCfg['CITY_DISPLAY']) {
            $renderAddress = $this->AllUserData[$this->login]['cityname'] . ' ' . $this->useraddress;
        } else {
            $renderAddress = $this->useraddress;
        }
        $profile .= $this->addRow(__('Full address') . $this->getTaskCreateControl(), $renderAddress . $this->getBuildControls());
//apt data like floor and entrance row
        $profile .= $this->addRow(__('Entrance') . ', ' . __('Floor'), @$this->aptdata['entrance'] . ' ' . @$this->aptdata['floor']);
//user districts row
        $profile .= $this->getDistrictControls();
//realname row
        $profile .= $this->addRow(__('Real name') . $this->getPhotostorageControls() . $this->getPassportDataControl(), $this->realname, true);
//contract row
        $profile .= $this->getContractControls();
//contract date row
        $profile .= $this->getContractDate();
//assigned agents row
        $profile .= $this->getAgentsControls();
//current user branch
        $profile .= $this->getUserBranchName();
//old corporate users aka userlinking
        $profile .= $this->getCorporateControls();
//phone     
        $profile .= $this->addRow(__('Phone'), $this->phone);
//and mobile data rows
        $profile .= $this->addRow(__('Mobile') . $this->getMobileControls(), $this->mobile);
//additional mobile data
        $profile .= $this->getMobilesExtControl();
//Email data row
        $profile .= $this->addRow(__('Email'), $this->mail);
//payment ID data
        $profile .= $this->addRow(__('Payment ID'), $this->paymentid, true);
//LAT data row
        $profile .= $this->getUserLat();
//login row
        $profile .= $this->addRow(__('Login'), $this->userdata['login'], true);
//password row
        $profile .= $this->getUserPassword();
//User IP data and extended networks controls if available
        $profile .= $this->addRow(__('IP'), $this->userdata['IP'] . $this->getExtNetsControls(), true);
//MAC address row
        $profile .= $this->addRow(__('MAC') . ' ' . $this->getSearchmacControl() . ' ' . $this->getProfileFdbSearchControl(), $this->mac);
//User tariff row
        $profile .= $this->addRow(__('Tariff') . $this->getTariffInfoControls($this->userdata['Tariff']), $this->userdata['Tariff'] . $this->getTariffInfoContrainer(), true);
//Tariff change row
        $profile .= $this->addRow(__('Planned tariff change') . $this->getTariffInfoControls($this->userdata['TariffChange']), $this->userdata['TariffChange']);
//old CaTv backlink if needed
        $profile .= $this->getCatvBacklinks();
//Speed override row
        $profile .= $this->addRow(__('Speed override'), $this->speedoverride);
// signup pricing row
        $profile .= $this->getSignupPricing();
//User current cash row
        $profile .= $this->addRow(__('Balance'), $this->getUserCash(), true);
//User credit row & easycredit control if needed
        $profile .= $this->addRow(__('Credit') . ' ' . $this->getEasyCreditController(), $this->userdata['Credit'], true);
//credit expire row
        $profile .= $this->addRow(__('Credit expire'), $this->getUserCreditExpire());
//Prepayed traffic
        $profile .= $this->getFreeMbControls();
//finance activity row
        $profile .= $this->addRow(__('Active') . $this->getCemeteryControls(), $activity);
//DN online detection row
        $profile .= $this->getUserOnlineDN();
//Always online flag row
        $profile .= $this->addRow(__('Always Online'), web_trigger($this->userdata['AlwaysOnline']));
//Detail stats flag row
        $profile .= $this->addRow(__('Disable detailed stats'), web_trigger($this->userdata['DisabledDetailStat']));
//Frozen aka passive flag row
        //passive time detection
        $passiveTimeLabel = '';
        if ($this->userdata['Passive']) {
            if ($this->userdata['PassiveTime']) {
                $passiveTimeLabel = wf_AjaxLoader();
                $passiveTimeLink = wf_AjaxLink('?module=passiveinfo&username='.$this->login, ' (' . zb_formatTime($this->userdata['PassiveTime']) . ')', 'passivedatecontainer');
                $passiveTimeLabel .= wf_AjaxContainerSpan('passivedatecontainer','',$passiveTimeLink);
                
            }
        }
        $profile .= $this->addRow(__('Freezed'), $passiveicon . web_trigger($this->userdata['Passive']) . $passiveTimeLabel, true);

        if (isset($this->alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) && $this->alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) {
            $FrozenAllQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $this->userdata['login'] . "';";
            $FrozenAll = simple_queryall($FrozenAllQuery);

            if (!empty($FrozenAll)) {
                foreach ($FrozenAll as $usr => $usrlogin) {
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Freeze days total amount'), $usrlogin['freeze_days_amount'], false, '50%');
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Freeze days used'), $usrlogin['freeze_days_used'], false, '50%');
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Freeze days available'), $usrlogin['freeze_days_amount'] - $usrlogin['freeze_days_used'], false, '50%');
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Workdays amount to restore freeze days'), $usrlogin['work_days_restore'], false, '50%');
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Days worked after freeze days used up'), $usrlogin['days_worked'], false, '50%');
                    $profile .= $this->addRow("&nbsp&nbsp&nbsp&nbsp" . __('Workdays left to restore'), $usrlogin['work_days_restore'] - $usrlogin['days_worked'], false, '50%');
                }
            }
        }

//Disable aka Down flag row
        $profile .= $this->addRow(__('Disabled'), $downicon . web_trigger($this->userdata['Down']), true);

        $profile .= $this->getSMSserviceSelectorControls();

//Deal with it available tasks notification
        $profile .= $this->getUserDealWithItNotification();
//Connection details  row
        $profile .= $this->getUserConnectionDetails();
//User notes row
        $profile .= $this->addRow(__('Notes'), zb_UserGetNotes($this->login) . $this->getAdcommentsIndicator());

        $profile .= wf_tag('tbody', true);
        $profile .= wf_tag('table', true);
        $profile .= wf_tag('td', true); //end of main profile container 


        $profile .= wf_tag('td', false, '', 'valign="top" width="10%"'); //profile plugins container
        $profile .= $this->plugins;
        $profile .= wf_tag('td', true); // end of plugins container

        $profile .= wf_tag('tr', true); // close profile+plugins row

        $profile .= wf_tag('tbody', true);
        $profile .= wf_tag('table', true); //end of all profile container
//profile switch port controls
        $profile .= $this->getSwitchAssignControls();
//profile onu signal controls
        $profile .= $this->getPonSignalControl();
//profile vlan controls
        $profile .= $this->getVlanAssignControls();
//profile vlan online
        $profile .= $this->getVlanOnline();
//profile CPE controls
        $profile .= $this->getUserCpeControls();

//Custom filelds display
        $profile .= cf_FieldShower($this->login);
//Tags add control and exiting tags listing
        if (cfr('TAGS')) {
            $profile .= wf_Link('?module=usertags&username=' . $this->login, web_add_icon(__('Tags')), false);
        }
        $profile .= stg_show_user_tags($this->login);

//main profile controls here
        $profile .= $this->getMainControls();

//Profile ending anchor for addcash links scroll
        $profile .= wf_tag('a', false, '', 'id="profileending"') . wf_tag('a', true);

        return($profile);
    }

}

?>
