<?php

/**
 * Base signup requests handling class
 */
class SignupRequests {

    /**
     * Contains available signup requests
     *
     * @var array
     */
    protected $requests = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altcfg = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Default whois service URL
     */
    const URL_WHOIS = '?module=whois&ip=';

    /**
     * Default module URL
     */
    const URL_ME = '?module=sigreq';

    /**
     * Creates new sigreq instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
    }

    /**
     * loads actual alter config into private property
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altcfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system messages helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * loads signup requests into private data property
     * 
     * @return void
     */
    protected function loadRequests() {
        $query = "SELECT * from `sigreq` ORDER BY `id` DESC;";
        $allreqs = simple_queryall($query);
        if (!empty($allreqs)) {
            $this->requests = $allreqs;
        }
    }

    /**
     * renders available signups data
     * 
     * @return void
     */
    public function renderAjListData() {
        $this->loadRequests();
        $result = '';
        $jsonAAData = array();
        $telepathy = new Telepathy(false, true, true);

        //additional comments indicator
        if ($this->altcfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('SIGREQ');
        }

        if (!empty($this->requests)) {
            foreach ($this->requests as $io => $eachreq) {

                $jsonItem = array();
                $jsonItem[] = $eachreq['id'];
                $jsonItem[] = $eachreq['date'];
                $jsonItem[] = wf_Link(self::URL_WHOIS . $eachreq['ip'], $eachreq['ip']);

                if (empty($eachreq['apt'])) {
                    $apt = 0;
                } else {
                    $apt = $eachreq['apt'];
                }
                $reqaddr = $eachreq['street'] . ' ' . $eachreq['build'] . '/' . $apt;
                $loginDetect = $telepathy->getLogin($reqaddr);

                $profileLink = (!empty($loginDetect)) ? ' ' . wf_Link('?module=userprofile&username=' . $loginDetect, web_profile_icon()) : '';
                $jsonItem[] = $reqaddr . $profileLink;
                $jsonItem[] = $eachreq['realname'];
                $jsonItem[] = $eachreq['phone'];

                if ($this->altcfg['ADCOMMENTS_ENABLED']) {
                    $commIndicator = ' ' . $adcomments->getCommentsIndicator($eachreq['id']);
                } else {
                    $commIndicator = '';
                }


                $actlinks = wf_Link('?module=sigreq&showreq=' . $eachreq['id'], wf_img('skins/icon_search_small.gif') . ' ' . __('Show'), true, '');
                $jsonItem[] = web_bool_led($eachreq['state']) . $commIndicator;
                $jsonItem[] = $actlinks;
                $jsonAAData[] = $jsonItem;
            }
        }

        $result = array("aaData" => $jsonAAData);
        die(json_encode($result));
    }

