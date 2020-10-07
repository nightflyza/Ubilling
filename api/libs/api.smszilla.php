<?php

class SMSZilla {

    /**
     * System alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all of available tariffs data as tariffname=>data
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available internet tariffs prices
     *
     * @var array
     */
    protected $allTariffPrices = array();

    /**
     * Contains available cities as cityid=>data
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains available swithes as swithid=>data
     *
     * @var array
     */
    protected $allSwitches = array();

    /**
     * Contains all users swithes as login=>switchid
     *
     * @var array
     */
    protected $allSwitchesUsers = array();

    /**
     * Contains data of all available Internet users as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains available tag types as id=>name
     *
     * @var array
     */
    protected $allTagTypes = array();

    /**
     * Contains available templates as id=>data
     *
     * @var array
     */
    protected $templates = array();

    /**
     * Contains available filters
     *
     * @var array
     */
    protected $filters = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Available filter types
     *
     * @var array
     */
    protected $filterTypes = array();

    /**
     * Contains available filters names
     *
     * @var array
     */
    protected $filterNames = array();

    /**
     * Contains available direction names
     *
     * @var array
     */
    protected $directionNames = array();

    /**
     * Current run entities type
     *
     * @var strings
     */
    protected $entitiesType = '';

    /**
     * Contains entities that passed full filter set
     *
     * @var array
     */
    protected $filteredEntities = array();

    /**
     * Contains filtered numbers extracted from filtered entities
     *
     * @var array
     */
    protected $filteredNumbers = array();

    /**
     * FundsFlow object placeholder
     *
     * @var object
     */
    protected $fundsFlow = '';

    /**
     * Contains tags for internet users
     *
     * @var array
     */
    protected $inetTags = array();

    /**
     * Contains tags for UKV users
     *
     * @var array
     */
    protected $ukvTags = array();

    /**
     * Contains all users and their down state as login=>state
     *
     * @var array
     */
    protected $downUsers = array();

    /**
     * Caching flag
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * SMS abstraction layer placeholder
     *
     * @var object
     */
    protected $sms = '';

    /**
     * Branches object placeholder
     *
     * @var object
     */
    protected $branches = '';

    /**
     * Districts object placeholder
     *
     * @var object
     */
    protected $districts = '';

    /**
     * UKV object placeholder
     *
     * @var object
     */
    protected $ukv = '';

    /**
     * UKV debtors once loading flag
     *
     * @var bool
     */
    protected $ukvDebtorsLoaded = false;

    /**
     * Available UKV debtors
     *
     * @var string
     */
    protected $ukvDebtors = array();

    /**
     * Contains available employee list
     *
     * @var array
     */
    protected $employee = array();

    /**
     * Extended mobiles phonebase usage flag
     *
     * @var bool
     */
    protected $useExtMobiles = false;

    /**
     * Extended mobiles object placeholder
     *
     * @var object
     */
    protected $extMobiles = '';

    /**
     * Contains available internet users paymentIDs
     *
     * @var array
     */
    protected $opCustomers = array();

    /**
     * Contains count of extended mobiles if they are extracted
     *
     * @var int
     */
    protected $extMobilesCount = 0;

    /**
     * Contains filters workflow stats as name=>count
     *
     * @var array
     */
    protected $filterStats = array();

    /**
     * Contains maximum chars limit for one SMS
     *      
     * @var int
     */
    protected $smsLenLimit = 160;

    /**
     * Contains country code for target country
     *
     * @var string
     */
    protected $countryCode = '380';

    /**
     * Contains full number length for some country without +
     *
     * @var int
     */
    protected $mobileLen = 12;

    /**
     * Phone normalizer debugging flag
     *
     * @var bool
     */
    protected $normalizerDebug = false;

    /**
     * Contains all numbers lists names as id=>name
     *
     * @var array
     */
    protected $allNumListsNames = array();

    /**
     * Contains all numbers lists numbers records ad id=>numlistdata
     *
     * @var array
     */
    protected $allNumListsNumbers = array();

    /**
     * Contains excludes numbers as mobile=>id
     *
     * @var array
     */
    protected $excludeNumbers = array();

    /**
     * Contains supported macro list for short help
     *
     * @var array
     */
    protected $supportedMacro = array();

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Base module URL
     */
    const URL_ME = '?module=smszilla';

    /**
     * Default macro help wiki URL
     */
    const URL_MACROHELP = 'http://wiki.ubilling.net.ua/doku.php?id=templating&#smszilla';

    /**
     * Contains SMS Pool saving path
     */
    const POOL_PATH = './exports/';

    /**
     * Contains temp files upload path
     */
    const UPLOAD_PATH = './exports/';

    /**
     * Creates new SMSZilla instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->initCache();
        $this->initSMS();
        $this->loadCities();
        $this->loadSwitches();
        $this->loadUsers();
        $this->loadOpCustomers();
        $this->loadDownUsers();
        $this->initUKV();
        $this->initBranches();
        $this->initDistricts();
        $this->loadTagTypes();
        $this->loadTariffs();
        $this->loadTemplates();
        $this->loadFilters();
        $this->loadNumLists();
        $this->loadExcludedNumbers();
        $this->loadInetTags();
        $this->loadUkvTags();
        $this->loadEmployee();
    }

    /**
     * Loads system alter config into private property for further usage
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
     * Loads all existing Internet users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        if ($this->useCache) {
            $this->allUserData = zb_UserGetAllDataCache();
        } else {
            $this->allUserData = zb_UserGetAllData();
        }
    }

    /**
     * Sets up OpenPayz paymentIDs array for further usage
     * 
     * @return void
     */
    protected function loadOpCustomers() {
        if ($this->useCache) {
            $this->opCustomers = $this->cache->get('OP_CUSTOMERS', 86400);
            if (empty($this->opCustomers)) {
                $this->opCustomers = $this->getOpenPayzCustomers();
                $this->cache->set('OP_CUSTOMERS', $this->opCustomers, 86400);
            }
        } else {
            $this->opCustomers = $this->getOpenPayzCustomers();
        }
    }

    /**
     * Returns list of OpenPayz customers as login=>paymentid
     * 
     * @return array
     */
    protected function getOpenPayzCustomers() {
        $result = array();
        if ($this->altCfg['OPENPAYZ_REALID']) {
            $query = "SELECT `realid`,`virtualid` from `op_customers`";
            $allcustomers = simple_queryall($query);
            if (!empty($allcustomers)) {
                foreach ($allcustomers as $io => $eachcustomer) {
                    $result[$eachcustomer['realid']] = $eachcustomer['virtualid'];
                }
            }
        } else {
            if (!empty($this->allUserData)) {
                foreach ($this->allUserData as $io => $each) {
                    $result[$each['login']] = ip2int($each['ip']);
                }
            }
        }
        return ($result);
    }

