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
     * Field length limits matching sigreq columns
     */
    const LEN_STREET = 255;
    const LEN_BUILD = 40;
    const LEN_APT = 40;
    const LEN_REALNAME = 255;
    const LEN_PHONE = 255;
    const LEN_SERVICE = 255;
    const LEN_NOTES = 4096;
    const LEN_EMAIL = 255;
    const LEN_IP = 40;

    /**
     * Default service stored when offered services list is empty
     */
    const DEFAULT_SERVICE = 'Internet';

    /**
     * Public form config flags and strings
     *
     * @var array
     */
    protected $publicConfig = array();

    /**
     * Hidden city/street names as name=>name
     *
     * @var array
     */
    protected $hideouts = array();

    /**
     * Available cities as name=>name after HIDEOUTS filter
     *
     * @var array
     */
    protected $cities = array();

    /**
     * Available streets as name=>name after HIDEOUTS filter
     *
     * @var array
     */
    protected $streets = array();

    /**
     * Offered services as name=>name
     *
     * @var array
     */
    protected $services = array();

    /**
     * Offered tariffs as name=>name
     *
     * @var array
     */
    protected $tariffs = array();

    /**
     * Public form data already loaded flag
     *
     * @var bool
     */
    protected $publicFormLoaded = false;

    /**
     * NyanORM instance for sigreq table
     *
     * @var object
     */
    protected $sigreqDb = '';

    /**
     * Creates new sigreq instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->initDatabase();
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
     * Inits NyanORM instance for signup requests table
     *
     * @return void
     */
    protected function initDatabase() {
        $this->sigreqDb = new NyanORM('sigreq');
    }

    /**
     * loads signup requests into private data property
     * 
     * @return void
     */
    protected function loadRequests() {
        $this->sigreqDb->orderBy('id', 'DESC');
        $allreqs = $this->sigreqDb->getAll();
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
                $jsonItem[] = ubRouting::filters($reqaddr, 'safe') . $profileLink;
                $jsonItem[] = ubRouting::filters($eachreq['realname'], 'safe');
                $jsonItem[] = ubRouting::filters($eachreq['phone'], 'safe');

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
        $this->sigreqDb->orderBy('date', 'ASC');
        $all = $this->sigreqDb->getAll();
        $result = '';
        $calendarData = '';
        $confControl = '';
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $coloring = '';
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);
                if ($each['state'] == 0) {
                    $coloring = "className : 'undone',";
                }
                $calendarData.="
                      {
                        title: '" . $rawTime . ' ' . ubRouting::filters($each['street'], 'safe') . ' ' . ubRouting::filters($each['build'], 'safe') . '/' . ubRouting::filters($each['apt'], 'safe') . "',
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
        }
        $viewControl = wf_Link('?module=sigreq', wf_img('skins/icon_table.png', __('Grid view')), false, '');
        show_window($confControl . __('Available signup requests') . ' ' . $viewControl, $result);
    }

    /**
     * returns signup request data by selected ID
     * 
     * @param int $reqid Existing signup request ID
     * 
     * @return array
     */
    protected function getData($reqid) {
        $result = array();
        $requid = ubRouting::filters($reqid, 'int');
        $this->sigreqDb->where('id', '=', $requid);
        $all = $this->sigreqDb->getAll();
        if (!empty($all)) {
            $result = $all[0];
        }
        return ($result);
    }

    /**
     * shows selected signup request by its ID
     * 
     * @param int $reqid Existing signup request ID
     * 
     * @return void
     */
    public function showRequest($reqid) {
        $requid = ubRouting::filters($reqid, 'int');
        $reqdata = $this->getData($requid);
        $deletelink = '';
        $result = '';

        if (!empty($reqdata)) {
        if (empty($reqdata['apt'])) {
            $apt = 0;
        } else {
            $apt = $reqdata['apt'];
        }

        $shortaddress = $reqdata['street'] . ' ' . $reqdata['build'] . '/' . $apt;
        $shortaddress = ubRouting::filters($shortaddress, 'safe');
        $taskCreateControls = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), ts_TaskCreateFormSigreq($shortaddress, ubRouting::filters($reqdata['phone'], 'safe')), '', '420', '500');

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
        $cells.=wf_TableCell(ubRouting::filters($reqAddress, 'safe') . ' ' . $capabControl);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Real Name'));
        $cells.=wf_TableCell(ubRouting::filters($reqdata['realname'], 'safe'));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Phone'));
        $cells.=wf_TableCell(ubRouting::filters($reqdata['phone'], 'safe'));
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Service'));
        $cells.=wf_TableCell(ubRouting::filters($reqdata['service'], 'safe'));
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Processed'));
        $cells.=wf_TableCell(web_bool_led($reqdata['state']));
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Notes'));
        $notes = nl2br(ubRouting::filters($reqdata['notes'], 'safe'));
        $notes = str_replace('Tariff:', __('Tariff') . ':', $notes);
        $notes = str_replace('Email:', __('Email') . ':', $notes);
        $cells.=wf_TableCell($notes);
        $rows.=wf_TableRow($cells, 'row3');

        $result = wf_TableBody($rows, '100%', '0', 'glamour');
    } else {
        $result = $this->messages->getStyledMessage(__('Signup request').' ['.$requid.'] '.__('not exists'), 'error');
    }

        $actlinks = wf_BackLink('?module=sigreq');

        if (!empty($reqdata)) {
        if (cfr('SIGREQEDIT')) {
            if ($reqdata['state'] == 0) {
                $actlinks.=wf_Link('?module=sigreq&reqdone=' . $requid, wf_img_sized('skins/icon_active.gif', '', '10') . ' ' . __('Close'), false, 'ubButton');
            } else {
                $actlinks.=wf_Link('?module=sigreq&requndone=' . $requid, wf_img_sized('skins/icon_inactive.gif', '', '10') . ' ' . __('Open'), false, 'ubButton');
            }
        }
   

        if (cfr('SIGREQDELETE')) {
            $deletelink = ' ' . wf_JSAlert("?module=sigreq&deletereq=" . $requid, web_delete_icon(), $this->messages->getDeleteAlert());
        } 
        }

        show_window(__('Signup request') . ': ' . $requid . $deletelink, $result);
        show_window('', $actlinks);

        //additional comments
        if (!empty($reqdata)) {
        if ($this->altcfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('SIGREQ');
            show_window(__('Additional comments'), $adcomments->renderComments($requid));
        }
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
        $requid = ubRouting::filters($reqid, 'int');
        $this->sigreqDb->where('id', '=', $requid);
        $this->sigreqDb->data('state', '1');
        $this->sigreqDb->save();
        log_register('SIGREQ DONE [' . $requid . ']');
    }

    /**
     * Marks signup request as undone in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */
    public function setUnDone($reqid) {
        $requid = ubRouting::filters($reqid, 'int');
        $this->sigreqDb->where('id', '=', $requid);
        $this->sigreqDb->data('state', '0');
        $this->sigreqDb->save();
        log_register('SIGREQ UNDONE [' . $requid . ']');
    }

    /**
     * Deletes signup request as done in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */
    public function deleteReq($reqid) {
        $requid = ubRouting::filters($reqid, 'int');
        $this->sigreqDb->where('id', '=', $requid);
        $this->sigreqDb->delete();
        log_register('SIGREQ DELETE [' . $requid . ']');
    }

    /**
     * Gets all undone requests count, used by taskbar notifier
     * 
     * @return int
     */
    public function getAllNewCount() {
        $this->sigreqDb->where('state', '=', '0');
        $result = $this->sigreqDb->getFieldsCount('id');
        return ($result);
    }

    /**
     * Splits CSV config value into name=>name map
     *
     * @param string $raw
     *
     * @return array
     */
    protected function parseCsvMap($raw) {
        $result = array();
        if (!empty($raw)) {
            $tmpArr = explode(',', $raw);
            if (!empty($tmpArr)) {
                foreach ($tmpArr as $io => $each) {
                    $item = trim($each);
                    if ($item != '') {
                        $result[$item] = $item;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Loads sigreqconf, cities, streets, services, tariffs and applies HIDEOUTS
     *
     * @return void
     */
    protected function loadPublicFormData() {
        if (!$this->publicFormLoaded) {
            $this->publicConfig = array(
                'CITY_DISPLAY' => false,
                'CITY_SELECTABLE' => false,
                'STREET_SELECTABLE' => false,
                'EMAIL_DISPLAY' => false,
                'SPAM_TRAPS' => false,
                'NOTES_DISPLAY' => true,
                'CACHING' => false,
                'ISP_NAME' => '',
                'ISP_URL' => '',
                'ISP_LOGO' => '',
                'SIDEBAR_TEXT' => '',
                'GREETING_TEXT' => ''
            );

            $boolFlags = array(
                'CITY_DISPLAY' => 1,
                'CITY_SELECTABLE' => 1,
                'STREET_SELECTABLE' => 1,
                'EMAIL_DISPLAY' => 1,
                'SPAM_TRAPS' => 1,
                'CACHING' => 1
            );
            $stringKeys = array(
                'ISP_NAME' => 1,
                'ISP_URL' => 1,
                'ISP_LOGO' => 1,
                'SIDEBAR_TEXT' => 1,
                'GREETING_TEXT' => 1
            );

            $confDb = new NyanORM('sigreqconf');
            $allConf = $confDb->getAll();
            if (!empty($allConf)) {
                foreach ($allConf as $io => $each) {
                    $confKey = $each['key'];
                    $confValue = $each['value'];
                    if (isset($boolFlags[$confKey])) {
                        $this->publicConfig[$confKey] = true;
                    } else {
                        if (isset($stringKeys[$confKey])) {
                            $this->publicConfig[$confKey] = $confValue;
                        } else {
                            if ($confKey == 'SERVICES') {
                                $this->services = $this->parseCsvMap($confValue);
                            } else {
                                if ($confKey == 'TARIFFS') {
                                    $this->tariffs = $this->parseCsvMap($confValue);
                                } else {
                                    if ($confKey == 'HIDEOUTS') {
                                        $this->hideouts = $this->parseCsvMap($confValue);
                                    } else {
                                        if ($confKey == 'NOTES_HIDDEN') {
                                            $this->publicConfig['NOTES_DISPLAY'] = false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $cityDb = new NyanORM('city');
            $cityDb->selectable('id,cityname');
            $cityDb->orderBy('id', 'ASC');
            $allCities = $cityDb->getAll();
            if (!empty($allCities)) {
                foreach ($allCities as $io => $each) {
                    $cityName = $each['cityname'];
                    if (!isset($this->hideouts[$cityName])) {
                        $this->cities[$cityName] = $cityName;
                    }
                }
            }

            $streetDb = new NyanORM('street');
            $streetDb->selectable('id,streetname');
            $allStreets = $streetDb->getAll();
            if (!empty($allStreets)) {
                foreach ($allStreets as $io => $each) {
                    $streetName = $each['streetname'];
                    if (!isset($this->hideouts[$streetName])) {
                        $this->streets[$streetName] = $streetName;
                    }
                }
            }

            if (!empty($this->streets)) {
                natsort($this->streets);
            }

            $this->publicFormLoaded = true;
        }
    }

    /**
     * Returns JSON payload for public signup3 form. HIDEOUTS stay on backend.
     *
     * @return array
     */
    public function getPublicPayload() {
        $this->loadPublicFormData();
        $result = array(
            'error' => false,
            'config' => array(
                'CITY_DISPLAY' => $this->publicConfig['CITY_DISPLAY'],
                'CITY_SELECTABLE' => $this->publicConfig['CITY_SELECTABLE'],
                'STREET_SELECTABLE' => $this->publicConfig['STREET_SELECTABLE'],
                'EMAIL_DISPLAY' => $this->publicConfig['EMAIL_DISPLAY'],
                'SPAM_TRAPS' => $this->publicConfig['SPAM_TRAPS'],
                'NOTES_DISPLAY' => $this->publicConfig['NOTES_DISPLAY'],
                'CACHING' => $this->publicConfig['CACHING'],
                'ISP_NAME' => $this->publicConfig['ISP_NAME'],
                'ISP_URL' => $this->publicConfig['ISP_URL'],
                'ISP_LOGO' => $this->publicConfig['ISP_LOGO'],
                'SIDEBAR_TEXT' => $this->publicConfig['SIDEBAR_TEXT'],
                'GREETING_TEXT' => $this->publicConfig['GREETING_TEXT'],
                'SERVICES' => array_values($this->services),
                'TARIFFS' => array_values($this->tariffs)
            ),
            'cities' => array_values($this->cities),
            'streets' => array_values($this->streets)
        );
        return ($result);
    }

    /**
     * Extracts a scalar string field from raw API data
     *
     * @param array $raw
     * @param string $name
     *
     * @return string
     */
    protected function extractField($raw, $name) {
        $result = '';
        if (isset($raw[$name])) {
            if (!is_array($raw[$name]) and !is_object($raw[$name])) {
                $result = $raw[$name];
            }
        }
        return ($result);
    }

    /**
     * Trim and drop NUL bytes without mutating letters/apostrophes
     *
     * @param string $data
     *
     * @return string
     */
    protected function plainText($data) {
        $result = '';
        if (!is_array($data) and !is_object($data)) {
            $result = trim($data);
            $result = ubRouting::filters($result, 'nb');
        }
        return ($result);
    }

    /**
     * Trims, strips tags/NUL and cuts value to column length
     *
     * @param string $data
     * @param int $maxLen
     * @param bool $emsafe
     *
     * @return string
     */
    protected function sanitizeText($data, $maxLen, $emsafe = false) {
        $result = '';
        if (!is_array($data) and !is_object($data)) {
            $result = trim($data);
            $result = ubRouting::filters($result, 'nb');
            if ($emsafe) {
                $result = ubRouting::filters($result, 'emsafe');
            } else {
                $result = ubRouting::filters($result, 'safe');
            }
            if (strlen($result) > $maxLen) {
                $result = substr($result, 0, $maxLen);
            }
        }
        return ($result);
    }

    /**
     * Keeps only digits in a phone number
     *
     * @param string $data
     *
     * @return string
     */
    protected function sanitizePhone($data) {
        $result = '';
        if (!is_array($data) and !is_object($data)) {
            $digits = ubRouting::filters(trim($data), 'int');
            if (is_string($digits) or is_numeric($digits)) {
                $result = strval($digits);
            }
            if (strlen($result) > self::LEN_PHONE) {
                $result = substr($result, 0, self::LEN_PHONE);
            }
        }
        return ($result);
    }

    /**
     * Final strip_tags pass before storing a field
     *
     * @param string $data
     *
     * @return string
     */
    protected function finalizeField($data) {
        $result = '';
        if (!is_array($data) and !is_object($data)) {
            $result = strip_tags($data);
        }
        return ($result);
    }

    /**
     * Validates and cuts visitor IP
     *
     * @param string $data
     *
     * @return string
     */
    protected function sanitizeIp($data) {
        $result = '';
        if (!is_array($data) and !is_object($data)) {
            $rawIp = trim($data);
            if (filter_var($rawIp, FILTER_VALIDATE_IP)) {
                if (strlen($rawIp) <= self::LEN_IP) {
                    $result = $rawIp;
                }
            }
        }
        return ($result);
    }

    /**
     * Detects filled honeypot fields
     *
     * @param array $raw
     *
     * @return bool
     */
    protected function isHoneypotFilled($raw) {
        $result = false;
        $traps = array('surname', 'lastname', 'seenoevil', 'mobile');
        if (!empty($raw)) {
            foreach ($traps as $io => $trap) {
                $trapValue = $this->extractField($raw, $trap);
                if (trim($trapValue) != '') {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Creates signup request from public API payload.
     *
     * @param array $raw
     *
     * @return array
     */
    public function createFromApi($raw) {
        $result = array(
            'error' => true,
            'created' => false,
            'id' => 0,
            'error_message' => 'EMPTY_REQUEST'
        );

        if (!empty($raw) and is_array($raw)) {
            $this->loadPublicFormData();
            if ($this->isHoneypotFilled($raw)) {
                $result['error'] = false;
                $result['created'] = true;
                $result['id'] = 0;
                $result['error_message'] = '';
            } else {
                $cityRaw = $this->plainText($this->extractField($raw, 'city'));
                $streetRaw = $this->plainText($this->extractField($raw, 'street'));
                $serviceRaw = $this->plainText($this->extractField($raw, 'service'));
                $tariffRaw = $this->plainText($this->extractField($raw, 'tariff'));
                $allowFail = '';
                $hiddenHit = false;
                $city = '';
                $street = '';
                $service = '';
                $tariff = '';

                if ($this->publicConfig['CITY_DISPLAY']) {
                    if ($this->publicConfig['CITY_SELECTABLE']) {
                        if ($cityRaw != '') {
                            if (isset($this->cities[$cityRaw])) {
                                $city = $this->cities[$cityRaw];
                            } else {
                                $allowFail = 'INVALID_CITY';
                            }
                        }
                    } else {
                        $city = $this->sanitizeText($cityRaw, self::LEN_STREET);
                        if (($city != '') and isset($this->hideouts[$city])) {
                            $hiddenHit = true;
                        }
                    }
                }

                if ($this->publicConfig['STREET_SELECTABLE']) {
                    if (isset($this->streets[$streetRaw])) {
                        $street = $this->streets[$streetRaw];
                    } else {
                        $allowFail = 'INVALID_STREET';
                    }
                } else {
                    $street = $this->sanitizeText($streetRaw, self::LEN_STREET);
                    if (isset($this->hideouts[$street])) {
                        $hiddenHit = true;
                    }
                }

                if (!empty($this->services)) {
                    if ($serviceRaw != '') {
                        if (isset($this->services[$serviceRaw])) {
                            $service = $this->services[$serviceRaw];
                        } else {
                            $allowFail = 'INVALID_SERVICE';
                        }
                    }
                } else {
                    $service = self::DEFAULT_SERVICE;
                }

                if (!empty($this->tariffs)) {
                    if ($tariffRaw != '') {
                        if (isset($this->tariffs[$tariffRaw])) {
                            $tariff = $this->tariffs[$tariffRaw];
                        } else {
                            $allowFail = 'INVALID_TARIFF';
                        }
                    }
                }

                $build = $this->sanitizeText($this->extractField($raw, 'build'), self::LEN_BUILD);
                $apt = $this->sanitizeText($this->extractField($raw, 'apt'), self::LEN_APT);
                $realname = $this->sanitizeText($this->extractField($raw, 'realname'), self::LEN_REALNAME);
                $phone = $this->sanitizePhone($this->extractField($raw, 'phone'));
                $email = '';
                if ($this->publicConfig['EMAIL_DISPLAY']) {
                    $email = $this->sanitizeText($this->extractField($raw, 'email'), self::LEN_EMAIL);
                }
                $notes = '';
                if ($this->publicConfig['NOTES_DISPLAY']) {
                    $notes = $this->sanitizeText($this->extractField($raw, 'notes'), self::LEN_NOTES, true);
                }
                $ip = $this->sanitizeIp($this->extractField($raw, 'ip'));

                if ($email != '') {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $allowFail = 'INVALID_EMAIL';
                    }
                }

                if ($allowFail != '') {
                    $result['error_message'] = $allowFail;
                } else {
                    if (($street == '') or ($build == '') or ($realname == '') or ($phone == '')) {
                        $result['error_message'] = 'REQUIRED_FIELDS';
                    } else {
                        if ($hiddenHit) {
                            $result['error_message'] = 'HIDDEN_ADDRESS';
                        } else {
                            $streetPacked = $street;
                            if ($city != '') {
                                $streetPacked = $city . ' ' . $street;
                                if (strlen($streetPacked) > self::LEN_STREET) {
                                    $streetPacked = substr($streetPacked, 0, self::LEN_STREET);
                                }
                            }
                            if ($apt == '') {
                                $apt = '0';
                            }
                            if ($service == '') {
                                $service = 'No';
                            }

                            $notesPacked = '';
                            if ($notes != '') {
                                $notesPacked .= $notes . "\n";
                            }
                            if ($tariff != '') {
                                $notesPacked .= 'Tariff: ' . $tariff . "\n";
                            }
                            if ($email != '') {
                                $notesPacked .= 'Email: ' . $email . "\n";
                            }
                            if (strlen($notesPacked) > self::LEN_NOTES) {
                                $notesPacked = substr($notesPacked, 0, self::LEN_NOTES);
                            }

                            $this->sigreqDb->data('date', ubRouting::filters($this->finalizeField(date('Y-m-d H:i:s')), 'mres'));
                            $this->sigreqDb->data('state', ubRouting::filters($this->finalizeField('0'), 'mres'));
                            $this->sigreqDb->data('ip', ubRouting::filters($this->finalizeField($ip), 'mres'));
                            $this->sigreqDb->data('street', ubRouting::filters($this->finalizeField($streetPacked), 'mres'));
                            $this->sigreqDb->data('build', ubRouting::filters($this->finalizeField($build), 'mres'));
                            $this->sigreqDb->data('apt', ubRouting::filters($this->finalizeField($apt), 'mres'));
                            $this->sigreqDb->data('realname', ubRouting::filters($this->finalizeField($realname), 'mres'));
                            $this->sigreqDb->data('phone', ubRouting::filters($this->finalizeField($phone), 'mres'));
                            $this->sigreqDb->data('service', ubRouting::filters($this->finalizeField($service), 'mres'));
                            $this->sigreqDb->data('notes', ubRouting::filters($this->finalizeField($notesPacked), 'mres'));
                            $this->sigreqDb->create();
                            $newId = $this->sigreqDb->getLastId();
                            if (!empty($newId)) {
                                log_register('SIGREQ CREATED [' . $newId . ']');
                                $result['error'] = false;
                                $result['created'] = true;
                                $result['id'] = $newId;
                                $result['error_message'] = '';
                            } else {
                                $result['error_message'] = 'CREATE_FAILED';
                            }
                        }
                    }
                }
            }
        }

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
        $key = ubRouting::filters($key, 'mres');
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
        $key = ubRouting::filters($key, 'mres');
        $data = ubRouting::filters($data, 'mres');
        $this->deleteConf($key);
        $query = "INSERT INTO `sigreqconf` (`id`, `key`, `value`) VALUES (NULL, '" . $key . "', '" . $data . "'); ";
        nr_query($query);
    }

    /**
     * Saves a posted string config value, including empty strings that hide a field
     *
     * @param string $postName
     * @param string $confKey
     * @param bool $allowHtml
     *
     * @return void
     */
    protected function saveStringConf($postName, $confKey, $allowHtml = false) {
        if (ubRouting::checkPost($postName, false)) {
            $newValue = '';
            $filtered = '';
            if ($allowHtml) {
                $filtered = ubRouting::post($postName, 'safe', 'HTML');
            } else {
                $filtered = ubRouting::post($postName, 'safe');
            }
            if (is_string($filtered)) {
                $newValue = $filtered;
            }
            if ($this->diffConf($confKey, $newValue)) {
                $this->setConf($confKey, $newValue);
                log_register('SIGREQCONF CHANGED ' . $confKey);
            }
        }
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
        $notesDispFlag = true;
        if ($this->checkConf('NOTES_HIDDEN')) {
            $notesDispFlag = false;
        }
        $spamDispFlag = $this->checkConf('SPAM_TRAPS');
        $cachingFlag = $this->checkConf('CACHING');

        $inputs.= wf_CheckInput('newcitydisplay', __('Display city input'), true, $cityDispFlag);
        $inputs.= wf_CheckInput('newcityselectable', __('Show city input as combobox'), true, $citySelFlag);
        $inputs.= wf_CheckInput('newstreetselectable', __('Show street input as combobox'), true, $streetSelFlag);
        $inputs.= wf_CheckInput('newemaildisplay', __('Display email field'), true, $emailDispFlag);
        $inputs.= wf_CheckInput('newnotesdisplay', __('Display notes field'), true, $notesDispFlag);
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
        if (ubRouting::checkPost('newcitydisplay')) {
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
        if (ubRouting::checkPost('newcityselectable')) {
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
        if (ubRouting::checkPost('newstreetselectable')) {
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
        if (ubRouting::checkPost('newemaildisplay')) {
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
        //notes input (missing key means shown, NOTES_HIDDEN hides it)
        if (ubRouting::checkPost('newnotesdisplay')) {
            if ($this->checkConf('NOTES_HIDDEN')) {
                $this->deleteConf('NOTES_HIDDEN');
                log_register('SIGREQCONF ENABLED NOTES_DISPLAY');
            }
        } else {
            if (!$this->checkConf('NOTES_HIDDEN')) {
                $this->setConf('NOTES_HIDDEN', 'NOP');
                log_register('SIGREQCONF DISABLED NOTES_DISPLAY');
            }
        }
        //spamtraps
        if (ubRouting::checkPost('newespamtraps')) {
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
        if (ubRouting::checkPost('newcaching')) {
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
        $this->saveStringConf('newispname', 'ISP_NAME');
        $this->saveStringConf('newispurl', 'ISP_URL');
        $this->saveStringConf('newisplogo', 'ISP_LOGO');
        $this->saveStringConf('newsidebartext', 'SIDEBAR_TEXT', true);
        $this->saveStringConf('newgreetingtext', 'GREETING_TEXT', true);
        $this->saveStringConf('newservices', 'SERVICES');
        $this->saveStringConf('newtariffs', 'TARIFFS');
        $this->saveStringConf('newhideouts', 'HIDEOUTS');
    }

}