    /**
     * Render requests list
     * 
     * @return void
     */
    public function renderList() {
        //check database configuration table
        if (zb_CheckTableExists('sigreqconf')) {
            if (cfr('SIGREQCONF')) {
                $confControl = wf_Link('?module=sigreq&settings=true', wf_img('skins/settings.png', __('Settings')), false) . ' ';
            } else {
                $confControl = '';
            }
        } else {
            $confControl = '';
        }
        $viewControl = wf_Link('?module=sigreq&calendarview=true', wf_img('skins/icon_calendar.gif', __('As calendar')), false, '');
        $columns = array(__('ID'), __('Date'), __('IP'), __('Full address'), __('Real Name'), __('Phone'), __('Processed'), __('Actions'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajlist=true', false, __('Signup requests'), 100, $opts);

        show_window($confControl . __('Available signup requests') . ' ' . $viewControl, $result);
    }

    /**
     * renders available signups data in calendar view
     * 
     * @return void
     */
    public function renderCalendar() {
        $query = "SELECT * from `sigreq` ORDER BY `date` ASC";
        $all = simple_queryall($query);
        $result = '';
        $calendarData = '';
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);
                if ($each['state'] == 0) {
                    $coloring = "className : 'undone',";
                } else {
                    $coloring = '';
                }
                $calendarData.="
                      {
                        title: '" . $rawTime . ' ' . $each['street'] . ' ' . $each['build'] . '/' . $each['apt'] . "',
                        url: '?module=sigreq&showreq=" . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                       " . $coloring . "     
                   },
                    ";
            }
        }
        $result = wf_FullCalendar($calendarData);
        //check database configuration table
        if (zb_CheckTableExists('sigreqconf')) {
            $confControl = wf_Link('?module=sigreq&settings=true', wf_img('skins/settings.png', __('Settings')), false) . ' ';
        } else {
            $confControl = '';
        }
        $viewControl = wf_Link('?module=sigreq', wf_img('skins/icon_table.png', __('Grid view')), false, '');
        show_window($confControl . __('Available signup requests') . ' ' . $viewControl, $result);
    }

    /**
     * returns signup request data by selected ID
     * 
     * @param int $requid Existing signup request ID
     * 
     * @return array
     */
    protected function getData($reqid) {
        $requid = vf($reqid, 3);
        $query = "SELECT * from `sigreq` WHERE `id`='" . $reqid . "'";
        $result = simple_query($query);
        return($result);
    }

    /**
     * shows selected signup request by its ID
     * 
     * @param int $requid Existing signup request ID
     * 
     * @return void
     */
    public function showRequest($reqid) {
        $requid = vf($reqid, 3);
        $reqdata = $this->getData($reqid);

        if (empty($reqdata['apt'])) {
            $apt = 0;
        } else {
            $apt = $reqdata['apt'];
        }

        $shortaddress = $reqdata['street'] . ' ' . $reqdata['build'] . '/' . $apt;
        $taskCreateControls = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), ts_TaskCreateFormSigreq($shortaddress, $reqdata['phone']), '', '420', '500');

        $cells = wf_TableCell(__('Date'));
        $cells.=wf_TableCell($reqdata['date'] . ' ' . $taskCreateControls);
        $rows = wf_TableRow($cells, 'row3');

        $whoislink = self::URL_WHOIS . $reqdata['ip'];
        $iplookup = wf_Link($whoislink, $reqdata['ip'], false, '');

        $cells = wf_TableCell(__('IP'));
        $cells.=wf_TableCell($iplookup);
        $rows.= wf_TableRow($cells, 'row3');

        $reqAddress = $reqdata['street'] . ' ' . $reqdata['build'] . '/' . $apt;
        
        //Construct capability create form if enabled
        if ($this->altcfg['CAPABDIR_ENABLED']) {
            $capabDir = new CapabilitiesDirectory(true);
            $capabCreateForm = $capabDir->createForm($reqAddress, $reqdata['phone'], $reqdata['service'] . ' ' . $reqdata['notes']);
            $capabControl = wf_modal(wf_img_sized('skins/icon_cake.png', __('Available connection capabilities'), 10), __('Create connection capability'), $capabCreateForm, '', '400', '300');
        } else {
            $capabControl = '';
        }


        $cells = wf_TableCell(__('Full address'));
        $cells.=wf_TableCell($reqAddress . ' ' . $capabControl);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Real Name'));
        $cells.=wf_TableCell($reqdata['realname']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Phone'));
        $cells.=wf_TableCell($reqdata['phone']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Service'));
        $cells.=wf_TableCell($reqdata['service']);
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Processed'));
        $cells.=wf_TableCell(web_bool_led($reqdata['state']));
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Notes'));
        $notes = nl2br($reqdata['notes']);
        $notes = str_replace('Tariff:', __('Tariff') . ':', $notes);
        $notes = str_replace('Email:', __('Email') . ':', $notes);
        $cells.=wf_TableCell($notes);
        $rows.=wf_TableRow($cells, 'row3');

        $result = wf_TableBody($rows, '100%', '0', 'glamour');


        $actlinks = wf_BackLink('?module=sigreq');

        if (cfr('SIGREQEDIT')) {
            if ($reqdata['state'] == 0) {
                $actlinks.=wf_Link('?module=sigreq&reqdone=' . $reqid, wf_img_sized('skins/icon_active.gif', '', '10') . ' ' . __('Close'), false, 'ubButton');
            } else {
                $actlinks.=wf_Link('?module=sigreq&requndone=' . $reqid, wf_img_sized('skins/icon_inactive.gif', '', '10') . ' ' . __('Open'), false, 'ubButton');
            }
        }

        if (cfr('SIGREQDELETE')) {
            $deletelink = ' ' . wf_JSAlert("?module=sigreq&deletereq=" . $reqid, web_delete_icon(), $this->messages->getDeleteAlert());
        } else {
            $deletelink = '';
        }

        show_window(__('Signup request') . ': ' . $reqid . $deletelink, $result);
        show_window('', $actlinks);

        //additional comments
        if ($this->altcfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('SIGREQ');
            show_window(__('Additional comments'), $adcomments->renderComments($requid));
        }
    }

    /**
     * Marks signup request as done in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */
    public function setDone($reqid) {
        $requid = vf($reqid, 3);
        simple_update_field('sigreq', 'state', '1', "WHERE `id`='" . $reqid . "'");
        log_register('SIGREQ DONE [' . $reqid . ']');
    }

    /**
     * Marks signup request as undone in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */
    public function setUnDone($reqid) {
        $requid = vf($reqid, 3);
        simple_update_field('sigreq', 'state', '0', "WHERE `id`='" . $reqid . "'");
        log_register('SIGREQ UNDONE [' . $reqid . ']');
    }

    /**
     * Deletes signup request as done in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */
    public function deleteReq($reqid) {
        $requid = vf($reqid, 3);
        $query = "DELETE from `sigreq` WHERE `id`='" . $reqid . "'";
        nr_query($query);
        log_register('SIGREQ DELETE [' . $reqid . ']');
    }

    /**
     * Gets all undone requests count, used by taskbar notifier
     * 
     * @return int
     */
    public function getAllNewCount() {
        $query = "SELECT COUNT(`id`) from `sigreq` WHERE `state`='0'";
        $result = simple_query($query);
        $result = $result['COUNT(`id`)'];
        return ($result);
    }

}

