<?php

/**
 * User profile loading and rendering class
 */
class UserProfile {

    /**
     * System config alter.ini content as array key=>value
     *
     * @var array
     */
    protected $alterCfg = array();

    /**
     * UbillingConfig object placeholder
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Current login stargazer user data
     *
     * @var array
     */
    protected $userdata = array();

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

    /**
     * Contains preloaded additional mobiles numbers
     *
     * @var array
     */
    protected $mobilesExt = array();

    /**
     * Contains custom profile fields instance for current user
     *
     * @var object
     */
    protected $customFields = '';

    /**
     * Contains user assigned culpa if it exists
     *
     * @var string
     */
    protected $culpa = '';

    /**
     * Path to SMS template for user quick credentials sending
     *
     * @var string
     */
    protected $esmsTemplatePath = 'content/documents/easy_sms_template/easy_sms.tpl';

    const EX_EMPTY_LOGIN = 'EMPTY_USERNAME_RECEIVED';
    const EX_EMPTY_USERDATA = 'EMPTY_DATABASE_USERDATA';
    const MAIN_ROW_HEADER_WIDTH = '30%';
    const MAIN_CONTROLS_SIZE = '90px';
    const MAIN_PLUGINS_SIZE = '64';
    const MAIN_OVERLAY_DISTANCE = '150px';
    const MAIN_TABLE_STYLE = 'style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2"';
    const URL_PROFILE = '?module=userprofile&username=';

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
                throw new Exception(self::EX_EMPTY_USERDATA);
            }
            $this->loadAptdata();
            $this->loadUserAlldata();
            $this->extractUserAllData();
            $this->loadSpeedoverride();
            $this->loadPaymentID();
            $this->loadPlugins();
            $this->loadMobilesExt();
            $this->loadCustomFields();
            $this->loadCulpa();
        } else {
            throw new Exception(self::EX_EMPTY_LOGIN);
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
        $this->ubConfig = $ubillingConfig;
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
            if (cfr('MACVEN')) {
                $optionState = $this->alterCfg['MACVEN_ENABLED'];
                switch ($optionState) {
                    case 1:
                        $lookupUrl = '?module=macvendor&modalpopup=true&mac=' . $this->mac . '&username=' . $this->login;
                        $result .= wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                        $result .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                        break;
                    case 2:
                        $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $this->mac . '&username=' . $this->login . '" width="360" height="160" frameborder="0"');
                        $vendorframe .= wf_tag('iframe', true);
                        $result = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
                        break;
                    case 3:
                        $lookupUrl = '?module=macvendor&raw=true&mac=' . $this->mac . '&username=' . $this->login;
                        $result .= wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                        $result .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                        break;
                }
            }
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
                $result .= wf_Link('?module=switchpoller&macfilter=' . $this->mac, wf_img('skins/fdbmacsearch.png', __('Current FDB cache')), false) . ' ';
                $result .= wf_Link('?module=fdbarchive&macfilter=' . $this->mac, wf_img_sized('skins/fdbarchive.png', __('FDB') . ' ' . __('Archive'), '10', '10'), false);
            }
        }

        return ($result);
    }

    /**
     * Returns backlink to surveilance user primary profile
     * 
     * @return string
     */
    protected function getVisorBacklinks() {
        $result = '';
        if (@$this->alterCfg['VISOR_ENABLED']) {
            if (@$this->alterCfg['VISOR_IN_PROFILE']) {
                $visorUsers = new NyanORM('visor_users');
                $visorUsers->selectable(array('id', 'realname'));
                $visorUsers->where('primarylogin', '=', $this->login);
                $visorUserData = $visorUsers->getAll();
                if (!empty($visorUserData)) {
                    $visorUserId = $visorUserData[0]['id'];
                    $visorUserName = $visorUserData[0]['realname'];
                    $visorIcon = wf_img_sized('skins/icon_camera_small.png', '', '12', '12');
                    $visorLinkControl = wf_Link(UbillingVisor::URL_ME . UbillingVisor::URL_USERVIEW . $visorUserId, $visorIcon . ' ' . $visorUserName);
                    $result = $this->addRow(__('Video surveillance'), $visorLinkControl);
                } else {
                    $result = $this->addRow(__('Video surveillance'), __('No'));
                }
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
                $commentsIndicator = $adcomments->getCommentsIndicator($this->login, '12');
                if (cfr('NOTES')) {
                    $result = ' ' . wf_Link('?module=notesedit&username=' . $this->login, $commentsIndicator, false, '');
                } else {
                    $result = ' ' . $commentsIndicator;
                }

                if ($adcomments->haveComments($this->login)) {
                    $allAdComments = $adcomments->getCommentsAll($this->login);
                    if (!empty($allAdComments)) {
                        $commentsContent = '';
                        $commentsRows = '';
                        foreach ($allAdComments as $eachComment) {
                            $commentsCells = wf_TableCell(nl2br($eachComment['text']));
                            $commentsRows .= wf_TableRow($commentsCells, 'row2');
                        }
                        $commentsContent .= wf_TableBody($commentsRows, '100%', 0, '');
                        $initialState = false;
                        $myLogin = whoami();
                        if (isset($this->alterCfg['EXPAND_ADCOMMENTS_IN_PROFILE'])) {
                            $expandedFor = explode(',', $this->alterCfg['EXPAND_ADCOMMENTS_IN_PROFILE']);
                            if (in_array($myLogin, $expandedFor)) {
                                $initialState = true;
                            }
                        }
                        $result .= wf_ShowHide($commentsContent, __('Show all'), '', 'fullwidthcontainer', $initialState);
                    }
                }
            } else {
                $result = '';
            }
        }
        return ($result);
    }

    /**
     * returns MeCulpa if enabled
     *
     * @return string
     */
    protected function getMeCulpaRaw() {
        $result = '';
        if ($this->ubConfig->getAlterParam('MEACULPA_ENABLED')) {
            if ($this->culpa) {
                $result = $this->addRow(__('CULPA'), $this->culpa);
            } else {
                $result = $this->addRow(__('CULPA'), __('No'));
            }
        }
        return ($result);
    }

    /**
     * Returns user assigned culpa if it exists
     *
     *
     * @return string
     */
    protected function loadCulpa() {
        if ($this->ubConfig->getAlterParam('MEACULPA_ENABLED')) {
            $meaCulpa = new MeaCulpa();
            $this->culpa = $meaCulpa->get($this->login);
        }
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
        $result = '';
        $pluginsTmp = '';

        if (!empty($plugins)) {
            foreach ($plugins as $modulename => $eachplugin) {
                $renderable = true;
                $linkTarget = '';
                //checks for required pluging rights
                if (isset($eachplugin['need_right']) and !empty($eachplugin['need_right'])) {
                    if (cfr($eachplugin['need_right'])) {
                        $renderable = true;
                    } else {
                        $renderable = false;
                    }
                }

                //checking for required options
                if ($renderable) { //avoiding additional check
                    if (isset($eachplugin['need_option'])) {
                        if (@$this->alterCfg[$eachplugin['need_option']]) {
                            $renderable = true;
                        } else {
                            $renderable = false;
                        }
                    }
                }

                //optional link target
                if (isset($eachplugin['link_target'])) {
                    if (!empty($eachplugin['link_target'])) {
                        $linkTarget = 'target="' . $eachplugin['link_target'] . '"';
                    }
                }

                if ($renderable) {
                    $pluginsTmp .= wf_tag('div', false, '', 'style="width: ' . self::MAIN_OVERLAY_DISTANCE . '; height: ' . self::MAIN_OVERLAY_DISTANCE . '; float: left; font-size: 8pt;"');
                    $pluginsTmp .= wf_Link('?module=' . $modulename . '&username=' . $this->login, wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', ''), false, '', $linkTarget);
                    $pluginsTmp .= wf_tag('br') . __($eachplugin['name']);
                    $pluginsTmp .= wf_tag('div', true);
                }
            }

            if (!empty($pluginsTmp)) {
                //formating results here
                $result .= wf_tag('table', false, '', 'width="100%" border="0"');
                $result .= wf_tag('tr', false);
                $result .= wf_tag('td', false, '', 'valign="middle" align="center"');
                $result .= $pluginsTmp;
                $result .= wf_tag('td', true);
                $result .= wf_tag('tr', true);
                $result .= wf_tag('table', true);
            }
        }


        return ($result);
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
                $graphPing = (@$this->alterCfg['PINGCHARTS_DEFAULT']) ? true : false;
                foreach ($rawPlugins as $modulename => $eachplugin) {
                    $linkTarget = '';
                    $renderable = true;
                    //checks for required pluging rights
                    if (isset($eachplugin['need_right']) and !empty($eachplugin['need_right'])) {
                        if (cfr($eachplugin['need_right'])) {
                            $renderable = true;
                        } else {
                            $renderable = false;
                        }
                    }

                    //checking for required options
                    if ($renderable) { //avoiding additional check
                        if (isset($eachplugin['need_option'])) {
                            if (@$this->alterCfg[$eachplugin['need_option']]) {
                                $renderable = true;
                            } else {
                                $renderable = false;
                            }
                        }
                    }

                    if (isset($eachplugin['link_target'])) {
                        if (!empty($eachplugin['link_target'])) {
                            $linkTarget = 'target="' . $eachplugin['link_target'] . '"';
                        }
                    }

                    if (isset($eachplugin['overlay'])) {
                        $overlaydata = $this->loadPluginsOverlay($eachplugin['overlaydata']);
                        //any overlay plugins loaded for current user?
                        if (!empty($overlaydata)) {
                            $overlaydata = $overlaydata . wf_delimiter();
                            if ($renderable) {
                                $this->plugins .= wf_modal(wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', self::MAIN_PLUGINS_SIZE), __($eachplugin['name']), $overlaydata, '', 850, 650);
                            }
                        }
                    } else {
                        $pluginUrl = '?module=' . $modulename . '&username=' . $this->login;
                        //appenging optional graphical ping if required
                        if ($graphPing) {
                            if (ispos($modulename, 'ping')) {
                                $pluginUrl .= '&charts=true';
                            }
                        }

                        if ($renderable) {
                            $this->plugins .= wf_Link($pluginUrl, wf_img_sized('skins/' . $eachplugin['icon'], __($eachplugin['name']), '', self::MAIN_PLUGINS_SIZE), false, '', $linkTarget) . wf_delimiter();
                        }
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
        if ($this->alterCfg['OPENPAYZ_SUPPORT']) {
            if ($this->alterCfg['OPENPAYZ_REALID']) {
                $this->paymentid = zb_PaymentIDGet($this->login);
            } else {
                $this->paymentid = ip2int($this->userdata['IP']);
            }
        }
    }

    /**
     * Preloads extended mobile numbers from database
     * 
     * @return void
     */
    protected function loadMobilesExt() {
        if (isset($this->alterCfg['MOBILES_EXT'])) {
            if ($this->alterCfg['MOBILES_EXT']) {
                //using raw query here to avoid performance degradation
                $allExtRaw = simple_queryall("SELECT `id`,`mobile` from `mobileext` WHERE `login`='" . $this->login . "'");
                if (!empty($allExtRaw)) {
                    foreach ($allExtRaw as $io => $each) {
                        $this->mobilesExt[] = $each['mobile'];
                    }
                }
            }
        }
    }

    /**
     * Preloads custom profile fields instance
     * 
     * @return void
     */
    protected function loadCustomFields() {
        $this->customFields = new CustomFields($this->login);
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
        $result .= $this->getControl('?module=addcash&username=' . $this->login . '#cashfield', 'skins/icon_cash_big.png', 'Finance operations', 'Cash', 'CASH');
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
        //receipt print button
        $result .= $this->getReceiptControls();

        //optional ONU master controls
        if ($this->ubConfig->getAlterParam('USERPROFILE_ONUMASTER_BUTTON_ON')) {
            $result .= $this->getControl('?module=onumaster&username=' . $this->login, 'skins/onu_master.png', 'ONU master', 'ONU master', 'ONUMASTER');
        }

        $result .= wf_tag('td', true);
        $result .= wf_tag('tbody', true);
        $result .= wf_tag('table', true);

        return ($result);
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
                $allUserAddress = zb_AddressGetFulladdresslistCached();
                $parent_login = cu_GetParentUserLogin($alllinkedusers[$this->login]);
                $result = wf_Link('?module=corporate&userlink=' . $alllinkedusers[$this->login], wf_img('skins/corporate_small.gif') . __('User linked with') . ': ' . @$allUserAddress[$parent_login], false, '');
            }
        }

        //check is user corporate parent?
        if ($this->alterCfg['USER_LINKING_ENABLED']) {
            $allparentusers = cu_GetAllParentUsers();
            if (isset($allparentusers[$this->login])) {
                if (($_GET['module'] != 'corporate') and ($_GET['module'] != 'addcash')) {
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
        $result = '';
        //profile task creation icon
        if ($this->alterCfg['CREATETASK_IN_PROFILE']) {
            if (!ts_isMeBranchCursed()) {
                @$shortAddress = $this->useraddress;
                //additional mobile numbers preset
                $additionalNumbers = '';
                if (isset($this->alterCfg['MOBILES_EXT'])) {
                    if ($this->alterCfg['MOBILES_EXT']) {
                        if (!empty($this->mobilesExt)) {
                            $additionalNumbers .= ' '; //space before primary mobile
                            $additionalNumbers .= implode(' ', $this->mobilesExt);
                        }
                    }
                }
                $createForm = ts_TaskCreateFormProfile($shortAddress, $this->mobile . $additionalNumbers, $this->phone, $this->login);
                $result = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), $createForm, '', '450', '540');
            }
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * Returns extended build locator modal window
     * 
     * @param int $userBuildId
     * @param string $currentGeo
     * 
     * @return string
     */
    protected function getBuildLocatorExt($userBuildId, $currentGeo) {
        $result = '';
        $locContent = '';
        if (cfr('BUILDS')) {
            if (ubRouting::checkPost(array('blextbuildid', 'blextbuildgeo'))) {
                zb_AddressChangeBuildGeo(ubRouting::post('blextbuildid'), ubRouting::post('blextbuildgeo'));
                ubRouting::nav(self::URL_PROFILE . $this->login);
            }
            $locInputs = wf_HiddenInput('blextbuildid', $userBuildId);
            $locInputs .= wf_TextInput('blextbuildgeo', __('Geo location'), $currentGeo, false, 15, 'geo', '', 'blextbuildgeo');
            //GPS geolocation accessible?
            if (zb_isHttpsRequest()) {
                $locInputs .= wf_delimiter();
                $locInputs .= web_GPSLocationFillInputControl('blextbuildgeo') . ' ';
            }
            $locInputs .= wf_Submit(__('Save'));
            $locContent .= wf_Form('', 'POST', $locInputs, 'glamour');
            $locContent .= wf_delimiter();
        }
        $locContent .= wf_Link('?module=usersmap&locfinder=true&placebld=' . $userBuildId, wf_img_sized('skins/ymaps/target.png', __('Place on map'), '10') . ' ' . __('Place on map'), false, 'ubButton');
        $result .= wf_modalAuto(wf_img_sized('skins/ymaps/target.png', __('Place on map') . ': ' . $this->useraddress, '10'), __('Place on map') . ': ' . $this->useraddress, $locContent);
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
                    $userBuildId = $this->aptdata['buildid'];
                    //extended build locator
                    if (@$this->alterCfg['BUILDLOCATOR_EXTENDED']) {

                        $buildLocator .= $this->getBuildLocatorExt($userBuildId, $thisUserBuildGeo);
                    } else {
                        //default build locator
                        $buildLocator .= ' ' . wf_Link('?module=usersmap&locfinder=true&placebld=' . $userBuildId, wf_img_sized('skins/ymaps/target.png', __('Place on map'), '10'), false, '');
                    }
                }
                //and neighbors state cache
                if (!empty($this->aptdata['buildid'])) {
                    $cache = new UbillingCache();
                    $inbuildNeigbors_raw = $cache->get('INBUILDUSERS', 3600);
                    if (isset($inbuildNeigbors_raw[$this->aptdata['buildid']])) {
                        $inbuildNeigbors_raw = $inbuildNeigbors_raw[$this->aptdata['buildid']];
                        if (!empty($inbuildNeigbors_raw)) {
                            $inbuildNeigborsStat = '';

                            //Build passports enabled?
                            if ($this->alterCfg['BUILD_EXTENDED']) {
                                $buildPassportUrl = BuildPassport::URL_PASSPORT . '&' . BuildPassport::ROUTE_BUILD . '=' . $this->aptdata['buildid'];
                                $buildPassportUrl .= '&back=' . base64_encode('userprofile&username=' . $this->login);
                                $bpStyle = 'style="width:85%;"';
                                $buildPassportLink = wf_Link($buildPassportUrl, wf_img('skins/icon_buildpassport.png') . ' ' . __('Go to build passport'), false, 'ubButton', $bpStyle);
                                $inbuildNeigborsStat .= $buildPassportLink;
                            }

                            $inbuildNeigborsStat .= wf_TableBody($inbuildNeigbors_raw['rows'], '100%', '0', 'sortable');
                            $inbuildNeigborsStat .= wf_tag('br') . __('Active') . ' ' . $inbuildNeigbors_raw['aliveusers'] . '/' . $inbuildNeigbors_raw['userscount'];

                            $buildNeighborsIcon = wf_img_sized('skins/icon_build.gif', __('Neighbours'), 12);
                            $fullUserData = $this->AllUserData[$this->login];
                            $locatorTitle = __('Neighbours') . ' ' . __('on') . ' ' . @$fullUserData['streetname'] . ' ' . @$fullUserData['buildnum'];
                            $buildLocator .= ' ' . wf_modal($buildNeighborsIcon, $locatorTitle, $inbuildNeigborsStat, '', 400, 400);
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
                    'unao' => __('Disable AlwaysOnline'),
                    'setdiscount' => __('Change discount')
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
                    'unao' => 'skins/icon_online.gif',
                    'setdiscount' => 'skins/icon_discount_16.png'
                );

                foreach ($all as $io => $each) {
                    if ((isset($actionNames[$each['action']])) and (isset($actionIcons[$each['action']]))) {
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
                    $contractDates = new ContractDates();
                    $allContractDates = $contractDates->getAllDatesBasic($this->contract);
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
            $switchPortAssign = new SwitchPortAssign();
            $switchPortAssign->catchChangeRequest(self::URL_PROFILE . $this->login);
            $result = $switchPortAssign->renderEditForm($this->login);
        } else {
            $result = '';
        }
        return ($result);
    }

    /**
     * gets zabbix profile controls
     * 
     * @return string
     */
    protected function getZabbixProblemControls() {
        //zabbix section
        if ($this->ubConfig->getAlterParam('ZABBIX_PROBLEM_IN_PROFILE')) {
            $result = web_ProfileSwitchZabbixProblem($this->login);
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
        if ($this->alterCfg['VLAN_IN_PROFILE'] and $this->alterCfg['VLANGEN_SUPPORT']) {
            $result = web_ProfileVlanControlForm($this->login);
        } else {
            $result = '';
        }
        return ($result);
    }

    protected function getQinqPairControls() {
        $result = '';
        if ($this->alterCfg['QINQ_IN_PROFILE'] and $this->alterCfg['VLAN_MANAGEMENT_ENABLED']) {
            $vlanManagement = new VlanManagement();
            $result .= $vlanManagement->showUsersVlanPair($this->login);
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
     * Returns assigned ONU signal/dereg reason
     *
     * @return string
     */
    protected function getPonSignalControlCompact() {
        $result = '';
        if (isset($this->alterCfg['SIGNAL_IN_PROFILE_COMPACT'])) {
            if ($this->alterCfg['SIGNAL_IN_PROFILE_COMPACT']) {

                $signal = 'ETAOIN SHRDLU';
                $deregReason = '';
                $ponizerDb = new NyanORM(PONizer::TABLE_ONUS);
                $ponizerDb->where('login', '=', $this->login);
                $onuData = $ponizerDb->getAll();
                if (empty($onuData)) {
                    //no primary assign found?
                    $ponizerOnuExtDb = new NyanORM(PONizer::TABLE_ONUEXTUSERS);
                    $ponizerOnuExtDb->where('login', '=', $this->login);
                    $assignedOnuExt = $ponizerOnuExtDb->getAll();
                    if (!empty($assignedOnuExt)) {
                        $ponizerDb->where('id', '=', $assignedOnuExt[0]['onuid']);
                        $onuData = $ponizerDb->getAll();
                    }
                }

                if (!empty($onuData)) {
                    $onuData = $onuData[0];
                    $onuId = $onuData['id'];
                    $oltId = $onuData['oltid'];
                    $oltAttractor = new OLTAttractor($oltId);
                    $allSignals = $oltAttractor->getSignalsAll();

                    //lookup latest ONU signal
                    $signalLookup = $oltAttractor->lookupOnuIdxValue($onuData, $allSignals);
                    if ($signalLookup != false) {
                        $signal = $signalLookup;
                    }

                    if ($onuId) {
                        //is ONU signal found in signals cache?
                        $signal = ($signal == 'ETAOIN SHRDLU') ? PONizer::NO_SIGNAL : $signal;
                        $signalLabel = zb_PonSignalColorize($signal);

                        //ONU is offline?
                        if ($signal == PONizer::NO_SIGNAL) {
                            //lookup last dereg reason
                            $allDeregReasons = $oltAttractor->getDeregsAll();
                            $deregLookup = $oltAttractor->lookupOnuIdxValue($onuData, $allDeregReasons);
                            if ($deregLookup != false) {
                                $deregReason = $deregLookup;
                            }

                            if (!empty($deregReason)) {
                                $signalLabel .= ' - ' . $deregReason;
                            }
                        }

                        if (cfr('PON')) {
                            $backUrl = wf_GenBackUrl(self::URL_PROFILE . $this->login);
                            $signalLabel = wf_Link(PONizer::URL_ONU . $onuId . $backUrl, $signalLabel);
                        }

                        //profile row here
                        $result .= $this->addRow(__('ONU Signal'), $signalLabel);
                    }
                }
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
        $rows = '';
        $searched = __('No');
        $sigColor = PONizer::COLOR_NOSIG;
        $signal = '';

        if ($this->alterCfg['SIGNAL_IN_PROFILE']) {
            $onuAdditionalData = '';
            $ponizerDb = new NyanORM(PONizer::TABLE_ONUS);
            $ponizerDb->selectable('`pononu`.`id`, `pononu`.`onumodelid`, `pononu`.`oltid`, `pononu`.`ip`, `pononu`.`mac`, `pononu`.`serial`, `switchmodels`.`modelname`');
            $ponizerDb->joinOn('LEFT', 'switchmodels', '`pononu`.`onumodelid` = `switchmodels`.`id`');
            $ponizerDb->where('login', '=', $this->login);
            $onuData = $ponizerDb->getAll();

            if (empty($onuData)) {
                $ponizerDb->joinOn('LEFT', 'switchmodels', '`pononu`.`onumodelid` = `switchmodels`.`id`');
                $ponizerDb->joinOn('INNER', 'pononuextusers', '`pononu`.`id` = `pononuextusers`.`onuid`');
                $ponizerDb->where('`pononuextusers`.`login`', '=', $this->login);
                $onuData = $ponizerDb->getAll();
            }

            if (!empty($onuData)) {
                $onuData = $onuData[0];
                $pon = ($this->ubConfig->getAlterParam('PON_OLT_UPTIME_IN_PROFILE')
                    or $this->ubConfig->getAlterParam('PON_REALTIME_SIGNAL_IN_PROFILE')
                    or $this->ubConfig->getAlterParam('PON_REALTIME_EXTEN_INFO_IN_PROFILE')) ? new PONizer() : null;

                $curOLTID = (empty($onuData['oltid']) ? 0 : $onuData['oltid']);
                $curOLTAliveCheck = $this->ubConfig->getAlterParam('PON_OLT_ALIVE_PING_CHECK', false);
                $curOLTAliveCheckTimeout = $this->ubConfig->getAlterParam('PON_OLT_ALIVE_PING_CHECK_TIMEOUT', 1);
                $curOLTAlive = ($curOLTAliveCheck) ? false : true;
                $curOLTIP = '';
                $curOLTModelName = '';
                $curOLTLocation = '';

                $switchesDb = new NyanORM('switches');
                $switchesDb->selectable('`switches`.`id`,`switches`.`ip`,`switches`.`location`,`switchmodels`.`modelname`');
                $switchesDb->joinOn('LEFT', 'switchmodels', '`switches`.`modelid` = `switchmodels`.`id`');
                $switchesDb->where('`switches`.`id`', '=', $curOLTID);
                $oltData = $switchesDb->getAll();

                if (isset($oltData[0]) and !empty($oltData[0])) {
                    $curOLTIP = $oltData[0]['ip'];
                    $curOLTModelName = $oltData[0]['modelname'];
                    $curOLTLocation = $oltData[0]['location'];
                }

                if ($curOLTAliveCheck and !empty($curOLTIP)) {
                    $curOLTAlive = zb_PingICMPTimeout($curOLTIP, $curOLTAliveCheckTimeout);
                }

                if ($this->ubConfig->getAlterParam('USERPROFILE_ONU_INFO_SHOW')) {
                    $onuAdditionalData .= wf_TableCell(__('OLT'), '30%', 'row2');

                    if (isset($oltData[0]) and !empty($oltData[0])) {
                        $webIfaceLink = wf_tag('a', false, '', 'href="http://' . $curOLTIP . '" target="_blank" title="' . __('Go to the web interface') . '"');
                        $webIfaceLink .= wf_img('skins/ymaps/network.png');
                        $webIfaceLink .= wf_tag('a', true);

                        $onuAdditionalData .= wf_TableCell($curOLTIP . ' - ' . $curOLTModelName . ' - ' . $curOLTLocation . wf_nbsp(2) . $webIfaceLink);
                    } else {
                        $onuAdditionalData .= wf_TableCell(__('No data'));
                    }

                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    if ($this->ubConfig->getAlterParam('PON_OLT_UPTIME_IN_PROFILE')) {
                        // getting true/false from 1 and 2 value
                        $uptimeCached = ($curOLTAlive ? $this->ubConfig->getAlterParam('PON_OLT_UPTIME_IN_PROFILE') - 1 : true);
                        $oltUptime = $pon->getOLTUptime($curOLTID, $uptimeCached);
                        $oltUptime = (empty($oltUptime)) ? __('No data') : $oltUptime;
                        $onuAdditionalData = wf_TableCell(__('OLT') . ' ' . __('uptime'), '30%', 'row2');
                        $onuAdditionalData .= wf_TableCell($oltUptime);
                        $rows .= wf_TableRow($onuAdditionalData, 'row3');
                    }

                    $webIfaceLink = wf_tag('a', false, '', 'href="http://' . $onuData['ip'] . '" target="_blank" title="' . __('Go to the web interface') . '"');
                    $webIfaceLink .= wf_img('skins/ymaps/network.png');
                    $webIfaceLink .= wf_tag('a', true);

                    $onuAdditionalData = wf_TableCell(__('ONU IP'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($onuData['ip'] . ' - ' . $onuData['modelname'] . wf_nbsp(2) . $webIfaceLink);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuAdditionalData = wf_TableCell(__('ONU MAC'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($onuData['mac']);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuAdditionalData = wf_TableCell(__('ONU Serial'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($onuData['serial']);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuInterface = '';
                    $onuInterfacesCache = PONizer::INTCACHE_PATH . $curOLTID . '_' . PONizer::INTCACHE_EXT;
                    if (file_exists($onuInterfacesCache)) {
                        $raw = file_get_contents($onuInterfacesCache);
                        $raw = unserialize($raw);
                        foreach ($raw as $mac => $interface) {
                            if ($mac == $onuData['mac'] or $mac == $onuData['serial']) {
                                $onuInterface = $interface;
                                break;
                            }
                        }
                    }

                    $onuAdditionalData = wf_TableCell(__('ONU LLID') . ' (' . __('interface') . ')', '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($onuInterface);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');
                }

                $raw = array();
                $realtimeStr = '';

                if ($curOLTAlive and $this->ubConfig->getAlterParam('PON_REALTIME_SIGNAL_IN_PROFILE')) {
                    $onuMAC = (empty($onuData['serial'])) ? $onuData['mac'] : $onuData['serial'];
                    $signal = $pon->getONURealtimeSignal($curOLTID, $onuMAC);

                    if (!empty($signal)) {
                        $raw = array($onuMAC => $signal);
                        $realtimeStr = ' (' . __('realtime') . ')';
                    }
                }

                if (empty($raw)) {
                    $onuSignalsCache = PONizer::SIGCACHE_PATH . $curOLTID . '_' . PONizer::SIGCACHE_EXT;
                    if (file_exists($onuSignalsCache)) {
                        $raw = file_get_contents($onuSignalsCache);
                        $raw = unserialize($raw);
                    }
                }

                if (isset($raw[$onuData['mac']])) {
                    $signal = $raw[$onuData['mac']];
                }
                if (isset($raw[$onuData['serial']])) {
                    $signal = $raw[$onuData['serial']];
                }
                if (!empty($signal)) {
                    $searched = zb_PonSignalColorize($signal);
                }
                $backUrl=wf_GenBackUrl(self::URL_PROFILE.$this->login);

                $cells = wf_TableCell(__('ONU Signal') . $realtimeStr, '30%', 'row2');
                $cells .= wf_TableCell(wf_tag('strong') . $searched . wf_tag('strong', true)
                    . wf_nbsp(2) . wf_Link('?module=ponizer&editonu=' . $onuData['id'].$backUrl, web_edit_icon()));
                $rows .= wf_TableRow($cells, 'row3');

                if ($curOLTAlive and $this->ubConfig->getAlterParam('PON_REALTIME_EXTEN_INFO_IN_PROFILE')) {
                    $lastRegTime = '';
                    $lastDeregTime = '';
                    $lastAliveTime = '';
                    $onuMAC = (empty($onuData['serial'])) ? $onuData['mac'] : $onuData['serial'];
                    $onuTXSignal = $pon->getONURealtimeSignal($curOLTID, $onuMAC, true);
                    $extenInfo = $pon->getONUExtenInfo($curOLTID, $onuMAC);

                    if (!empty($extenInfo)) {
                        $lastRegTime = $extenInfo['lastreg'];
                        $lastDeregTime = $extenInfo['lastdereg'];
                        $lastAliveTime = $extenInfo['lastalive'];
                    }

                    $onuAdditionalData = wf_TableCell(__('ONU TX signal'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($onuTXSignal);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuAdditionalData = wf_TableCell(__('ONU last reg time'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($lastRegTime);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuAdditionalData = wf_TableCell(__('ONU last dereg time'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($lastDeregTime);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');

                    $onuAdditionalData = wf_TableCell(__('ONU connection uptime'), '30%', 'row2');
                    $onuAdditionalData .= wf_TableCell($lastAliveTime);
                    $rows .= wf_TableRow($onuAdditionalData, 'row3');
                }

                if ($this->ubConfig->getAlterParam('PONBOXES_ENABLED')) {
                    $ponBoxes = new PONBoxes(true);

                    $tmpONUData = $onuData;
                    $tmpONUData['login'] = $this->login;
                    $crossLinkWarning = '';
                    $cells = wf_TableCell(__('PON box'), '30%', 'row2');

                    //rendering associated boxes
                    $linkedBoxes = $ponBoxes->getLinkedBoxes($tmpONUData);

                    if (count($linkedBoxes) > 1) {
                        $crossLinkWarning = $ponBoxes->renderCrossLinkWarning(true) . wf_delimiter(0);
                    }

                    $cells .= wf_TableCell($crossLinkWarning . $ponBoxes->renderLinkedBoxes($linkedBoxes, true));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result = wf_TableBody($rows, '100%', '0');
            }
        }
        return ($result);
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
     * Returns user karma state notification
     * 
     * @return string
     */
    protected function getUserKarma() {
        $result = '';
        if (@$this->alterCfg['KARMA_CONTROL']) {
            if (@$this->alterCfg['KARMA_IN_PROFILE']) {
                $userKarma = new BadKarma(true);
                $karmaState = $userKarma->getKarmaIndicator($this->login, $this->userdata, '12');
                $result = $this->addRow(__('Karma'), $karmaState);
            }
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
                        $smsServiceId = (wf_CheckPost(array('preferredsmssrvid'))) ? $_POST['preferredsmssrvid'] : '';
                        log_register('EASYSMS SEND SMS `' . $targetNumber . '` FOR (' . $this->login . ')');
                        $queueFile = $sms->sendSMS($targetNumber, $targetText, $translitFlag, 'EASYSMS');
                        $sms->setDirection($queueFile, '', '', $smsServiceId);
                        rcms_redirect('?module=userprofile&username=' . $this->login);
                    }

                    if (!empty($this->mobile)) {
                        $translitCheckBox = true;
                        if ($this->alterCfg['EASY_SMS'] == 2) {
                            $translitCheckBox = false;
                        }

                        //cleaning mobile number
                        $userMobile = trim($this->mobile);
                        $userMobile = str_replace(' ', '', $userMobile);
                        $userMobile = str_replace('-', '', $userMobile);
                        $userMobile = str_replace('(', '', $userMobile);
                        $userMobile = str_replace(')', '', $userMobile);

                        if (isset($this->alterCfg['REMINDER_PREFIX'])) {
                            $prefix = $this->alterCfg['REMINDER_PREFIX']; //trying to support different formats
                            $smsText = '';
                            $sendInputs = '';

                            if (!empty($prefix)) {
                                $userMobile = str_replace($prefix, '', $userMobile);
                                $userMobile = $prefix . $userMobile;
                            }

                            if ($this->ubConfig->getAlterParam('EASY_SMS_QUICK_TEMPLATE')) {
                                $templateData = file_get_contents($this->esmsTemplatePath);

                                if (empty($templateData)) {
                                    $smsText = 'Login: ' . $this->login . ' Password: ' . $this->userdata['Password'];
                                } else {
                                    $smsText = $templateData;
                                    $smsText = str_ireplace('{LOGIN}', $this->login, $smsText);
                                    $smsText = str_ireplace('{PASSWORD}', $this->AllUserData[$this->login]['Password'], $smsText);
                                    $smsText = str_ireplace('{TARIFF}', $this->AllUserData[$this->login]['Tariff'], $smsText);
                                    $smsText = str_ireplace('{TARIFFPRICE}', zb_TariffGetPrice($this->AllUserData[$this->login]['Tariff']), $smsText);
                                    $smsText = str_ireplace('{CASH}', $this->AllUserData[$this->login]['Cash'], $smsText);
                                    $smsText = str_ireplace('{ROUNDCASH}', round($this->AllUserData[$this->login]['Cash'], 2), $smsText);
                                    $smsText = str_ireplace('{CONTRACT}', $this->contract, $smsText);
                                    $smsText = str_ireplace('{REALNAME}', $this->realname, $smsText);
                                    $smsText = str_ireplace('{ADDRESS}', $this->AllUserData[$this->login]['fulladress'], $smsText);
                                    $smsText = str_ireplace('{PAYID}', $this->paymentid, $smsText);
                                    $smsText = str_ireplace('{IP}', $this->AllUserData[$this->login]['ip'], $smsText);
                                    $smsText = str_ireplace('{MAC}', $this->mac, $smsText);
                                    $smsText = str_ireplace('{CURDATE}', curdate(), $smsText);
                                }
                            }

                            $sendInputs .= wf_TextInput('neweasysmsnumber', __('Mobile'), $userMobile, true, '15', 'mobile');
                            $sendInputs .= wf_TextArea('neweasysmstext', '', $smsText, true, '40x5');
                            $sendInputs .= wf_CheckInput('neweasysmstranslit', __('Forced transliteration'), true, $translitCheckBox);
                            $sendInputs .= wf_tag('br');
                            $sendInputs .= wf_Submit(__('Send SMS'));

                            if ($this->ubConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
                                $smsDirections = new SMSDirections();
                                $smsServiceId = $smsDirections->getDirection('user_login', $this->login);
                                $sendInputs .= wf_HiddenInput('preferredsmssrvid', $smsServiceId);
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
     * Returns PB fast controls for all user phones available
     *
     * @return void
     */
    protected function getPbFastUrlControls() {
        $result = '';
        if (@$this->alterCfg['PB_FASTURL_TOKEN']) {
            $pbFastUrl = new PBFastURL($this->login);
            $allUserPhones = array();
            $defaultAmount = 0;
            if (!empty($this->mobile)) {
                $allUserPhones[] = $this->mobile;
            }
            if (!empty($this->mobilesExt)) {
                $allUserPhones = array_merge($allUserPhones, $this->mobilesExt);
            }

            if ($this->userdata['Cash'] < 0) {
                $defaultAmount = abs($this->userdata['Cash']);
            }
            $pbForm = $pbFastUrl->renderForm($this->paymentid, $allUserPhones, $defaultAmount);
            $controlLabel = wf_img_sized('skins/pbfpay16.png', __('Send SMS'), '10', '10');
            $result .= wf_modalAuto($controlLabel, __('Send SMS'), $pbForm, '');
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
            if (@$this->alterCfg['CITY_DISPLAY']) {
                $userAddress = $this->useraddress;
            } else {
                $userAddress = @$this->AllUserData[$this->login]['cityname'] . ' ' . $this->useraddress;
            }
            $assignedAgentData = zb_AgentAssignedGetDataFast($this->login, $userAddress);
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
     * Returns discount controller
     * 
     * @return string
     */
    protected function getDiscountController() {
        $result = '';
        if (isset($this->alterCfg[Discounts::OPTION_ENABLE])) {
            if ($this->alterCfg[Discounts::OPTION_ENABLE]) {
                $discounts = new Discounts();
                $userDiscountPercent = $discounts->getUserDiscount($this->login);
                if ($userDiscountPercent) {
                    $renderDiscountPerncent = $userDiscountPercent . '%';
                } else {
                    $renderDiscountPerncent = __('No');
                }
                $result = $this->addRow(__('Discount'), $renderDiscountPerncent, true);
            }
        }
        return ($result);
    }

    /**
     * Returns additional fee controller
     * 
     * @return string
     */
    protected function getTaxSupController() {
        $result = '';
        if (isset($this->alterCfg['TAXSUP_ENABLED'])) {
            if ($this->alterCfg['TAXSUP_ENABLED']) {
                $taxa = new TaxSup();
                $userFee = $taxa->getUserFee($this->login);
                if ($userFee) {
                    $renderFee = $userFee;
                } else {
                    $renderFee = __('No');
                }
                $result = $this->addRow(__('Additional fee'), $renderFee, true);
            }
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
            if ((cfr('CREDIT')) and (cfr('CREDITEXPIRE'))) {
                $result = web_EasyCreditForm($this->login, $this->userdata['Cash'], $this->userdata['Credit'], $this->userdata['Tariff'], $this->alterCfg['EASY_CREDIT']);
            } else {
                $result = '';
            }
        }
        return ($result);
    }

    /**
     * Returns EasyFreeze controller if feature is enabled
     * 
     * @return string
     */
    protected function getEasyFreezeController() {
        $result = '';
        $messages = new UbillingMessageHelper();
        if (@$this->alterCfg['EASY_FREEZE']) {
            if (cfr('EASYFREEZE')) {
                if (@$this->alterCfg['DEALWITHIT_ENABLED']) {
                    $freezingAllowed = true;
                    if (@$this->alterCfg['DDT_ANTIFREEZE']) {
                        if (!cfr('SWRTZNGRFREEZE')) {
                            $ddt = new DoomsDayTariffs(true);
                            $protectedTariffs = $ddt->getCurrentTariffsDDT();
                            $userData = zb_UserGetStargazerData($this->login);
                            $userTariff = $userData['Tariff'];
                            if (isset($protectedTariffs[$userTariff])) {
                                $freezingAllowed = false;
                            }
                        }
                    }

                    if ($freezingAllowed) {
                        //catch freezing form data etc
                        $freezeResult = zb_EasyFreezeController();
                        //form rendering
                        $form = web_EasyFreezeForm($this->login);
                        if (!empty($freezeResult)) {
                            $form .= $messages->getStyledMessage($freezeResult, 'error');
                        }
                    } else {
                        $ddtProtectionLabel = $messages->getStyledMessage(__('This user uses one of doomsday tariffs') . '. ' . __('Freezing denied') . '.', 'error');
                        $form = wf_AjaxContainer('ddtnotice', 'style="width: 750px;"', $ddtProtectionLabel);
                    }
                } else {
                    $form = $messages->getStyledMessage(__('Deal with it') . ' ' . __('Disabled'), 'error');
                }

                $controlIcon = wf_img_sized('skins/easyfreeze.png', __('Freeze user'), '10');
                $result = wf_modalAuto($controlIcon, __('Freeze user'), $form);
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
                    rcms_redirect(self::URL_PROFILE . $this->login);
                }

                if (wf_CheckPost(array('cemeterysetasdead'))) {
                    $cemetery->setDead($_POST['cemeterysetasdead']);
                    rcms_redirect(self::URL_PROFILE . $this->login);
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
     * @param bool $nextMonth
     * 
     * @return string
     */
    protected function getTariffInfoContrainer($nextMonth = false) {
        $result = '';
        if (@$this->alterCfg['TARIFFINFO_IN_PROFILE']) {
            $containerId = ($nextMonth) ? 'TARIFFINFO_CONTAINER' : 'TARIFFINFO_CONTAINERNM';
            $result = wf_tag('div', false, '', 'id="' . $containerId . '" style="display:block;"') . wf_tag('div');
        }
        return ($result);
    }

    /**
     * Returns tariff info ajax controls
     * 
     * @param string $tariffName
     * @param bool $nextMonth
     * 
     * @return string
     */
    protected function getTariffInfoControls($tariffName, $nextMonth = false) {
        $result = '';
        if (@$this->alterCfg['TARIFFINFO_IN_PROFILE']) {
            $containerId = ($nextMonth) ? 'TARIFFINFO_CONTAINER' : 'TARIFFINFO_CONTAINERNM';
            if (!empty($tariffName)) {
                $result .= wf_AjaxLoader();
                $ajURL = '?module=tariffinfo&tariff=' . $tariffName;
                if (@$this->alterCfg['PT_ENABLED']) {
                    $ajURL .= '&username=' . $this->login;
                }
                $result .= wf_AjaxLink($ajURL, wf_img('skins/tariffinfo.gif', __('Tariff info')), $containerId, false, '');
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

        if ($this->alterCfg['PON_ENABLED'] and isset($this->alterCfg['PONCPE_CONTROLS_ENABLED']) and $this->alterCfg['PONCPE_CONTROLS_ENABLED']) {
            $poncpeFlag = true;

            if (isset($this->alterCfg['PONCPE_TARIFFMASK'])) {
                if (!empty($this->alterCfg['PONCPE_TARIFFMASK'])) {
                    if (isset($this->alterCfg['USERCPE_TARIFFMASK_CASEINSENS']) and $this->alterCfg['USERCPE_TARIFFMASK_CASEINSENS']) {
                        if (!ispos(strtolower($this->userdata['Tariff']), strtolower($this->alterCfg['PONCPE_TARIFFMASK']))) {
                            $poncpeFlag = false;
                        }
                    } else {
                        if (!ispos($this->userdata['Tariff'], $this->alterCfg['PONCPE_TARIFFMASK'])) {
                            $poncpeFlag = false;
                        }
                    }
                }
            }

            if ($poncpeFlag) {
                $pon = new PONizer();
                $result .= $pon->renderCpeUserControls($this->login, $this->AllUserData);
            }
        }

        if ($this->alterCfg['WIFICPE_ENABLED']) {
            $wcpeFlag = true;

            if (isset($this->alterCfg['WIFICPE_TARIFFMASK'])) {
                if (!empty($this->alterCfg['WIFICPE_TARIFFMASK'])) {
                    if (isset($this->alterCfg['USERCPE_TARIFFMASK_CASEINSENS']) and $this->alterCfg['USERCPE_TARIFFMASK_CASEINSENS']) {
                        if (!ispos(strtolower($this->userdata['Tariff']), strtolower($this->alterCfg['WIFICPE_TARIFFMASK']))) {
                            $wcpeFlag = false;
                        }
                    } else {
                        if (!ispos($this->userdata['Tariff'], $this->alterCfg['WIFICPE_TARIFFMASK'])) {
                            $wcpeFlag = false;
                        }
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
                if (!empty($this->mobilesExt)) {
                    $additionalNumbers = implode(', ', $this->mobilesExt);
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
        if ((isset($this->alterCfg['DISTRICTS_ENABLED'])) and ($this->alterCfg['DISTRICTS_ENABLED'])) {
            if ((isset($this->alterCfg['DISRTICTS_IN_PROFILE'])) and ($this->alterCfg['DISRTICTS_IN_PROFILE'])) {
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
        if (isset($this->alterCfg['CONTRACT_PROFILE_HIDE']) and $this->alterCfg['CONTRACT_PROFILE_HIDE']) {
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

        if (isset($this->alterCfg['SMS_HISTORY_ON']) and $this->alterCfg['SMS_HISTORY_ON'] and cfr('SMSHIST')) {
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
        $row = '';

        if ($this->ubConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
            $usrLogin = $this->userdata['login'];
            $tabSMSSrvRelations = new NyanORM('sms_services_relations');

            if (ubRouting::checkPost('ajax') and ubRouting::post('action') == 'BindSMSSrv') {
                $newSMSSrvID = ubRouting::post('smssrvid');
                $oldSMSSrvID = ubRouting::post('oldsmssrvid');

                if (ubRouting::checkPost('createrec')) {
                    $tabSMSSrvRelations->dataArr(
                        array(
                            'sms_srv_id' => $newSMSSrvID,
                            'user_login' => $usrLogin
                        )
                    );
                    $tabSMSSrvRelations->create();
                } else {
                    $tabSMSSrvRelations->data('sms_srv_id', $newSMSSrvID);
                    $tabSMSSrvRelations->where('user_login', '=', $usrLogin);
                    $tabSMSSrvRelations->save();
                }

                log_register("Prefered SMS service changed from [" . $oldSMSSrvID . "] to [" . $newSMSSrvID . "] for user (" . $usrLogin . ")");
            }

            $preferredSMSSrv = zb_getUsersPreferredSMSService($usrLogin);
            $preferredSMSSrvId = $preferredSMSSrv[0];

            $row .= $this->addRow(
                __('Preferred SMS service'),
                wf_Selector('sms_srv', zb_getSMSServicesList(), '', $preferredSMSSrvId, false, false, 'related_sms_srv') .
                    wf_HiddenInput('sms_srv_create', empty($preferredSMSSrvId), 'related_sms_srv_create') .
                    wf_tag('span', false, '', 'id="sms_srv_change_flag" style="color: darkred"') .
                    wf_tag('span', true)
            );
            $row .= wf_tag('script', false, '', 'type="text/javascript"');
            $row .= '$(\'#related_sms_srv\').change(function() {
                            var SMSSrvID = $(this).val(); 
                            var CreateRec = $(\'#related_sms_srv_create\').val();
                            
                            $.ajax({
                                type: "POST",
                                url: "?module=userprofile&username=' . $usrLogin . '",
                                data: { action: "BindSMSSrv",
                                        ajax: true,                                            
                                        smssrvid: SMSSrvID,                                                                                                                 
                                        ' . ((empty($preferredSMSSrvId)) ? 'createrec: CreateRec, ' : '') . '
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
     * Returns spoiler block with user's active PPPoE session data
     *
     * @return string
     */
    protected function getROSPPPoESessionData() {
        $row = '';

        if ($this->ubConfig->getAlterParam('ROS_NAS_PPPOE_SESSION_INFO_IN_PROFLE')) {
            $row = zb_RenderROSPPPoESessionInfo($this->login, '?module=userprofile');
        }

        return ($row);
    }

    /**
     * Returns users data export allowance trigger if appropriate alter.ini option is set
     *
     * @return string
     */
    protected function getDataExportPermissionTrigger() {
        $row = '';

        if ($this->ubConfig->getAlterParam('USERS_DATA_EXPORT_ON')) {
            $usrLogin = $this->userdata['login'];
            $tabDataExportAllowed = new NyanORM('user_dataexport_allowed');

            if (ubRouting::checkPost('ajax') and ubRouting::post('action') == 'ToggleDataExport') {
                $newTriggerVal = ubRouting::post('newtriggerval');
                $oldTriggerVal = ubRouting::post('oldtriggerval');

                if (ubRouting::checkPost('createrec')) {
                    $tabDataExportAllowed->dataArr(
                        array(
                            'login' => $usrLogin,
                            'export_allowed' => $newTriggerVal
                        )
                    );
                    $tabDataExportAllowed->create();
                } else {
                    $tabDataExportAllowed->data('export_allowed', $newTriggerVal);
                    $tabDataExportAllowed->where('login', '=', $usrLogin);
                    $tabDataExportAllowed->save();
                }

                log_register("Data export permission changed from [" . $oldTriggerVal . "] to [" . $newTriggerVal . "] for user (" . $usrLogin . ")");
            }

            $tabDataExportAllowed->selectable('export_allowed');
            $tabDataExportAllowed->where('login', '=', $usrLogin);
            $queryResult = $tabDataExportAllowed->getAll();
            $triggerVal = (isset($queryResult[0])) ? $queryResult[0]['export_allowed'] : '';

            $row .= $this->addRow(
                __('Data export allowed'),
                wf_tag('span', false, '', 'id="data_export_off"') .
                    web_red_led() .
                    wf_tag('span', true) .
                    wf_tag('span', false, '', 'id="data_export_on"') .
                    web_green_led() .
                    wf_tag('span', true) .
                    wf_nbsp(2) . wf_Selector('dea', array(0 => __('No'), 1 => __('Yes')), '', $triggerVal, false, false, 'DataExportAllowed') .
                    wf_HiddenInput('dataexportreccreate', wf_emptyNonZero($triggerVal), 'data_export_rec_create') .
                    wf_tag('span', false, '', 'id="data_export_change_flag" style="color: darkred"') .
                    wf_tag('span', true)
            );
            $row .= wf_tag('script', false, '', 'type="text/javascript"');
            $row .= '
                    $(document).ready(function() {
                        var deaVal = $(\'#DataExportAllowed\').val();
    
                        if (deaVal == 1) {
                            $(\'#data_export_off\').hide();
                            $(\'#data_export_on\').show();
                        } else {
                            $(\'#data_export_off\').show();
                            $(\'#data_export_on\').hide();
                        }
                    });
                                    
                    $(\'#DataExportAllowed\').change(function() {
                        var deaVal = $(this).val(); 
                        var CreateRec = $(\'#data_export_rec_create\').val();
                        
                        if (deaVal == 1) {
                            $(\'#data_export_off\').hide();
                            $(\'#data_export_on\').show();
                        } else {
                            $(\'#data_export_off\').show();
                            $(\'#data_export_on\').hide();
                        }
                        
                        $.ajax({
                            type: "POST",
                            url: "?module=userprofile&username=' . $usrLogin . '",
                            data: { action: "ToggleDataExport",
                                    ajax: true,                                            
                                    newtriggerval: deaVal,                                                                                                                 
                                    ' . (wf_emptyNonZero($triggerVal) ? 'createrec: CreateRec, ' : '') . '
                                    oldtriggerval: "' . $triggerVal . '"
                                   },
                            success: function() {
                                        $(\'#data_export_change_flag\').text(" ' . __('Changed') . '");
                                     }
                        });
                     });
                    ';
            $row .= wf_tag('script', true);
        }

        return ($row);
    }

    /**
     * Returns easy charge form and controller
     * 
     * @return string
     */
    protected function getEasyChargeController() {
        global $billing;

        $result = '';
        if (cfr('CASH')) {
            if (@$this->alterCfg['EASY_CHARGE']) {
                $creditDaysMode = (@$this->alterCfg['EASY_CHARGE_CREDIT_DAYS']) ? ubRouting::filters($this->alterCfg['EASY_CHARGE_CREDIT_DAYS'], 'int') : 0;

                if ($creditDaysMode) {
                    $proposalLabel = __('Set user credit for') . ' ' . $creditDaysMode . ' ' . __('days if required');
                } else {
                    $proposalLabel = __('Set user credit to end of month if required');
                }

                $inputs = wf_HiddenInput('easychargedosomething', 'true');
                $inputs .= wf_TextInput('easychargesumm', __('Withdraw from user account'), '', true, 5, 'finance');
                $inputs .= wf_TextInput('easychargenote', __('Notes'), '', true, 30);
                $inputs .= wf_CheckInput('easychargecreditm', $proposalLabel, true, true);
                $inputs .= wf_delimiter(0);
                $inputs .= wf_Submit(__('Charge'));
                $form = wf_Form('', 'POST', $inputs, 'glamour');
                $result .= ' ' . wf_modalAuto(wf_img_sized('skins/icon_minus.png', __('Charge'), '10'), __('Charge'), $form);

                //controller part
                if (ubRouting::checkPost(array('easychargedosomething', 'easychargesumm'))) {
                    $currentUserData = $this->AllUserData[$this->login];
                    $currentUserBalance = $currentUserData['Cash'];
                    $currentUserCredit = $currentUserData['Credit'];
                    $chargeType = ($this->alterCfg['EASY_CHARGE'] == 1) ? 'add' : 'correct';

                    $chargeSumm = ubRouting::post('easychargesumm', 'mres');
                    $chargeSumm = abs($chargeSumm); // it shall to be positive always
                    if (zb_checkMoney($chargeSumm)) {
                        $cashAfterCharge = $currentUserBalance - $chargeSumm; // ^^^ thats why
                        $nextUserCredit = $currentUserCredit;
                        $note = ubRouting::post('easychargenote', 'mres');

                        if (abs($cashAfterCharge) >= $currentUserCredit) {
                            $nextUserCredit = abs($cashAfterCharge);
                            //prevent bad-karma
                            if (!is_int($nextUserCredit)) {
                                $nextUserCredit = $nextUserCredit + 1;
                            }
                        }
                        //charge some money
                        zb_CashAdd($this->login, '-' . $chargeSumm, $chargeType, 1, 'ECHARGE:' . $note);

                        //is new credit required?
                        if ($cashAfterCharge < '-' . $currentUserCredit) {
                            //credit is required by checkbox
                            if (ubRouting::checkPost('easychargecreditm')) {
                                //set credit
                                $billing->setcredit($this->login, $nextUserCredit);
                                log_register('CHANGE Credit (' . $this->login . ') ON ' . $nextUserCredit);

                                if ($creditDaysMode) {
                                    //set credit expire date for some days count
                                    $currentTimeStamp = time();
                                    $daysOffset = $creditDaysMode * 86400;
                                    $dateShift = $currentTimeStamp + $daysOffset;
                                    $creditExpire = date('Y-m-d', $dateShift);
                                } else {
                                    //set credit expire date to next month
                                    $creditExpire = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));
                                }
                                $billing->setcreditexpire($this->login, $creditExpire);
                                log_register('CHANGE CreditExpire (' . $this->login . ') ON ' . $creditExpire);
                            }
                        }

                        //preventing charing duplicates
                        if (ubRouting::checkGet('module')) {
                            $currentModule = ubRouting::get('module');

                            $redirectUrl = '';
                            if ($currentModule == 'userprofile') {
                                $redirectUrl = '?module=userprofile&username=' . $this->login;
                            }

                            if ($currentModule == 'addcash') {
                                $redirectUrl = '?module=addcash&username=' . $this->login . '#cashfield';
                            }

                            if (!empty($redirectUrl)) {
                                //must be an header redirect to avoid fails with URLs that contains #anchor
                                ubRouting::nav($redirectUrl, true);
                            }
                        }
                    } else {
                        $result .= wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200');
                        log_register('EASYCHARGEFAIL (' . $this->login . ') WRONG SUMM `' . ubRouting::post('easychargesumm') . '`');
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders deferred sales controller and form
     * 
     * @return string
     */
    protected function getDeferredSaleController() {
        $result = '';
        if ($this->alterCfg['DEFERRED_SALE_ENABLED']) {
            if (cfr('DEFSALE')) {
                $deferredSale = new DeferredSale($this->login);
                $result .= ' ' . $deferredSale->renderForm();
                $result .= $deferredSale->catchRequest();
            }
        }
        return ($result);
    }

    /**
     * Returns receipt controls (BOBER GDE COMMENT????)
     * 
     * @return string
     */
    protected function getReceiptControls() {
        if ($this->ubConfig->getAlterParam('PRINT_RECEIPTS_ENABLED') and $this->ubConfig->getAlterParam('PRINT_RECEIPTS_IN_PROFILE') and cfr('PRINTRECEIPTS')) {
            $receiptsPrinter = new PrintReceipt();

            $result = wf_tag('div', false, 'dashtask', 'style="height:' . self::MAIN_CONTROLS_SIZE . '; width:' . self::MAIN_CONTROLS_SIZE . ';"');
            $result .= $receiptsPrinter->renderWebFormForProfile($this->login, 'inetsrv', __('Internet'), $this->AllUserData[$this->login]['Cash'], $this->AllUserData[$this->login]['streetname'], $this->AllUserData[$this->login]['buildnum']);
            $result .= wf_tag('br');
            $result .= __('Print receipt');
            $result .= wf_tag('div', true);

            return ($result);
        }
    }

    /**
     * Returns user NAS info ajax controls
     * 
     * @param string $userIp
     * 
     * @return string 
     */
    protected function getNasInfoControls($userIp) {
        $result = '';
        if (@$this->alterCfg['USERNAS_IN_PROFILE']) {
            $containerId = 'NASINFO_CONTAINER';
            if (!empty($userIp)) {
                $result .= wf_AjaxLoader();
                $result .= wf_AjaxLink('?module=nasinfo&ip=' . $userIp, wf_img('skins/nasinfo.gif', __('Network Access Servers')), $containerId, false, '');
            }
        }
        return ($result);
    }

    /**
     * Returns user NAS info container for data display
     * 
     * 
     * @return string
     */
    protected function getNasInfoContrainer() {
        $result = '';
        if (@$this->alterCfg['USERNAS_IN_PROFILE']) {
            $containerId = 'NASINFO_CONTAINER';
            $result = wf_tag('div', false, '', 'id="' . $containerId . '" style="display:block;"') . wf_tag('div');
        }
        return ($result);
    }

    /**
     * Returns cached user extended address info rows
     *
     * @return string
     */
    protected function getAddressExtenControls() {
        $result = '';
        if ($this->ubConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED')) {
            $extenAddrData = zb_AddressExtenGetLoginFast($this->login);

            $postCode = (empty($extenAddrData['postal_code'])) ? '' : $extenAddrData['postal_code'];
            $extenTown = (empty($extenAddrData['town_district'])) ? '' : $extenAddrData['town_district'];
            $extenAddr = (empty($extenAddrData['address_exten'])) ? '' : $extenAddrData['address_exten'];

            $result .= $this->addRow(__('Postal code'), $postCode, false);
            $result .= $this->addRow(__('Town/District/Region'), $extenTown, false);
            $result .= $this->addRow(__('Extended address'), $extenAddr, false);
        }
        return ($result);
    }

    /**
     * Renders pseudo CRM activities if enabled in the configuration.
     *
     * This method checks if the pseudo CRM feature is enabled and if the activities
     * should be displayed in the user profile. If both conditions are met, it searches
     * for a lead by the user's login and renders the lead's activities list.
     *
     * @return string
     */
    protected function renderPseudoCRMActs() {
        $result = '';
        if ($this->alterCfg['PSEUDOCRM_ENABLED']) {
            if ($this->alterCfg['PSEUDOCRM_ACT_IN_PROFILE']) {
                $crm = new PseudoCRM();
                $detectedLeadId = $crm->searchLeadByLogin($this->login);
                if ($detectedLeadId) {
                    $result .= $crm->renderLeadActivitiesList($detectedLeadId, true);
                }
            }
        }
        return ($result);
    }

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

        $activity = wf_img_sized('skins/icon_inactive.gif', '', '', '12') . ' ' . __('No');
        if ($this->userdata['Passive'] == 1 or $this->userdata['Down'] == 1) {
            $activity  = wf_img_sized('skins/yellow_led.png', '', '', '12') . ' ' . __('No');
        } else {
            if ($this->userdata['Cash'] >= '-' . $this->userdata['Credit']) {
                $activity  = wf_img_sized('skins/icon_active.gif', '', '', '12') . ' ' . __('Yes');
            }
        }


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
        //user extended address info rows
        $profile .= $this->getAddressExtenControls();
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
        if (!@$this->alterCfg['EMAILHIDE']) {
            $profile .= $this->addRow(__('Email'), $this->mail);
        }
        //payment ID data
        if ($this->alterCfg['OPENPAYZ_SUPPORT']) {
            $profile .= $this->addRow(__('Payment ID') . $this->getPbFastUrlControls(), $this->paymentid, true);
        }
        //LAT data row
        $profile .= $this->getUserLat();
        //Login row
        $profile .= $this->addRow(__('Login'), $this->userdata['login'], true);
        //Password row
        $profile .= $this->getUserPassword();
        //User IP data and extended networks controls if available
        $profile .= $this->addRow(__('IP') . ' ' . $this->getNasInfoControls($this->userdata['IP']), $this->userdata['IP'] . $this->getExtNetsControls() . $this->getNasInfoContrainer(), true);
        //MAC address row
        $profile .= $this->addRow(__('MAC') . ' ' . $this->getSearchmacControl() . ' ' . $this->getProfileFdbSearchControl(), $this->mac);
        //MeCulpa row
        $profile .= $this->getMeCulpaRaw();
        //User tariff row
        $profile .= $this->addRow(__('Tariff') . $this->getTariffInfoControls($this->userdata['Tariff']), $this->userdata['Tariff'] . $this->getTariffInfoContrainer(), true);
        //Tariff change row
        $profile .= $this->addRow(__('Planned tariff change') . $this->getTariffInfoControls($this->userdata['TariffChange'], true), $this->userdata['TariffChange'] . $this->getTariffInfoContrainer(true));
        //CaTv backlink if needed
        $profile .= $this->getCatvBacklinks();
        //Visor user backlink if user is primary
        $profile .= $this->getVisorBacklinks();
        //Speed override row
        $profile .= $this->addRow(__('Speed override'), $this->speedoverride);
        //Signup pricing row
        $profile .= $this->getSignupPricing();
        //User current cash row
        $profile .= $this->addRow(__('Balance') . $this->getEasyChargeController() . $this->getDeferredSaleController(), $this->getUserCash(), true);
        //User discount row
        $profile .= $this->getDiscountController();
        //User additional fee row
        $profile .= $this->getTaxSupController();
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
        //Karma controls here        
        $profile .= $this->getUserKarma();
        //Always online flag row
        $profile .= $this->addRow(__('Always Online'), web_trigger($this->userdata['AlwaysOnline']));
        //Detail stats flag row
        if (@$this->alterCfg['DSTAT_ENABLED']) {
            $profile .= $this->addRow(__('Disable detailed stats'), web_trigger($this->userdata['DisabledDetailStat']));
        }
        //Frozen aka passive flag row
        //passive time detection
        $passiveTimeLabel = '';
        if ($this->userdata['Passive']) {
            if ($this->userdata['PassiveTime']) {
                $passiveTimeLabel = wf_AjaxLoader();
                $passiveTimeLink = wf_AjaxLink('?module=passiveinfo&username=' . $this->login, ' (' . zb_formatTime($this->userdata['PassiveTime']) . ')', 'passivedatecontainer');
                $passiveTimeLabel .= wf_AjaxContainerSpan('passivedatecontainer', '', $passiveTimeLink);
            }
        }
        $profile .= $this->addRow(__('Freezed') . ' ' . $this->getEasyFreezeController(), $passiveicon . web_trigger($this->userdata['Passive']) . $passiveTimeLabel, true);

        if (isset($this->alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) && $this->alterCfg['FREEZE_DAYS_CHARGE_ENABLED']) {
            $FrozenAllQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $this->userdata['login'] . "';";
            $FrozenAll = simple_queryall($FrozenAllQuery);

            if (!empty($FrozenAll)) {
                foreach ($FrozenAll as $usr => $usrlogin) {
                    $profile .= $this->addRow(wf_nbsp(4) . __('Freeze days total amount'), $usrlogin['freeze_days_amount'], false, '50%');
                    $profile .= $this->addRow(wf_nbsp(4) . __('Freeze days used'), $usrlogin['freeze_days_used'], false, '50%');
                    $profile .= $this->addRow(wf_nbsp(4) . __('Freeze days available'), $usrlogin['freeze_days_amount'] - $usrlogin['freeze_days_used'], false, '50%');
                    $profile .= $this->addRow(wf_nbsp(4) . __('Workdays amount to restore freeze days'), $usrlogin['work_days_restore'], false, '50%');
                    $profile .= $this->addRow(wf_nbsp(4) . __('Days worked after freeze days used up'), $usrlogin['days_worked'], false, '50%');
                    $profile .= $this->addRow(wf_nbsp(4) . __('Workdays left to restore'), $usrlogin['work_days_restore'] - $usrlogin['days_worked'], false, '50%');
                }
            }
        }

        //Disable aka Down flag row
        $profile .= $this->addRow(__('Disabled'), $downicon . web_trigger($this->userdata['Down']), true);

        //Compact ONU signal here
        $profile .= $this->getPonSignalControlCompact();

        $profile .= $this->getSMSserviceSelectorControls();
        $profile .= $this->getDataExportPermissionTrigger();

        //Deal with it available tasks notification
        $profile .= $this->getUserDealWithItNotification();
        //Connection details  row
        $profile .= $this->getUserConnectionDetails();
        //User notes row
        $profile .= $this->addRow(__('Notes'), zb_UserGetNotes($this->login) . $this->getAdcommentsIndicator());

        $profile .= wf_tag('tbody', true);
        $profile .= wf_tag('table', true);
        $profile .= wf_tag('td', true); //end of main profile container 


        $profile .= wf_tag('td', false, '', 'valign="top" width="10%" class="profileplugincontainer"'); //profile plugins container
        $profile .= $this->plugins;
        $profile .= wf_tag('td', true); // end of plugins container

        $profile .= wf_tag('tr', true); // close profile+plugins row

        $profile .= wf_tag('tbody', true);
        $profile .= wf_tag('table', true); //end of all profile container
        //PseudoCRM lead related data here
        $profile .= $this->renderPseudoCRMActs();
        //profile switch port controls
        $profile .= $this->getSwitchAssignControls();
        //profile zabbix problen controls
        $profile .= $this->getZabbixProblemControls();
        //profile onu signal controls
        $profile .= $this->getPonSignalControl();
        //profile vlan controls
        $profile .= $this->getVlanAssignControls();
        //profile vlan online
        $profile .= $this->getVlanOnline();
        //profile qinq controls        
        $profile .= $this->getQinqPairControls();
        // profile RoS PPPoE session info
        $profile .= $this->getROSPPPoESessionData();
        //profile CPE controls
        $profile .= $this->getUserCpeControls();

        //Custom filelds display
        $profile .= $this->customFields->renderUserFields();

        //Tags add control and exiting tags listing
        if ($this->ubConfig->getAlterParam('USERPROFILE_TAG_SECTION_HIGHLIGHT')) {
            if (cfr('TAGS')) {
                $profile .= wf_tag('h2', false) . __('Tags');
                $profile .= wf_Link('?module=usertags&username=' . $this->login, web_add_icon(__('Add tag')), false);
                $profile .= wf_tag('h2', true);
            }

            $userTags = stg_show_user_tags($this->login);

            if (!empty($userTags)) {
                $profile .= wf_tag('div', false, '', 'style="margin-bottom: 15px; padding: 8px 11px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0px 2px 5px #A0A0A0; -webkit-box-shadow: 0px 2px 5px #A0A0A0; -moz-box-shadow: 0px 2px 5px #A0A0A0;"');
                $profile .= $userTags;
                $profile .= wf_tag('div', true);
            }
        } else {
            if (cfr('TAGS')) {
                $profile .= wf_Link('?module=usertags&username=' . $this->login, web_add_icon(__('Tags')), false);
            }

            $profile .= stg_show_user_tags($this->login, true);
        }

        //main profile controls here
        $profile .= $this->getMainControls();

        //Profile ending anchor for addcash links scroll
        $profile .= wf_tag('a', false, '', 'id="profileending"') . wf_tag('a', true);
        /**
         * Dinosaurs are my best friends
         * Through thick and thin, until the very end
         * People tell me, do not pretend
         * Stop living in your made up world again
         * But the dinosaurs, they`re real to me
         * They bring me up and make me happy
         * I wished all the world could see
         * The dinosaurs are a part of me
         */
        return ($profile);
    }
}