    /**
     * Loads available users down states into separate property
     * 
     * @return void
     */
    protected function loadDownUsers() {
        $query = "SELECT `login`,`Down` from `users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->downUsers[$each['login']] = $each['Down'];
            }
        }
    }

    /**
     * Inits UKV object instance
     * 
     * @return void
     */
    protected function initUKV() {
        $this->ukv = new UkvSystem();
    }

    /**
     * Loads existing tariffs from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['name']] = $each;
                $this->allTariffPrices[$each['name']] = $each['Fee'];
            }
        }
    }

    /**
     * Loads existing cities from database
     * 
     * @return void
     */
    protected function loadCities() {
        $query = "SELECT * from `city` ORDER BY `city`.`cityname` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCities[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing switches from database
     * 
     * @return void
     */
    protected function loadSwitches() {
        $query = "SELECT * from `switches` ORDER BY `location`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSwitches[$each['id']] = $each;
            }
        }
        $queryUsers = "SELECT * from `switchportassign`";
        $allUsers = simple_queryall($queryUsers);
        if (!empty($allUsers)) {
            foreach ($allUsers as $io => $each) {
                $this->allSwitchesUsers[$each['login']] = $each['switchid'];
            }
        }
    }

    /**
     * Loads existing tag types from database
     * 
     * @return void
     */
    protected function loadTagTypes() {
        $query = "SELECT * from `tagtypes`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTagTypes[$each['id']] = $each['tagname'];
            }
        }
    }

    /**
     * Loads all existing SMS templates from database
     * 
     * @return void
     */
    protected function loadTemplates() {
        $query = "SELECT * from `smz_templates` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->templates[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads array of all tagtypes set to users
     * 
     * @return void
     */
    protected function loadInetTags() {
        $query = "SELECT * from `tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->inetTags[$each['login']][] = $each['tagid'];
            }
        }
    }

    /**
     * Loads array of all tagtypes set to users
     * 
     * @return void
     */
    protected function loadUkvTags() {
        $query = "SELECT * from `ukv_tags`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->ukvTags[$each['userid']][] = $each['tagtypeid'];
            }
        }
    }

    /**
     * Loads available employee from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->employee[$each['id']] = $each;
            }
        }
    }

    /**
     * Inits system messages helper into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits system caching object into protected prop
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits SMS queue abstraction layer
     * 
     * @return void
     */
    protected function initSMS() {
        $this->sms = new UbillingSMS();
    }

    /**
     * Branches initalization
     * 
     * @return void
     */
    protected function initBranches() {
        $this->branches = new UbillingBranches();
    }

    /**
     * Creates new districts instance
     * 
     * @return void
     */
    protected function initDistricts() {
        $this->districts = new Districts(false);
    }

    /**
     * Inits funds flow object instance
     * 
     * @return void
     */
    protected function initFundsFlow() {
        $this->fundsFlow = new FundsFlow();
        $this->fundsFlow->runDataLoders();
    }

    /**
     * Sets all necessary options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->filterTypes = array(
            self::URL_ME . '&filters=true&newfilterdirection=none' => __('No'),
            self::URL_ME . '&filters=true&newfilterdirection=login' => __('Internet'),
            self::URL_ME . '&filters=true&newfilterdirection=ukv' => __('UKV'),
            self::URL_ME . '&filters=true&newfilterdirection=employee' => __('Employee'),
            self::URL_ME . '&filters=true&newfilterdirection=numlist' => __('Numbers list')
        );

        $this->filterNames = array(
            'atstart' => 'At begining',
            'filterdirection' => 'SMS direction',
            'filtername' => 'Filter name',
            'filteraddress' => 'Address contains',
            'filterao' => 'User is AlwaysOnline',
            'filterbranch' => 'Branch',
            'filtercashdays' => 'Balance is enought less than days',
            'filtercashgreater' => 'Balance is greater than',
            'filtercashlesser' => 'Balance is less than',
            'filtercashlesszero' => 'Balance is less than zero',
            'filtercashzero' => 'Balance is zero',
            'filtercreditset' => 'User have credit',
            'filtercashmonth' => 'Balance is not enough for the next month',
            'filtercity' => 'City',
            'filterdown' => 'User is down',
            'filteremployeeactive' => 'Employee is active',
            'filteremployeeappointment' => 'Appointment',
            'filterextmobiles' => 'Use additional mobiles',
            'filterlogin' => 'Login contains',
            'filterip' => 'IP contains',
            'filternotariff' => 'User have no tariff assigned',
            'filterpassive' => 'User is frozen',
            'filternotpassive' => 'User is not frozen',
            'filteractive' => 'User is active',
            'filtertags' => 'User have tag assigned',
            'filtertariff' => 'User have tariff',
            'filtertariffcontain' => 'User tariff contains',
            'filterukvactive' => 'User is active',
            'filterukvdebtor' => 'Debtors',
            'filterukvtariff' => 'User have tariff',
            'filterrealname' => 'Real Name contains',
            'filternumlist' => 'Numbers list',
            'filternumcontain' => 'Notes contains',
            'filternumnotcontain' => 'Notes not contains',
            'filternumnotouruser' => 'Is not our user',
            'filterdistrict' => 'District',
            'filterswitch' => 'Switch',
        );

        $this->directionNames = array(
            'login' => 'Internet',
            'ukv' => 'UKV',
            'employee' => 'Employee',
            'numlist' => 'Numbers list'
        );

        $this->supportedMacro = array(
            '{LOGIN}' => __('Login'),
            '{REALNAME}' => __('Real Name'),
            '{TARIFF}' => __('Tariff'),
            '{TARIFFPRICE}' => __('Tariff fee'),
            '{PAYMENTID}' => __('Payment ID'),
            '{CREDIT}' => __('Credit'),
            '{CASH}' => __('Balance'),
            '{LACK}' => __('Balance lack'),
            '{ROUNDCASH}' => __('Cash rounded to cents'),
            '{IP}' => __('IP'),
            '{MAC}' => __('MAC address'),
            '{FULLADDRESS}' => __('Full address'),
            '{PHONE}' => __('Phone') . ' ' . __('number'),
            '{MOBILE}' => __('Mobile') . ' ' . __('number'),
            '{CONTRACT}' => __('User contract'),
            '{EMAIL}' => __('Email'),
            '{CURDATE}' => __('Current date'),
            '{PASSWORD}' => __('Password')
        );

        if ((isset($this->altCfg['SMSZILLA_MOBILE_LEN'])) AND ( $this->altCfg['SMSZILLA_COUNTRY_CODE'])) {
            //custom countries number settings
            $this->countryCode = vf($this->altCfg['SMSZILLA_COUNTRY_CODE'], 3);
            $this->mobileLen = $this->altCfg['SMSZILLA_MOBILE_LEN'];
        }

        //cahing disabling
        if ((isset($this->altCfg['SMSZILLA_NOCACHE'])) AND ( $this->altCfg['SMSZILLA_NOCACHE'])) {
            $this->useCache = false;
        }
    }

    /**
     * Creates new SMS text template
     * 
     * @param string $name
     * @param string $text
     * 
     * @return int
     */
    public function createTemplate($name, $text) {
        $name = mysql_real_escape_string($name);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `smz_templates` (`id`,`name`,`text`) VALUES ";
        $query .= "(NULL,'" . $name . "','" . $text . "');";
        nr_query($query);
        $newId = simple_get_lastid('smz_templates');
        log_register('SMSZILLA TEMPLATE CREATE [' . $newId . ']');
        return ($newId);
    }

    /**
     * Deletes existing template
     * 
     * @param int $templateId
     * 
     * @return void/string on error
     */
    public function deleteTemplate($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $query = "DELETE from `smz_templates` WHERE `id`='" . $templateId . "';";
            nr_query($query);
            log_register('SMSZILLA TEMPLATE DELETE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Saves changes in existing template
     * 
     * @param int $templateId
     * @param string $name
     * @param string $text
     * 
     * @return void/string on error
     */
    public function saveTemplate($templateId, $name, $text) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $where = "WHERE `id`='" . $templateId . "'";
            simple_update_field('smz_templates', 'name', $name, $where);
            simple_update_field('smz_templates', 'text', $text, $where);
            log_register('SMSZILLA TEMPLATE CHANGE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders new template creation form
     * 
     * @return string
     */
    public function renderTemplateCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newtemplatename', __('Name'), '', true, '40');
        $inputs .= __('Template') . wf_tag('br');
        $inputs .= wf_TextArea('newtemplatetext', '', '', true, '45x5');
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME . '&templates=true', 'POST', $inputs, 'glamour');
        $cells = wf_TableCell($form, '50%', '', 'valign="top"');
        $cells .= wf_TableCell($this->renderMacroHelp(), '', '', 'valign="top"');
        $rows = wf_TableRow($cells);
        $result .= wf_TableBody($rows, '100%', 0);
        return ($result);
    }

    /**
     * Renders supported templating macro short help
     * 
     * @return string
     */
    protected function renderMacroHelp() {
        $result = '';
        if (!empty($this->supportedMacro)) {
            $result .= wf_tag('strong') . __('Available macroses') . ':' . wf_tag('strong', true) . wf_delimiter();
            foreach ($this->supportedMacro as $io => $each) {
                $result .= wf_tag('b') . $io . wf_tag('b', true) . ' - ' . $each . wf_tag('br');
            }
            $result .= wf_tag('br') . wf_Link(self::URL_MACROHELP, __('Details'), false, '', 'target="_BLANK"');
        }
        return ($result);
    }

    /**
     * Renders existing template edit form
     * 
     * @param int $templateId
     * 
     * @return string
     */
    public function renderTemplateEditForm($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $templateData = $this->templates[$templateId];
            $inputs = wf_HiddenInput('edittemplateid', $templateId);
            $inputs .= wf_TextInput('edittemplatename', __('Name'), $templateData['name'], true, '40');
            $inputs .= __('Template') . wf_tag('br');
            $inputs .= wf_TextArea('edittemplatetext', '', $templateData['text'], true, '45x5');
            $templateSize = mb_strlen($templateData['text'], 'utf-8');
            $inputs .= __('Text size') . ' ~' . $templateSize . wf_tag('br');
            $inputs .= wf_Submit(__('Save'));
            $form = wf_Form(self::URL_ME . '&templates=true&edittemplate=' . $templateId, 'POST', $inputs, 'glamour');

            $cells = wf_TableCell($form, '50%', '', 'valign="top"');
            $cells .= wf_TableCell($this->renderMacroHelp(), '', '', 'valign="top"');
            $rows = wf_TableRow($cells);
            $result .= wf_TableBody($rows, '100%', 0);
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Renders existing templates list with some controls
     * 
     * @return string
     */
    public function renderTemplatesList() {
        $result = '';
        if (!empty($this->templates)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Text'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->templates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['text']);
                $actLinks = wf_JSAlert(self::URL_ME . '&templates=true&deletetemplate=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_JSAlert(self::URL_ME . '&templates=true&edittemplate=' . $each['id'], web_edit_icon(), $this->messages->getEditAlert());

                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No existing templates available'), 'warning');
        }
        return ($result);
    }

    /**
     * Loads existing numberlists from database
     * 
     * @return void
     */
    protected function loadNumLists() {
        $query = "SELECT * from `smz_lists`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allNumListsNames[$each['id']] = $each['name'];
            }
        }

        $query = "SELECT * from `smz_nums`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allNumListsNumbers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing excluded numbers from database
     * 
     * @return void
     */
    protected function loadExcludedNumbers() {
        $query = "SELECT * from `smz_excl`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->excludeNumbers[$each['mobile']] = $each['id'];
            }
        }
    }

    /**
     * Creates new mobile exclude in database
     * 
     * @param string $mobileNum
     * 
     * @return void
     */
    public function createExclude($mobileNum) {
        $mobileNumF = mysql_real_escape_string($mobileNum);
        if (!empty($mobileNumF)) {
            if (!isset($this->excludeNumbers[$mobileNumF])) {
                $query = "INSERT INTO `smz_excl` (`id`,`mobile`) VALUES ";
                $query .= "(NULL,'" . $mobileNumF . "');";
                nr_query($query);
                $newId = simple_get_lastid('smz_excl');
                log_register('SMSZILLA EXCLUDE CREATE [' . $newId . '] `' . $mobileNum . '`');
            }
        }
    }

    /**
     * Deletes existing excluded number from database
     * 
     * @param int $excludeId
     * 
     * @return void
     */
    public function deleteExlude($excludeId) {
        $excludeId = vf($excludeId, 3);
        $query = "DELETE from `smz_excl` WHERE `id`='" . $excludeId . "';";
        nr_query($query);
        log_register('SMSZILLA EXCLUDE DELETE [' . $excludeId . ']');
    }

    /**
     * Renders numlist creation form
     * 
     * @return string
     */
    public function renderNumListCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newnumlistname', __('Name'), '', false, 20) . ' ';
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form(self::URL_ME . '&numlists=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders numeric list editing form
     * 
     * @param int $numlistId
     * 
     * @return string
     */
    public function renderNumListEditForm($numlistId) {
        $result = '';
        $numlistId = vf($numlistId, 3);
        if (isset($this->allNumListsNames[$numlistId])) {
            $inputs = wf_HiddenInput('editnumlistid', $numlistId);
            $inputs .= wf_TextInput('editnumlistname', __('Name'), $this->allNumListsNames[$numlistId], true, '20');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form(self::URL_ME . '&numlists=true', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Saves numlist name changes into database
     * 
     * @param int $numlistId
     * @param string $numlistName
     * 
     * @return void
     */
    public function saveNumList($numlistId, $numlistName) {
        $numlistId = vf($numlistId, 3);
        if (isset($this->allNumListsNames[$numlistId])) {
            simple_update_field('smz_lists', 'name', $numlistName, "WHERE `id`='" . $numlistId . "';");
            log_register('SMSZILLA NUMLIST CHANGE [' . $numlistId . '] `' . $numlistName . '`');
        }
    }

    /**
     * Renders numlist list with some controls
     * 
     * @return string
     */
    public function renderNumListsList() {
        $result = '';
        if (!empty($this->allNumListsNames)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allNumListsNames as $io => $each) {
                $cells = wf_TableCell($io);
                $cells .= wf_TableCell($each);
                $actLinks = wf_JSAlert(self::URL_ME . '&numlists=true&deletenumlistid=' . $io, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_JSAlert(self::URL_ME . '&numlists=true&editnumlistid=' . $io, web_edit_icon(), $this->messages->getEditAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        $result .= wf_tag('br');
        $result .= $this->renderNumListCreateForm();
        return ($result);
    }

    /**
     * Creates new numbers list in database
     * 
     * @param string $name
     * 
     * @return void/string on error
     */
    public function createNumList($name) {
        $result = '';
        $nameF = mysql_real_escape_string($name);
        if (!empty($nameF)) {
            $query = "INSERT INTO `smz_lists` (`id`,`name`) VALUES ";
            $query .= "(NULL,'" . $nameF . "');";
            nr_query($query);
            $newId = simple_get_lastid('smz_lists');
            log_register('SMSZILLA NUMLIST CREATE [' . $newId . '] `' . $name . '`');
        } else {
            $result = __('Oh no') . ': EX_EMPTY_NUMLIST_NAME';
        }
    }

    /**
     * Creates new numbers list in database
     * 
     * @param string $name
     * 
     * @return void/string on error
     */
    public function deleteNumList($numlistId) {
        $numlistId = vf($numlistId, 3);
        $result = '';
        if (isset($this->allNumListsNames[$numlistId])) {
            $query = "DELETE FROM `smz_lists` WHERE `id`='" . $numlistId . "';";
            nr_query($query);
            log_register('SMSZILLA NUMLIST DELETE [' . $numlistId . ']');
            $query = "DELETE FROM `smz_nums` WHERE `numid`='" . $numlistId . "';";
            nr_query($query);
            log_register('SMSZILLA NUMLIST FLUSH [' . $numlistId . ']');
        } else {
            $result = __('Oh no') . ': EX_NUMLISTID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders form for single number addition to number list
     * 
     * @return string
     */
    public function createNumListNumberForm() {
        $result = '';
        if (!empty($this->allNumListsNames)) {
            $inputs = wf_Selector('newsinglenumlistid', $this->allNumListsNames, __('Numbers list'), '', false);
            $inputs .= wf_TextInput('newsinglenumlistmobile', __('Mobile'), '', false, 15, 'mobile');
            $inputs .= wf_TextInput('newsinglenumlistnotes', __('Notes'), '', false, 30);
            $inputs .= wf_Submit(__('Add'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Renders upload form for some mobile data
     * 
     * @return string
     */
    public function uploadNumListNumbersForm() {
        $result = '';
        if (!empty($this->allNumListsNames)) {
            $result .= wf_tag('form', false, 'glamour', 'action="" enctype="multipart/form-data" method="POST"');
            $result .= wf_HiddenInput('uploadnumlistnumbers', 'true');
            $result .= wf_Selector('newnumslistid', $this->allNumListsNames, __('Numbers list'), '', false);
            $result .= wf_tag('input', false, '', 'id="fileselector" type="file" name="smznumlistcsv"');
            $result .= wf_Submit('Upload');
            $result .= wf_tag('form', true);
            $result .= wf_CleanDiv();
        } else {
            $result .= $this->messages->getStyledMessage(__('No existing numbers lists available'), 'warning');
        }
        return ($result);
    }

    /**
     * Creates new numlist phone record in database
     * 
     * @param int $numlistId
     * @param string $mobile
     * @param string $notes
     * 
     * @return void/string on error
     */
    public function createNumlistSingleNumber($numlistId, $mobile, $notes) {
        $result = '';
        $numlistId = vf($numlistId, 3);
        if (isset($this->allNumListsNames[$numlistId])) {
            $mobileF = mysql_real_escape_string($mobile);
            $notes = mysql_real_escape_string($notes);
            $query = "INSERT INTO `smz_nums` (`id`,`numid`,`mobile`,`notes`) VALUES ";
            $query .= "(NULL, '" . $numlistId . "','" . $mobileF . "','" . $notes . "');";
            nr_query($query);
            $newId = simple_get_lastid('smz_nums');
            log_register('SMSZILLA NUMLISTNUM CREATE [' . $numlistId . '] MOBILE  `' . $mobile . '`');
        } else {
            $result .= __('Oh no') . ': EX_NUMLISTID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Cleanups numlist from existing users phones
     * 
     * @param int $numlistId
     * 
     * @return void/string on error
     */
    public function cleanupNumlist($numlistId) {
        $result = '';
        $numlistId = vf($numlistId, 3);
        $cleanupUserMobiles = array(); // contains temp array for deletion as mobile=>login
        $extMobilesFlag = ($this->altCfg['MOBILES_EXT']) ? true : false;
        if (wf_CheckPost(array('cleanupnumlistid', 'cleanupagree'))) {
            if (!empty($this->allUserData)) {
                if ($extMobilesFlag) {
                    $this->extMobiles = new MobilesExt();
                }

                foreach ($this->allUserData as $io => $each) {
                    $userLogin = $each['login'];
                    $primaryMobile = $this->normalizePhoneFormat($each['mobile']);
                    if (!empty($primaryMobile)) {
                        $cleanupUserMobiles[$primaryMobile] = $userLogin;
                    }

                    if ($this->extMobiles) {
                        $userExtMobiles = $this->extMobiles->getUserMobiles($userLogin);
                        if (!empty($userExtMobiles)) {
                            foreach ($userExtMobiles as $ia => $eachExt) {
                                $additionalMobile = $this->normalizePhoneFormat($eachExt['mobile']);
                                if (!empty($additionalMobile)) {
                                    $cleanupUserMobiles[$additionalMobile] = $userLogin;
                                }
                            }
                        }
                    }
                }
            }

            if ((!empty($cleanupUserMobiles)) AND ( !empty($this->allNumListsNumbers))) {
                foreach ($this->allNumListsNumbers as $io => $each) {
                    if ($each['numid'] == $numlistId) {
                        $numlistNumber = $each['mobile'];
                        $numlistNumber = $this->normalizePhoneFormat($numlistNumber);
                        if (isset($cleanupUserMobiles[$numlistNumber])) {
                            $this->deleteNumlistNumber($each['id']);
                        }
                    }
                }
            }
        } else {
            $result .= __('You are not mentally prepared for this');
        }
        return ($result);
    }

    /**
     * Renders numlist cleanup form 
     * 
     * @return string
     */
    public function renderCleanupNumlistForm() {
        $result = '';
        if (!empty($this->allNumListsNames)) {
            $inputs = wf_Selector('cleanupnumlistid', $this->allNumListsNames, __('Numbers list'), '', false) . ' ';
            $inputs .= wf_CheckInput('cleanupagree', __('I`m ready'), false, false) . ' ';
            $inputs .= wf_Submit(__('Cleanup'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

    /**
     * Catches file upload
     * 
     * @return string
     */
    public function catchFileUpload() {
        $result = '';
        $numListId = $_POST['newnumslistid'];
        $allowedExtensions = array("csv", "txt");
        $fileAccepted = true;
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $fileAccepted = false;
                }
            }
        }

        if ($fileAccepted) {
            $newFilename = zb_rand_string(6) . '_smz_nums.dat';
            $newSavePath = self::UPLOAD_PATH . $newFilename;
            move_uploaded_file($_FILES['smznumlistcsv']['tmp_name'], $newSavePath);
            if (file_exists($newSavePath)) {
                $uploadResult = $this->messages->getStyledMessage(__('Upload complete'), 'success');
                $result = $newFilename;
            } else {
                $uploadResult = $this->messages->getStyledMessage(__('Upload failed'), 'error');
            }
        } else {
            $uploadResult = $this->messages->getStyledMessage(__('Upload failed') . ': EX_WRONG_EXTENSION', 'error');
        }

        show_window('', $uploadResult);
        if ($result) {
            $this->preprocessNumList($result, $numListId);
        }
        return ($result);
    }

    /**
     * Opens and inserts into database some numbers list data
     * 
     * @param string $fileName
     * @param int $numlistId
     * 
     * @return string
     */
    protected function preprocessNumList($fileName, $numlistId) {
        $result = '';
        $numlistId = vf($numlistId, 3);
        $count = 0;
        if (file_exists(self::UPLOAD_PATH . $fileName)) {
            $fileRawData = file_get_contents(self::UPLOAD_PATH . $fileName);
            if (!empty($fileRawData)) {
                $fileRawData = explodeRows($fileRawData);
                if (!empty($fileRawData)) {
                    foreach ($fileRawData as $io => $line) {
                        if (!empty($line)) {
                            $lineExploded = explode(';', $line);
                            $newNumber = $this->normalizePhoneFormat($lineExploded[0]);
                            $newNumber = mysql_real_escape_string($newNumber);
                            $newNotes = '';
                            unset($lineExploded[0]);
                            foreach ($lineExploded as $ia => $nts) {
                                $newNotes .= $nts . ' ';
                            }
                            $newNotes = trim($newNotes);
                            $newNotes = mysql_real_escape_string($newNotes);
                            $query = "INSERT INTO `smz_nums` (`id`,`numid`,`mobile`,`notes`) VALUES ";
                            $query .= "(NULL,'" . $numlistId . "','" . $newNumber . "','" . $newNotes . "');";
                            nr_query($query);
                            $count++;
                        }
                    }
                    log_register('SMSZILLA NUMLISTNUM UPLOAD [' . $numlistId . '] COUNT `' . $count . '`');
                }
            }
        }
        return ($result);
    }

    /**
     * Renders numbers list mobiles container
     * 
     * @return string
     */
    public function renderNumsContainer() {
        $result = '';
        $columns = array('ID', 'Numbers list', 'Mobile', 'Notes', 'Actions');
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&numlists=true&ajnums=true', false, __('Mobile'), 100);
        return ($result);
    }

    /**
     * Renders numbers list ajax data tables reply
     * 
     * @return void
     */
    public function ajaxNumbersReply() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allNumListsNumbers)) {
            foreach ($this->allNumListsNumbers as $io => $each) {
                $data[] = $each['id'];
                $data[] = @$this->allNumListsNames[$each['numid']];
                $data[] = $each['mobile'];
                $data[] = $each['notes'];
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&numlists=true&deletenumid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Delete some single numlist mobile number from database
     * 
     * @param int $numId
     * 
     * @return void
     */
    public function deleteNumlistNumber($numId) {
        $numId = vf($numId, 3);
        if (isset($this->allNumListsNumbers[$numId])) {
            $query = "DELETE from `smz_nums` WHERE `id`='" . $numId . "';";
            nr_query($query);
            log_register('SMSZILLA NUMLISTNUM DELETE [' . $numId . ']');
        }
    }

    /**
     * Renders existing excludes list with some controls
     * 
     * @return string
     */
    public function renderExcludeNumsList() {
        $result = '';
        if (!empty($this->excludeNumbers)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Mobile'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->excludeNumbers as $io => $each) {
                $cells = wf_TableCell($each);
                $cells .= wf_TableCell($io);
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&excludes=true&deleteexclnumid=' . $each, web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders exclude number creation form
     * 
     * @return string
     */
    public function renderExcludeCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newexcludenumber', __('Mobile'), '', false, '20', 'mobile');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form(self::URL_ME . '&excludes=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders default module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&sending=true', wf_img('skins/icon_sms_micro.gif') . ' ' . __('SMS sending'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&templates=true', wf_img('skins/icon_template.png') . ' ' . __('Templates'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&filters=true', web_icon_extended() . ' ' . __('Filters'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&numlists=true', wf_img('skins/icon_mobile.gif') . ' ' . __('Numbers lists'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&excludes=true', wf_img('skins/icon_deleterow.png') . ' ' . __('Excludes'), !$this->sms->smsRoutingFlag, 'ubButton') . ' ';

        if ($this->sms->smsRoutingFlag) {
            $cacheLnkId = wf_InputId();
            $addServiceJS = wf_JSAjaxModalOpener(self::URL_ME, array('action' => 'RefreshBindingsCache'), $cacheLnkId, true);
            $result .= wf_Link('#', wf_img('skins/refresh.gif') . ' ' . __('Refresh SMS services bindings cache'), true, 'ubButton', 'id="' . $cacheLnkId . '"') . $addServiceJS;
        }

        if (wf_CheckGet(array('action')) and $_GET['action'] == 'RefreshBindingsCache') {
            $this->sms->smsDirections->refreshCacheForced();
            $messageWindow = $this->messages->getStyledMessage(__('SMS services cache bindings updated succesfuly'), 'success', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
            die(wf_modalAutoForm('', $messageWindow, $_GET['modalWindowId'], '', true));
        }

        if (wf_CheckGet(array('templates'))) {
            $result .= wf_tag('br');
            if (wf_CheckGet(array('edittemplate'))) {
                $result .= wf_BackLink(self::URL_ME . '&templates=true') . ' ';
            } else {
                $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new template'), __('Create new template'), $this->renderTemplateCreateForm(), 'ubButton');
            }
        }
        return ($result);
    }

    /**
     * Renders filter creation form
     * 
     * @return string
     */
    public function renderFilterCreateForm() {
        $result = '';
        $result .= wf_AjaxLoader();
        $inputs = wf_AjaxContainer('inputscontainer', '', $this->catchAjRequest(true));
        $result .= wf_Form(self::URL_ME . '&filters=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns ajax inputs of required type
     * 
     * @return string
     */
    public function catchAjRequest($remote = false) {
        $inputs = '';
        $direction = 'none';
        if (wf_CheckGet(array('newfilterdirection'))) {
            $direction = vf($_GET['newfilterdirection']);
        }

        $citiesParams = array('' => __('Any'));
        if (!empty($this->allCities)) {
            foreach ($this->allCities as $io => $each) {
                $citiesParams[$each['cityname']] = $each['cityname'];
            }
        }

        $tagsParams = array('' => __('-'));
        if (!empty($this->allTagTypes)) {
            foreach ($this->allTagTypes as $io => $each) {
                $tagsParams[$io] = $each;
            }
        }

        $tariffParams = array('' => __('Any'));
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $tariffParams[$each['name']] = $each['name'];
            }
        }

        $ukvTariffParams = array('' => __('Any'));
        $ukvfTariffsAvail = $this->ukv->getTariffs();
        if (!empty($ukvfTariffsAvail)) {
            foreach ($ukvfTariffsAvail as $io => $each) {
                $ukvTariffParams[$each['id']] = $each['tariffname'];
            }
        }


        $numListParams = $this->allNumListsNames;

        $branchParams = array('' => __('Any'));
        $availBranches = $this->branches->getBranchesAvailable();
        if (!empty($availBranches)) {
            foreach ($availBranches as $io => $each) {
                $branchParams[$io] = $each;
            }
        }

        $switchesParams = array('' => __('Any'));
        if (!empty($this->allSwitches)) {
            foreach ($this->allSwitches as $io => $each) {
                $switchesParams[$each['id']] = $each['ip'] . ' - ' . $each['location'];
            }
        }

        $districtsParams = array('' => __('Any'));
        $districtsParams += $this->districts->getDistricts();


        $inputs .= wf_AjaxSelectorAC('inputscontainer', $this->filterTypes, __('SMS direction'), self::URL_ME . '&filters=true&newfilterdirection=' . $direction, true);
        $inputs .= wf_tag('br');

        if ($direction != 'none') {
            $inputs .= wf_HiddenInput('newfilterdirection', $direction);
            $inputs .= wf_TextInput('newfiltername', __('Filter name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '30');

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs .= wf_Selector('newfiltercity', $citiesParams, __('City'), '', true, false);
                $inputs .= wf_TextInput('newfilteraddress', __('Address contains'), '', true, '40');
            }

            if (($direction == 'login') OR ( $direction == 'ukv') OR ( $direction == 'employee')) {
                $inputs .= wf_TextInput('newfilterrealname', __('Real Name') . ' ' . __('contains'), '', true, '30');
            }

            if (($direction == 'login')) {
                $inputs .= wf_TextInput('newfilterlogin', __('Login contains'), '', true, '20');
                $inputs .= wf_TextInput('newfilterip', __('IP contains'), '', true, '20');
                $inputs .= wf_Selector('newfilterswitch', $switchesParams, __('Switch'), '', true, false);
                $inputs .= wf_CheckInput('newfiltercashmonth', __('Balance is not enough for the next month'), true, false);
                $inputs .= wf_TextInput('newfiltercashdays', __('Balance is enought less than days'), '', true, '5');
            }

            if ($direction == 'ukv') {
                $inputs .= wf_Selector('newfilterukvtariff', $ukvTariffParams, __('User have tariff'), '', true, false);
                $inputs .= wf_CheckInput('newfilterukvdebtor', __('Debtors'), true, false);
                $inputs .= wf_CheckInput('newfilterukvactive', __('User is active'), true, false);
            }

            if (($direction == 'login') OR ( $direction == 'ukv')) {
                $inputs .= wf_TextInput('newfiltercashgreater', __('Balance is greater than'), '', true, '5');
                $inputs .= wf_TextInput('newfiltercashlesser', __('Balance is less than'), '', true, '5');
                $inputs .= wf_CheckInput('newfiltercashlesszero', __('Balance is less than zero'), true, false);
                $inputs .= wf_CheckInput('newfiltercashzero', __('Balance is zero'), true, false);
                $inputs .= wf_Selector('newfiltertags', $tagsParams, __('User have tag assigned'), '', true, false);
            }


            if (($direction == 'login')) {
                $inputs .= wf_CheckInput('newfiltercreditset', __('User have credit'), true, false);
                $inputs .= wf_CheckInput('newfilterpassive', __('User is frozen'), true, false);
                $inputs .= wf_CheckInput('newfilternotpassive', __('User is not frozen'), true, false);
                $inputs .= wf_CheckInput('newfilteractive', __('User is active'), true, false);
                $inputs .= wf_CheckInput('newfilterdown', __('User is down'), true, false);
                $inputs .= wf_CheckInput('newfilterao', __('User is AlwaysOnline'), true, true);
                $inputs .= wf_Selector('newfiltertariff', $tariffParams, __('User have tariff'), '', true, false);
                $inputs .= wf_TextInput('newfiltertariffcontain', __('User tariff contains'), '', true, '15');
                $inputs .= wf_CheckInput('newfilternotariff', __('User have no tariff assigned'), true, false);
                $inputs .= wf_CheckInput('newfilterextmobiles', __('Use additional mobiles'), true, false);
                $inputs .= wf_Selector('newfilterbranch', $branchParams, __('Branch'), '', true, false);
                $inputs .= wf_Selector('newfilterdistrict', $districtsParams, __('District'), '', true, false);
            }

            if (($direction == 'numlist')) {
                $inputs .= wf_Selector('newfilternumlist', $numListParams, __('Numbers list'), '', true, false);
                $inputs .= wf_TextInput('newfilternumcontain', __('Notes contains'), '', true, '20');
                $inputs .= wf_TextInput('newfilternumnotcontain', __('Notes not contains'), '', true, '20');
                $inputs .= wf_CheckInput('newfilternumnotouruser', __('Is not our user'), true, false);
            }

            if ($direction == 'employee') {
                $inputs .= wf_TextInput('newfilteremployeeappointment', __('Appointment'), '', true, '30');
                $inputs .= wf_CheckInput('newfilteremployeeactive', __('Employee is active'), true, true);
            }

            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Create'));
        } else {
            $inputs .= __('Please select SMS direction');
        }

        if (!$remote) {
            die($inputs);
        } else {
            return ($inputs);
        }
    }

    /**
     * Creates new filter in database
     * 
     * @return void/string
     */
    public function createFilter() {
        $result = '';
        if (wf_CheckPost(array('newfilterdirection'))) {
            $filterName = $_POST['newfiltername'];
            $filterNameF = mysql_real_escape_string($filterName);
            if (!empty($filterNameF)) {
                $filterParams = array();
                foreach ($_POST as $io => $each) {
                    if (ispos($io, 'newfilter')) {
                        $filterParams[$io] = $each;
                    }
                }
                if (!empty($filterParams)) {
                    $filterParams = json_encode($filterParams);
                    $filterParams = mysql_real_escape_string($filterParams);
                    $query = "INSERT INTO `smz_filters` (`id`,`name`,`filters`) VALUES ";
                    $query .= "(NULL,'" . $filterNameF . "','" . $filterParams . "');";
                    nr_query($query);
                    $newId = simple_get_lastid('smz_filters');
                    log_register('SMSZILLA FILTER CREATE [' . $newId . '] `' . $filterName . '`');
                }
            } else {
                $result = __('Something went wrong') . ': EX_FILTER_NAME_EMPTY';
            }
        }
        return ($result);
    }

    /**
     * Loads existing filters from database
     * 
     * @return void
     */
    protected function loadFilters() {
        $query = "SELECT * from `smz_filters` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->filters[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders existing filter preview
     * 
     * @param string $filters
     * 
     * @return string
     */
    protected function renderFilterPreview($filters) {
        $result = '';
        if (!empty($filters)) {
            $unpack = json_decode($filters, true);
            if (!empty($unpack)) {
                $cells = wf_TableCell(__('Filter'));
                $cells .= wf_TableCell(__('Parameter'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($unpack as $io => $each) {
                    $filterName = str_replace('new', '', $io);
                    if (isset($this->filterNames[$filterName])) {
                        $filterName = __($this->filterNames[$filterName]);
                    }
                    $cells = wf_TableCell($filterName);
                    $cells .= wf_TableCell($each);
                    $rows .= wf_TableRow($cells, 'row3');
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
            }
        }
        return ($result);
    }

    /**
     * Renders available filters list
     * 
     * @return string
     */
    public function renderFiltersList() {
        $result = '';
        if (!empty($this->filters)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->filters as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $actLinks = wf_JSAlert(self::URL_ME . '&filters=true&deletefilterid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_icon_search(), __('Preview'), $this->renderFilterPreview($each['filters']));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Deletes existing filter from database
     * 
     * @param int $filterId
     * 
     * @return void/string
     */
    public function deleteFilter($filterId) {
        $result = '';
        $filterId = vf($filterId, 3);
        if (isset($this->filters[$filterId])) {
            $query = "DELETE FROM `smz_filters` WHERE `id`='" . $filterId . "';";
            nr_query($query);
            log_register('SMSZILLA FILTER DELETE [' . $filterId . ']');
        } else {
            $result = __('Something went wrong') . ': : FILTER_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Checks have user some tag assigned
     * 
     * @param string $login
     * @param int $tagId
     * 
     * @return bool
     */
    protected function checkInetTagId($login, $tagId) {
        $result = false;
        if (isset($this->inetTags[$login])) {
            if (!empty($this->inetTags[$login])) {
                foreach ($this->inetTags[$login] as $io => $each) {
                    if ($each == $tagId) {
                        $result = true;
                        return ($result);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Checks have user switchId
     * 
     * @param string $login
     * @param int $switchId
     * 
     * @return bool
     */
    protected function checkSwitchId($login, $switchId) {
        $result = false;
        if (isset($this->allSwitchesUsers[$login])) {
            if ($this->allSwitchesUsers[$login] == $switchId) {
                $result = true;
                return ($result);
            }
        }
        return ($result);
    }

    /**
     * Checks have UKV user some tag assigned
     * 
     * @param int $userid
     * @param int $tagId
     * 
     * @return bool
     */
    protected function checkUkvTagId($userid, $tagId) {
        $result = false;
        if (isset($this->ukvTags[$userid])) {
            if (!empty($this->ukvTags[$userid])) {
                foreach ($this->ukvTags[$userid] as $io => $each) {
                    if ($each == $tagId) {
                        $result = true;
                        return ($result);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders template and filters selection form
     * 
     * @return string
     */
    public function renderSendingForm() {
        $result = '';

//saving previous selectors state
        $curTemplateId = (wf_CheckPost(array('sendingtemplateid'))) ? $_POST['sendingtemplateid'] : '';
        $curFilterId = (wf_CheckPost(array('sendingfilterid'))) ? $_POST['sendingfilterid'] : '';
        $curVisualFlag = (wf_CheckPost(array('sendingvisualfilters'))) ? true : false;
        $curTranslitFlag = (wf_CheckPost(array('forcetranslit'))) ? true : false;

        if (!(empty($this->templates)) AND ( !empty($this->filters))) {
            $templatesParams = array();
            foreach ($this->templates as $io => $each) {
                $templatesParams[$each['id']] = $each['name'];
            }

            $filterParams = array();
            foreach ($this->filters as $io => $each) {
                $filterUnpack = json_decode($each['filters'], true);
                $filterDirection = $filterUnpack['newfilterdirection'];
                $filterDirection = $this->directionNames[$filterDirection];
                $filterParams[$each['id']] = __($filterDirection) . ' ⇒ ' . $each['name'];
            }


            $inputs = wf_Selector('sendingtemplateid', $templatesParams, __('Template'), $curTemplateId, false) . ' ';
            $inputs .= wf_Selector('sendingfilterid', $filterParams, __('Filter'), $curFilterId, false) . ' ';
            $inputs .= wf_CheckInput('sendingvisualfilters', __('Visual'), false, $curVisualFlag) . ' ';
            $inputs .= wf_CheckInput('forcetranslit', __('Forced transliteration'), false, $curTranslitFlag) . ' ';
            $inputs .= wf_CheckInput('sendingperform', __('Perform real sending'), false, false) . ' ';

            $inputs .= wf_Submit(__('Send SMS'));

            $result .= wf_Form(self::URL_ME . '&sending=true', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('No existing templates or filters available'), 'warning');
            $result .= wf_CleanDiv();
            $result .= wf_delimiter();
            $result .= wf_tag('center') . wf_img('skins/gojiracry.jpg') . wf_tag('center', true);
        }
        return ($result);
    }

    /**
     * Normalizes mobile number to E164 phone format.
     * 
     * @param string $mobile
     * 
     * @return string/void on error
     */
    protected function normalizePhoneFormat($mobile) {
        $mobile = vf($mobile, 3);
        if (!empty($mobile)) {
            $inputLen = strlen($mobile);
            $codeLen = strlen($this->countryCode);

            if ($inputLen < $this->mobileLen) {
//trying to append country code if number is not ok by default or too short
                $mobileTmp = $mobile;
                for ($i = 1; $i <= $codeLen; $i++) {
                    $appendedLen = strlen($mobileTmp);
                    if ($appendedLen < $this->mobileLen) {
                        $appendCode = substr($this->countryCode, 0, $i);
                        $mobileTmp = $appendCode . $mobile;
                        $appendedLen = $appendedLen = strlen($mobileTmp);
                        if ($this->normalizerDebug) {
                            show_warning('Try to append: ' . $appendCode . ' to ' . $mobile . ' now len of (' . $mobileTmp . ') is ' . strlen($mobileTmp));
                        }
                        if ($appendedLen == $this->mobileLen) {
                            $mobile = $mobileTmp;
                            if ($this->normalizerDebug) {
                                show_success('Yeah! now mobile normalized to ' . $mobile);
                            }
                        }
                    } else {
                        $mobile = $mobileTmp;
                        if ($this->normalizerDebug) {
                            show_success('Number len normalized: ' . $mobileTmp);
                        }
                    }
                }
            } else {
                if ($this->normalizerDebug) {
                    show_info('Number is ok by default: ' . $mobile);
                }
            }

//checking is number starting from full country code?
            if (strpos($mobile, $this->countryCode) === false) {
                if ($this->normalizerDebug) {
                    show_error('Number doesnt start with ' . $this->countryCode . ': ' . $mobile);
                }
                $mobile = '';
            }


//appending plus symbol due E164 standard
            $newLen = strlen($mobile);
            if ($newLen == $this->mobileLen) {
                $mobile = '+' . $mobile;
            } else {
                $mobile = '';
            }
        }
        return ($mobile);
    }

    /**
     * Extract mobile numbers from filtered entities array
     * 
     * @return void
     */
    protected function extractEntitiesNumbers() {

        if (!empty($this->filteredEntities)) {
            switch ($this->entitiesType) {
                case 'login':
                    if ($this->useExtMobiles) {
                        $this->extMobiles = new MobilesExt();
                    }
                    foreach ($this->filteredEntities as $io => $each) {
                        $userLogin = $each['login'];
                        $primaryMobile = $this->normalizePhoneFormat($each['mobile']);
                        if ((!empty($primaryMobile) AND ( !isset($this->excludeNumbers[$primaryMobile])))) {
                            $this->filteredNumbers[$userLogin][] = $primaryMobile;
                        }

                        if ($this->useExtMobiles) {
                            $userExtMobiles = $this->extMobiles->getUserMobiles($userLogin);
                            if (!empty($userExtMobiles)) {
                                foreach ($userExtMobiles as $ia => $eachExt) {
                                    $additionalMobile = $this->normalizePhoneFormat($eachExt['mobile']);
                                    if ((!empty($additionalMobile)) AND ( !isset($this->excludeNumbers[$additionalMobile]))) {
                                        $this->filteredNumbers[$userLogin][] = $additionalMobile;
                                        $this->extMobilesCount++;
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'ukv':
                    foreach ($this->filteredEntities as $io => $each) {
                        $userPrimaryMobile = $this->normalizePhoneFormat($each['mobile']);
                        if ((!empty($userPrimaryMobile)) AND ( !isset($this->excludeNumbers[$userPrimaryMobile]))) {
                            $this->filteredNumbers[$each['id']] = $userPrimaryMobile;
                        }
                    }
                    break;

                case 'employee':
                    foreach ($this->filteredEntities as $io => $each) {
                        $employeeMobile = $this->normalizePhoneFormat($each['mobile']);
                        if ((!empty($employeeMobile) AND ( !isset($this->excludeNumbers[$employeeMobile])))) {
                            $this->filteredNumbers[$each['id']] = $employeeMobile;
                        }
                    }
                    break;
                case 'numlist':
                    foreach ($this->filteredEntities as $io => $each) {
                        $numlistMobile = $this->normalizePhoneFormat($each['mobile']);
                        if ((!empty($numlistMobile) AND ( !isset($this->excludeNumbers[$numlistMobile])))) {
                            $this->filteredNumbers[$each['id']] = $numlistMobile;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Saves each filter workflow stats
     * 
     * @param string $filterName
     * @param int $entityCount
     * 
     * @return void
     */
    protected function saveFilterStats($filterName, $entityCount) {
        $this->filterStats[$filterName] = $entityCount;
    }

    /**
     * Renders some filtered processing stats and charts
     * 
     * @return string
     */
    protected function renderFilterStats() {
        $result = '';
        $params = array();
        $params[] = array(__('Filter'), __('Entities count'));

        $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";
        if (!empty($this->filterStats)) {
            foreach ($this->filterStats as $filterName => $entityCount) {
                $filterNormalName = (isset($this->filterNames[$filterName])) ? __($this->filterNames[$filterName]) : $filterName;
                $params[] = array($filterNormalName, $entityCount);
            }
            $result .= wf_gchartsLine($params, __('Filters'), '100%', '300px', $chartsOptions);
        }
        return ($result);
    }

    /**
     * Performs draft filter entities preprocessing
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return string
     */
    public function filtersPreprocessing($filterId, $templateId) {
        $result = array();
        $unknownFilters = array();
        if (isset($this->filters[$filterId])) {
            $filterData = $this->filters[$filterId]['filters'];
            $filterData = json_decode($filterData, true);
            if (isset($filterData['newfilterdirection'])) {
                $direction = $filterData['newfilterdirection'];
                $this->entitiesType = $direction;
                switch ($direction) {
                    case 'login':
                        $this->filteredEntities = $this->allUserData;
                        break;
                    case 'ukv':
                        $this->filteredEntities = $this->ukv->getUsers();
                        break;
                    case 'employee':
                        $this->filteredEntities = $this->employee;
                        break;
                    case 'numlist':
                        $this->filteredEntities = $this->allNumListsNumbers;
                        break;
                }

//setting base entities count
                $this->saveFilterStats('atstart', sizeof($this->filteredEntities));
                /**
                 * Knowing that I pack your things up in lie
                 * I guess I'll beware of love with a bite
                 * Roll on, girl. Roll, roll to tomorrow
                 * Rollin' on, girl without feeling tonight
                 */
                foreach ($filterData as $eachFilter => $eachFilterParam) {
                    if ((ispos($eachFilter, 'newfilter')) AND ( $eachFilter != 'newfilterdirection') AND ( $eachFilter != 'newfiltername')) {
                        $filterMethodName = str_replace('new', '', $eachFilter);
                        if (method_exists($this, $filterMethodName)) {
                            $this->$filterMethodName($direction, $eachFilterParam);
//saving filter stats
                            if (!empty($eachFilterParam)) {
                                $this->saveFilterStats($filterMethodName, sizeof($this->filteredEntities));
                            }
                        } else {
                            show_error(__('Something went wrong') . ': UNKNOWN_FILTER_METHOD ' . $filterMethodName);
                        }
                    }
                }
            }
        }

        if ((!empty($this->filteredEntities)) AND ( !empty($this->entitiesType))) {
            if (wf_CheckPost(array('sendingvisualfilters'))) {
                show_window(__('Filters workflow visualization'), $this->renderFilterStats());
            }
            $this->extractEntitiesNumbers();
            $sendingStats = $this->messages->getStyledMessage(__('Entities filtered') . ': ' . sizeof($this->filteredEntities) . ' ' . __('Numbers extracted') . ': ' . (sizeof($this->filteredNumbers) + $this->extMobilesCount), 'info');
            if (wf_CheckPost(array('sendingperform'))) {
                $sendingStats .= $this->messages->getStyledMessage(__('SMS for all of extracted numbers stored in sending queue'), 'success');
            }
            show_window('', $sendingStats);

            $this->generateSmsPool($filterId, $templateId);
            show_window(__('Preview'), $this->renderSmsPoolPreviewContainer($filterId, $templateId));
        } else {
            show_warning(__('Nothing found'));
        }

        return ($result);
    }

    /**
     * Renders SMS pool preview container
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return string
     */
    protected function renderSmsPoolPreviewContainer($filterId, $templateId) {
        $result = '';
        if ($this->sms->smsRoutingFlag) {
            $columns = array('SMS direction', 'Mobile', 'Text', 'Size', __('Count') . ' ' . 'SMS', __('SMS service'));
        } else {
            $columns = array('SMS direction', 'Mobile', 'Text', 'Size', __('Count') . ' ' . 'SMS');
        }

        $result = wf_JqDtLoader($columns, self::URL_ME . '&sending=true&ajpreview=true&filterid=' . $filterId . '&templateid=' . $templateId, false, __('SMS'), 100);
        return ($result);
    }

    /**
     * Renders previously cached preview JSON data
     * 
     * @param int $filterId
     * @param int $templateId
     * 
     * @return void
     */
    public function ajaxPreviewReply($filterId, $templateId) {
        $result = '';
        $previewCacheName = self::POOL_PATH . 'SMZ_PREVIEW_' . $filterId . '_' . $templateId;
        if (file_exists($previewCacheName)) {
            die(file_get_contents($previewCacheName));
        } else {
            $reply = array('aaData' => array());
            die(json_encode($reply));
        }
    }

    /**
     * Generates SMS text for sending/preview
     * 
     * @param int $templateId
     * @param array $entity
     * @param bool $forceTranslit
     * 
     * @return strings
     */
    protected function generateSmsText($templateId, $entity, $forceTranslit) {
        $result = '';

        $result = $this->templates[$templateId]['text'];
        switch ($this->entitiesType) {
            case 'login':
                $result = str_ireplace('{LOGIN}', $this->filteredEntities[$entity]['login'], $result);
                $result = str_ireplace('{REALNAME}', $this->filteredEntities[$entity]['realname'], $result);
                $result = str_ireplace('{TARIFF}', $this->filteredEntities[$entity]['Tariff'], $result);
                $result = str_ireplace('{TARIFFPRICE}', @$this->allTariffPrices[$this->filteredEntities[$entity]['Tariff']], $result);
                $result = str_ireplace('{PAYMENTID}', @$this->opCustomers[$this->filteredEntities[$entity]['login']], $result);
                $result = str_ireplace('{CREDIT}', $this->filteredEntities[$entity]['Credit'], $result);
                $result = str_ireplace('{CASH}', $this->filteredEntities[$entity]['Cash'], $result);
                if (@empty($this->filteredEntities[$entity]['TariffChange'])) {
                    $lackCash = @$this->allTariffPrices[$this->filteredEntities[$entity]['Tariff']] - $this->filteredEntities[$entity]['Cash'];
                } else {
                    $lackCash = @$this->allTariffPrices[$this->filteredEntities[$entity]['TariffChange']] - $this->filteredEntities[$entity]['Cash'];
                }
                $result = str_ireplace('{LACK}', $lackCash, $result);
                $result = str_ireplace('{ROUNDCASH}', round($this->filteredEntities[$entity]['Cash'], 2), $result);
                $result = str_ireplace('{IP}', $this->filteredEntities[$entity]['ip'], $result);
                $result = str_ireplace('{MAC}', $this->filteredEntities[$entity]['mac'], $result);
                $result = str_ireplace('{FULLADDRESS}', $this->filteredEntities[$entity]['fulladress'], $result);
                $result = str_ireplace('{PHONE}', $this->filteredEntities[$entity]['phone'], $result);
                $result = str_ireplace('{MOBILE}', $this->filteredEntities[$entity]['mobile'], $result);
                $result = str_ireplace('{CONTRACT}', $this->filteredEntities[$entity]['contract'], $result);
                $result = str_ireplace('{EMAIL}', $this->filteredEntities[$entity]['email'], $result);
                $result = str_ireplace('{CURDATE}', date("Y-m-d"), $result);
                $result = str_ireplace('{PASSWORD}', $this->filteredEntities[$entity]['Password'], $result);
                break;
            case 'ukv':

                $result = str_ireplace('{REALNAME}', $this->filteredEntities[$entity]['realname'], $result);
                $result = str_ireplace('{TARIFF}', $this->ukv->tariffGetName($this->filteredEntities[$entity]['tariffid']), $result);
                $result = str_ireplace('{CASH}', $this->filteredEntities[$entity]['cash'], $result);
                $result = str_ireplace('{ROUNDCASH}', round($this->filteredEntities[$entity]['cash'], 2), $result);
                $result = str_ireplace('{FULLADDRESS}', $this->ukv->userGetFullAddress($entity), $result);
                $result = str_ireplace('{PHONE}', $this->filteredEntities[$entity]['phone'], $result);
                $result = str_ireplace('{MOBILE}', $this->filteredEntities[$entity]['mobile'], $result);
                $result = str_ireplace('{CONTRACT}', $this->filteredEntities[$entity]['contract'], $result);
                $result = str_ireplace('{CURDATE}', date("Y-m-d"), $result);
                break;
        }
        if ($forceTranslit) {
            $result = zb_TranslitString($result, true);
        }
        return ($result);
    }

    /**
     * Returns nearest sending SMS count for multibyte encodings
     * 
     * @param string $messageText
     * 
     * @return int
     */
    protected function getSmsCount($textLen) {
        $result = 0;
        $result = ceil($textLen / $this->smsLenLimit);
        return ($result);
    }

    /**
     * Generates SMS pool for preview rendering or further sending
     * 
     * @param type $filterId
     * @param type $templateId
     * 
     * @return void
     */
    protected function generateSmsPool($filterId, $templateId) {
        $json = new wf_JqDtHelper();
        $data = array();
        $realSending = (wf_CheckPost(array('sendingperform'))) ? true : false;
        $forceTranslit = (wf_CheckPost(array('forcetranslit'))) ? true : false;
        if (!$realSending) {
//Remote API background call
            $realSending = (wf_CheckGet(array('key', 'action', 'filterid', 'templateid'))) ? true : false;
        }
        if (!$forceTranslit) {
//Remote API translit option instead
            $forceTranslit = (wf_CheckGet(array('translit'))) ? true : false;
        }
        $sendCounter = 0;
//changing nearest SMS bytes limit
        if ($forceTranslit) {
            $this->smsLenLimit = 160;
        } else {
            $this->smsLenLimit = 70;
        }

        if (!empty($this->filteredNumbers)) {
            switch ($this->entitiesType) {
                case 'login':
                    foreach ($this->filteredNumbers as $entityId => $numbers) {
                        if (!empty($numbers)) {
                            foreach ($numbers as $io => $eachNumber) {
                                $userLogin = $entityId;
                                $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $this->filteredEntities[$userLogin]['fulladress']);

                                $messageText = $this->generateSmsText($templateId, $userLogin, $forceTranslit);
                                $textLen = mb_strlen($messageText, 'utf-8');
                                $smsCount = $this->getSmsCount($textLen);
                                $messageDirection = '';

                                $data[] = $userLink . ' ' . $this->filteredEntities[$userLogin]['realname'];
                                $data[] = $eachNumber;
                                $data[] = $messageText;
                                $data[] = $textLen;
                                $data[] = $smsCount;
                                if ($this->sms->smsRoutingFlag) {
                                    $messageDirection = $this->sms->smsDirections->getDirection('user_login', $userLogin);
                                    $data[] = $this->sms->smsDirections->getDirectionNameById($messageDirection);
                                }

                                $json->addRow($data);
                                unset($data);

//pushing some messages into queue
                                if ($realSending) {
                                    $queueFile = $this->sms->sendSMS($eachNumber, $messageText, false, 'SMSZILLA');
                                    $this->sms->setDirection($queueFile, 'user_login', $userLogin, $messageDirection);
                                    $sendCounter++;
                                }
                            }
                        }
                    }
                    break;

                case 'ukv':
                    foreach ($this->filteredNumbers as $entityId => $number) {
                        if (!empty($number)) {
                            $userId = $entityId;
                            $userLink = wf_Link('?module=ukv&users=true&showuser=' . $userId, web_profile_icon() . ' ' . $this->ukv->userGetFullAddress($userId));
                            $messageText = $this->generateSmsText($templateId, $userId, $forceTranslit);
                            $textLen = mb_strlen($messageText, 'utf-8');
                            $smsCount = $this->getSmsCount($textLen);

                            $data[] = $userLink . ' ' . $this->filteredEntities[$userId]['realname'];
                            $data[] = $number;
                            $data[] = $messageText;
                            $data[] = $textLen;
                            $data[] = $smsCount;
                            $json->addRow($data);
                            unset($data);

//pushing some messages into queue
                            if ($realSending) {
                                $this->sms->sendSMS($number, $messageText, false, 'SMSZILLA');
                                $sendCounter++;
                            }
                        }
                    }
                    break;

                case 'employee':
                    foreach ($this->filteredNumbers as $entityId => $number) {
                        if (!empty($number)) {
                            $employeeId = $entityId;
                            $employeeLink = wf_Link('?module=employee&edit=' . $employeeId, web_profile_icon() . ' ' . $this->employee[$employeeId]['name']);
                            $messageText = $this->generateSmsText($templateId, $employeeId, $forceTranslit);
                            $textLen = mb_strlen($messageText, 'utf-8');
                            $smsCount = $this->getSmsCount($textLen);

                            $data[] = $employeeLink;
                            $data[] = $number;
                            $data[] = $messageText;
                            $data[] = $textLen;
                            $data[] = $smsCount;
                            $json->addRow($data);
                            unset($data);

//pushing some messages into queue
                            if ($realSending) {
                                $this->sms->sendSMS($number, $messageText, false, 'SMSZILLA');
                                $sendCounter++;
                            }
                        }
                    }
                    break;
                case 'numlist':
                    foreach ($this->filteredNumbers as $entityId => $number) {
                        if (!empty($number)) {
                            $numId = $entityId;
                            $messageText = $this->generateSmsText($templateId, $numId, $forceTranslit);
                            $textLen = mb_strlen($messageText, 'utf-8');
                            $smsCount = $this->getSmsCount($textLen);

                            $data[] = $this->allNumListsNumbers[$numId]['notes'];
                            $data[] = $number;
                            $data[] = $messageText;
                            $data[] = $textLen;
                            $data[] = $smsCount;
                            $json->addRow($data);
                            unset($data);

//pushing some messages into queue
                            if ($realSending) {
                                $this->sms->sendSMS($number, $messageText, false, 'SMSZILLA');
                                $sendCounter++;
                            }
                        }
                    }
                    break;
            }
//logging if SMS really sent
            if ($realSending) {
                log_register('SMSZILLA SENDING TEMPLATE [' . $templateId . '] FILTER [' . $filterId . '] COUNT `' . $sendCounter . '`');
            }
        }
//saving preview data
        file_put_contents(self::POOL_PATH . 'SMZ_PREVIEW_' . $filterId . '_' . $templateId, $json->extractJson());
    }

    /**
     * City filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercity($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cityname'] != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['city'] != $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Address filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteraddress($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['fulladress'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $userAddress = $this->ukv->userGetFullAddress($entity['id']);
                            if (!ispos($userAddress, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Login filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterlogin($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['login'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * IP substring filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterip($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['ip'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Month cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashmonth($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
//init slow funds flow object if required
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            $daysLeft = $this->fundsFlow->getOnlineLeftCountFast($entity['login']);
                            if ($daysLeft > 0) {
                                $curMonthDays = date("t");
                                $curDate = date("j");
                                /**
                                 * ─────█─▄▀█──█▀▄─█─────
                                 * ────▐▌──────────▐▌────
                                 * ────█▌▀▄──▄▄──▄▀▐█────
                                 * ───▐██──▀▀──▀▀──██▌───
                                 * ──▄████▄──▐▌──▄████▄──
                                 */
                                $curMonthOffset = strtotime(date("Y-m-05"));
                                /**
                                 * remember remember the 5th of november
                                 */
                                $nextMonthDays = date("t", strtotime(date('Y-m-d', strtotime('+1 month', $curMonthOffset))));
                                $expr = ($curMonthDays - $curDate) + $nextMonthDays;
                                if ($daysLeft > $expr) {
                                    unset($this->filteredEntities[$entity['login']]);
                                }
                            } else {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Days less cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashdays($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
//init slow funds flow object if required
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            $daysLeft = $this->fundsFlow->getOnlineLeftCountFast($entity['login']);
                            if ($daysLeft > 0) {
                                if ($daysLeft > $param) {
                                    unset($this->filteredEntities[$entity['login']]);
                                }
                            } else {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Greater cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashgreater($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] < $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] < $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Lesser cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashlesser($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] > $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] > $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Lesser zero cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashlesszero($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] >= 0) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] >= 0) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Zero cash filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercashzero($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Cash'] != 0) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['cash'] != 0) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Credit filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtercreditset($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Credit'] == 0) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Tags filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtertags($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->checkInetTagId($entity['login'], $param)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->checkUkvTagId($entity['id'], $param)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * AlwaysOnline filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterao($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['AlwaysOnline'] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Passive filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterpassive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Passive'] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Switch filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterswitch($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->checkSwitchId($entity['login'], $param)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Not passive filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternotpassive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Passive'] != '0') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * User activity filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteractive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Passive'] == '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }

                            if ($entity['Down'] == '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }

                            if ($entity['AlwaysOnline'] == '0') {
                                unset($this->filteredEntities[$entity['login']]);
                            }

                            if ($entity['Cash'] < '-' . $entity['Credit']) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Down filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterdown($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($this->downUsers[$entity['login']] != '1') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Tariff filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtertariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Tariff'] != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Tariff filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filtertariffcontain($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['Tariff'], $param)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Branch filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterbranch($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($this->branches->userGetBranch($entity['login']) != $param) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Districts filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterdistrict($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!$this->districts->checkUserDistrictFast($entity['login'], $param)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * No tariff users filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternotariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['Tariff'] != '*_NO_TARIFF_*') {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV tariff users filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvtariff($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['tariffid'] != $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Numlist ID filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternumlist($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'numlist':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['numid'] != $param) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Numlist notes contains filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternumcontain($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'numlist':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['notes'], $param)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Numlist notes not contains filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternumnotcontain($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'numlist':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (ispos($entity['notes'], $param)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Numlist not our user filter by mobile number
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filternumnotouruser($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'numlist':
                        $this->extMobiles = new MobilesExt();

                        foreach ($this->filteredEntities as $io => $entity) {
                            $numlistMobile = $this->normalizePhoneFormat($entity['mobile']);
                            if (!empty($this->allUserData)) {
                                foreach ($this->allUserData as $eachUserLogin => $eachUserData) {
//base numbers comparison
                                    if (ispos($this->normalizePhoneFormat($eachUserData['mobile']), $numlistMobile)) {
                                        unset($this->filteredEntities[$entity['id']]);
                                        break;
                                    }
//check for additional mobile
                                    $userExtMobiles = $this->extMobiles->getUserMobiles($eachUserLogin);
                                    if (!empty($userExtMobiles)) {
                                        foreach ($userExtMobiles as $ia => $eachExt) {
                                            $additionalMobile = $this->normalizePhoneFormat($eachExt['mobile']);
                                            if (!empty($additionalMobile)) {
                                                if (ispos($this->normalizePhoneFormat($additionalMobile), $numlistMobile)) {
                                                    unset($this->filteredEntities[$entity['id']]);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV activity filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvactive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['active'] != '1') {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Employee appointment filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteremployeeappointment($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'employee':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['appointment'], $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Employee activity filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filteremployeeactive($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'employee':
                        foreach ($this->filteredEntities as $io => $entity) {
                            if ($entity['active'] != '1') {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * UKV debtors filter
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterukvdebtor($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'ukv':
//one time debtors loading on filter run
                        if (!$this->ukvDebtorsLoaded) {
                            $this->ukvDebtors = $this->ukv->getDebtors();
                            $this->ukvDebtorsLoaded = true;
                        }
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!isset($this->ukvDebtors[$entity['id']])) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Just sets ext mobiles extraction flag
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterextmobiles($direction, $param) {
        $this->useExtMobiles = true;
    }

    /**
     * 
     * @param string $direction
     * @param string $param
     * 
     * @return void
     */
    protected function filterrealname($direction, $param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                switch ($direction) {
                    case 'login':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            if (!ispos($entity['realname'], $search)) {
                                unset($this->filteredEntities[$entity['login']]);
                            }
                        }
                        break;
                    case 'ukv':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $userRealName = $entity['realname'];
                            if (!ispos($userRealName, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                    case 'employee':
                        $search = trim($param);
                        foreach ($this->filteredEntities as $io => $entity) {
                            $employeeRealName = $this->filteredEntities[$entity['id']]['name'];
                            if (!ispos($employeeRealName, $search)) {
                                unset($this->filteredEntities[$entity['id']]);
                            }
                        }
                        break;
                }
            }
        }
    }

}

?>