/**
 * sigreq configuration class
 */
class SignupConfig {

    protected $configRaw = array();

    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads sigreqconf config from database
     *  
     * @return void 
     */
    protected function loadConfig() {
        $query = "SELECT * from `sigreqconf`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->configRaw[$each['key']] = $each['value'];
            }
        }
    }

    /**
     * checks key existance into raw config array
     * 
     * @param string $key key value to check
     * 
     * @return bool
     */
    protected function checkConf($key) {
        if (isset($this->configRaw[$key])) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * deletes key from database config
     * 
     * @param string $key key to delete from database config
     * 
     * @return void
     */
    protected function deleteConf($key) {
        $key = mysql_real_escape_string($key);
        $query = "DELETE from `sigreqconf` WHERE `key`='" . $key . "';";
        nr_query($query);
    }

    /**
     * creates/replaces config key with some data into database config
     * 
     * @param string $key key set data
     * @param string $data value data to set
     * 
     * @return void
     */
    protected function setConf($key, $data) {
        $key = mysql_real_escape_string($key);
        $data = mysql_real_escape_string($data);
        $this->deleteConf($key);
        $query = "INSERT INTO `sigreqconf` (`id`, `key`, `value`) VALUES (NULL, '" . $key . "', '" . $data . "'); ";
        nr_query($query);
    }

    /**
     * checks diff key text data
     * 
     * @param string $key key to check
     * @param string $data data to check diff
     * 
     * @return bool
     */
    protected function diffConf($key, $data) {
        if (isset($this->configRaw[$key])) {
            if ($this->configRaw[$key] == $data) {
                return (false);
            } else {
                return (true);
            }
        } else {
            return (true);
        }
    }

    /**
     * renders editing form
     * 
     * @return string
     */
    public function renderForm() {
        $inputs = '';

        $cityDispFlag = $this->checkConf('CITY_DISPLAY');
        $citySelFlag = $this->checkConf('CITY_SELECTABLE');
        $streetSelFlag = $this->checkConf('STREET_SELECTABLE');
        $emailDispFlag = $this->checkConf('EMAIL_DISPLAY');
        $spamDispFlag = $this->checkConf('SPAM_TRAPS');
        $cachingFlag = $this->checkConf('CACHING');

        $inputs.= wf_CheckInput('newcitydisplay', __('Display city input'), true, $cityDispFlag);
        $inputs.= wf_CheckInput('newcityselectable', __('Show city input as combobox'), true, $citySelFlag);
        $inputs.= wf_CheckInput('newstreetselectable', __('Show street input as combobox'), true, $streetSelFlag);
        $inputs.= wf_CheckInput('newemaildisplay', __('Display email field'), true, $emailDispFlag);
        $inputs.= wf_CheckInput('newespamtraps', __('Render spambots protection traps'), true, $spamDispFlag);
        $inputs.= wf_CheckInput('newcaching', __('Database connections caching'), true, $cachingFlag);

        $inputs.= wf_TextInput('newispname', __('Your ISP Name'), @$this->configRaw['ISP_NAME'], true, 25);
        $inputs.= wf_TextInput('newispurl', __('Your ISP site URL'), @$this->configRaw['ISP_URL'], true, 25);
        $inputs.= wf_TextInput('newisplogo', __('Your ISP logo URL'), @$this->configRaw['ISP_LOGO'], true, 25);
        $inputs.= wf_tag('label') . __('Sidebar text - contacts, phones etc.') . ' (HTML)' . wf_tag('label', true) . wf_tag('br');
        $inputs.= wf_TextArea('newsidebartext', '', @$this->configRaw['SIDEBAR_TEXT'], true, '50x10');
        $inputs.= wf_tag('label') . __('Greeting text') . ' (HTML)' . wf_tag('label', true) . wf_tag('br');
        $inputs.= wf_TextArea('newgreetingtext', '', @$this->configRaw['GREETING_TEXT'], true, '50x5');
        $inputs.= wf_TextInput('newservices', __('Services offered') . ' ' . __('(separator - comma)'), @$this->configRaw['SERVICES'], true, 25);
        $inputs.= wf_TextInput('newtariffs', __('Tariffs offered') . ' ' . __('(separator - comma)'), @$this->configRaw['TARIFFS'], true, 25);
        $inputs.= wf_TextInput('newhideouts', __('City and streets hide lists') . ' ' . __('(separator - comma)'), @$this->configRaw['HIDEOUTS'], true, 25);
        $inputs.= wf_HiddenInput('changesettings', 'true');
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        $result.= wf_BackLink('?module=sigreq');
        return ($result);
    }

    /**
     * saves config to database if needed
     * 
     * @return void
     */
    public function save() {
        //city display
        if (isset($_POST['newcitydisplay'])) {
            if (!$this->checkConf('CITY_DISPLAY')) {
                $this->setConf('CITY_DISPLAY', 'NOP');
                log_register('SIGREQCONF ENABLED CITY_DISPLAY');
            }
        } else {
            if ($this->checkConf('CITY_DISPLAY')) {
                $this->deleteConf('CITY_DISPLAY');
                log_register('SIGREQCONF DISABLED CITY_DISPLAY');
            }
        }
        //city combobox
        if (isset($_POST['newcityselectable'])) {
            if (!$this->checkConf('CITY_SELECTABLE')) {
                $this->setConf('CITY_SELECTABLE', 'NOP');
                log_register('SIGREQCONF ENABLED CITY_SELECTABLE');
            }
        } else {
            if ($this->checkConf('CITY_SELECTABLE')) {
                $this->deleteConf('CITY_SELECTABLE');
                log_register('SIGREQCONF DISABLED CITY_SELECTABLE');
            }
        }

        //street combobox
        if (isset($_POST['newstreetselectable'])) {
            if (!$this->checkConf('STREET_SELECTABLE')) {
                $this->setConf('STREET_SELECTABLE', 'NOP');
                log_register('SIGREQCONF ENABLED STREET_SELECTABLE');
            }
        } else {
            if ($this->checkConf('STREET_SELECTABLE')) {
                $this->deleteConf('STREET_SELECTABLE');
                log_register('SIGREQCONF DISABLED STREET_SELECTABLE');
            }
        }

        //mail input
        if (isset($_POST['newemaildisplay'])) {
            if (!$this->checkConf('EMAIL_DISPLAY')) {
                $this->setConf('EMAIL_DISPLAY', 'NOP');
                log_register('SIGREQCONF ENABLED EMAIL_DISPLAY');
            }
        } else {
            if ($this->checkConf('EMAIL_DISPLAY')) {
                $this->deleteConf('EMAIL_DISPLAY');
                log_register('SIGREQCONF DISABLED EMAIL_DISPLAY');
            }
        }
        //spamtraps
        if (isset($_POST['newespamtraps'])) {
            if (!$this->checkConf('SPAM_TRAPS')) {
                $this->setConf('SPAM_TRAPS', 'NOP');
                log_register('SIGREQCONF ENABLED SPAM_TRAPS');
            }
        } else {
            if ($this->checkConf('SPAM_TRAPS')) {
                $this->deleteConf('SPAM_TRAPS');
                log_register('SIGREQCONF DISABLED SPAM_TRAPS');
            }
        }
        //caching
        if (isset($_POST['newcaching'])) {
            if (!$this->checkConf('CACHING')) {
                $this->setConf('CACHING', 'NOP');
                log_register('SIGREQCONF ENABLED CACHING');
            }
        } else {
            if ($this->checkConf('CACHING')) {
                $this->deleteConf('CACHING');
                log_register('SIGREQCONF DISABLED CACHING');
            }
        }
        //isp name
        if (isset($_POST['newispname'])) {
            if ($this->diffConf('ISP_NAME', $_POST['newispname'])) {
                $this->setConf('ISP_NAME', $_POST['newispname']);
                log_register('SIGREQCONF CHANGED ISP_NAME');
            }
        }

        //isp url
        if (isset($_POST['newispurl'])) {
            if ($this->diffConf('ISP_URL', $_POST['newispurl'])) {
                $this->setConf('ISP_URL', $_POST['newispurl']);
                log_register('SIGREQCONF CHANGED ISP_URL');
            }
        }

        //isp logo
        if (isset($_POST['newisplogo'])) {
            if ($this->diffConf('ISP_LOGO', $_POST['newisplogo'])) {
                $this->setConf('ISP_LOGO', $_POST['newisplogo']);
                log_register('SIGREQCONF CHANGED ISP_LOGO');
            }
        }


        //sidebar
        if (isset($_POST['newsidebartext'])) {
            if ($this->diffConf('SIDEBAR_TEXT', $_POST['newsidebartext'])) {
                $this->setConf('SIDEBAR_TEXT', $_POST['newsidebartext']);
                log_register('SIGREQCONF CHANGED SIDEBAR_TEXT');
            }
        }

        //greeting
        if (isset($_POST['newgreetingtext'])) {
            if ($this->diffConf('GREETING_TEXT', $_POST['newgreetingtext'])) {
                $this->setConf('GREETING_TEXT', $_POST['newgreetingtext']);
                log_register('SIGREQCONF CHANGED GREETING_TEXT');
            }
        }

        //services
        if (isset($_POST['newservices'])) {
            if ($this->diffConf('SERVICES', $_POST['newservices'])) {
                $this->setConf('SERVICES', $_POST['newservices']);
                log_register('SIGREQCONF CHANGED SERVICES');
            }
        }

        //tariffs
        if (isset($_POST['newtariffs'])) {
            if ($this->diffConf('TARIFFS', $_POST['newtariffs'])) {
                $this->setConf('TARIFFS', $_POST['newtariffs']);
                log_register('SIGREQCONF CHANGED TARIFFS');
            }
        }

        //hideouts
        if (isset($_POST['newhideouts'])) {
            if ($this->diffConf('HIDEOUTS', $_POST['newhideouts'])) {
                $this->setConf('HIDEOUTS', $_POST['newhideouts']);
                log_register('SIGREQCONF CHANGED HIDEOUTS');
            }
        }
    }

}

?>