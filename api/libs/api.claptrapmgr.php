<?php

/**
 * CL4P-TP management interface
 */
class ClapTrapMgr {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains current instance bot token
     *
     * @var string
     */
    protected $token = '';

    /**
     * Contains current instance hook URL
     *
     * @var string
     */
    protected $hookUrl = '';

    /**
     * Contains current instance authentication string
     *
     * @var string
     */
    protected $authString = '';

    /**
     * Telegram API object instance
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * ClapTrapBot object instance
     *
     * @var object
     */
    protected $botInstance = '';

    /**
     * Contains messages helper object instance
     *
     * @var object
     */
    protected $messages = '';

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
     * Contains available filters names
     *
     * @var array
     */
    protected $filterNames = array();

    /**
     * Contains supported template macros
     *
     * @var array
     */
    protected $supportedMacro = array();

    /**
     * Contains bot auth data as login=>authData
     *
     * @var array
     */
    protected $botAuth = array();

    /**
     * Contains entities that passed full filter set
     *
     * @var array
     */
    protected $filteredEntities = array();

    /**
     * Contains filtered chat IDs as login=>chatid
     *
     * @var array
     */
    protected $filteredChatIds = array();

    /**
     * FundsFlow object placeholder
     *
     * @var object
     */
    protected $fundsFlow = '';

    /**
     * Contains tags for internet users as login=>array(tagid=>tagname)
     *
     * @var array
     */
    protected $inetTags = array();

    /**
     * Contains all users and their down state as login=>state
     *
     * @var array
     */
    protected $downUsers = array();

    /**
     * OpenPayz customers as login=>paymentid
     *
     * @var array
     */
    protected $opCustomers = array();

    /**
     * Filter workflow stats
     *
     * @var array
     */
    protected $filterStats = array();


    /**
     * Templates database abstraction layer
     *
     * @var object
     */
    protected $templatesDb = '';

    /**
     * Filters database abstraction layer
     *
     * @var object
     */
    protected $filtersDb = '';

    const URL_ME = '?module=cltpmgr';
    const ROUTE_USERS = 'showusers';
    const ROUTE_CONFIG = 'hookconfig';
    const ROUTE_SENDING = 'messagesending';
    const ROUTE_TEMPLATES = 'templates';
    const ROUTE_FILTERS = 'filters';
    const ROUTE_INSTALL = 'install';
    const PROUTE_HOOK_URL = 'newhookinstall';
    const POOL_PATH = './exports/';
    const URL_MACROHELP = 'http://wiki.ubilling.net.ua/doku.php?id=templating&#smszilla';
    const TABLE_TEMPLATES = 'ct_templates';
    const TABLE_FILTERS = 'ct_filters';

    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->setOptions();
        $this->initTelegram();
        $this->initBotInstance();
        //required only for messages sending
        if (ubRouting::checkGet(self::ROUTE_SENDING)) {
        $this->initTemplatesFiltersDb();
        $this->loadCities();
        $this->loadTariffs();
        $this->loadTagTypes();
        $this->loadInetTags();
        $this->loadUsers();
        $this->loadOpCustomers();
        $this->loadTemplates();
        $this->loadFilters();
        $this->loadBotAuth();
        }
    }

    /**
     * Loads system alter config into protected property for further usage
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
     * Sets current instance options from config values
     *
     * @return void
     */
    protected function setOptions() {
        $this->token = $this->altCfg[ClapTrapBot::OPTION_TOKEN];
        $this->hookUrl = $this->altCfg[ClapTrapBot::OPTION_HOOK_URL];
        $this->authString = $this->altCfg[ClapTrapBot::OPTION_AUTH_STRING];

        $this->filterNames = array(
            'atstart' => 'At begining',
            'filtername' => 'Filter name',
            'filteraddress' => 'Address contains',
            'filterao' => 'User is AlwaysOnline',
            'filtercashdays' => 'Balance is enought less than days',
            'filtercashgreater' => 'Balance is greater than',
            'filtercashlesser' => 'Balance is less than',
            'filtercashlesszero' => 'Balance is less than zero',
            'filtercashzero' => 'Balance is zero',
            'filtercreditset' => 'User have credit',
            'filtercashmonth' => 'Balance is not enough for the next month',
            'filtercity' => 'City',
            'filterdown' => 'User is down',
            'filterip' => 'IP contains',
            'filtertariffnm' => 'Planned tariff change',
            'filterpassive' => 'User is frozen',
            'filternotpassive' => 'User is not frozen',
            'filteractive' => 'User is active',
            'filtertags' => 'User have tag assigned',
            'filtertariff' => 'User have tariff',
            'filtertariffcontain' => 'User tariff contains',
            'filterrealname' => 'Real Name contains',
        );

        $this->supportedMacro = array(
            '{LOGIN}' => __('Login'),
            '{REALNAME}' => __('Real Name'),
            '{TARIFF}' => __('Tariff'),
            '{TARIFFPRICE}' => __('Tariff fee'),
            '{TARIFFPERIOD}' => __('Tariff period'),
            '{TARIFFNM}' => __('Tariff') . ' ' . __('Next month'),
            '{TARIFFNMPRICE}' => __('Tariff fee') . ' ' . __('Next month'),
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
            '{PASSWORD}' => __('Password'),
            '{USERONLINELEFTDAY}' => __('The remaining number of days to use the service'),
            '{USERONLINETODATE}' => __('Tariff period'),
        );
    }

    /**
     * Initializes Telegram object instance
     *
     * @return void
     */
    protected function initTelegram() {
        if (!empty($this->token)) {
            $this->telegram = new UbillingTelegram($this->token);
        }
    }

    /**
     * Initializes message helper object instance
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initializes ClapTrapBot object instance
     *
     * @return void
     */
    protected function initBotInstance() {
        if (!empty($this->token)) {
            $this->botInstance = new ClapTrapBot($this->token);
        }
    }

    /**
     * Loads existing cities from database
     *
     * @return void
     */
    protected function loadCities() {
        $this->allCities = zb_AddressGetFullCityNames();
    }

    /**
     * Loads existing tariffs from database
     *
     * @return void
     */
    protected function loadTariffs() {
        $allTariffsData=zb_TariffGetAllData();
        if (!empty($allTariffsData)) {
            foreach ($allTariffsData as $io => $each) {
                $this->allTariffs[$each['name']] = $each;
                $this->allTariffPrices[$each['name']] = $each['Fee'];
            }
        }
    }

    /**
     * Loads existing tag types from database
     *
     * @return void
     */
    protected function loadTagTypes() {
        $this->allTagTypes=stg_get_alltagnames();
    }

    /**
     * Loads user tags as login=>array(tagid=>tagname)
     *
     * @return void
     */
    protected function loadInetTags() {
        $this->inetTags = zb_UserGetAllTags();
    }

    /**
     * Loads all existing Internet users from database
     *
     * @return void
     */
    protected function loadUsers() {
        $this->allUserData = zb_UserGetAllDataCache();
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $login => $userData) {
              $this->downUsers[$login] = $userData['Down'];
            }
        }
    }

    /**
     * Sets up OpenPayz paymentIDs as login=>paymentid into protected property for further usage
     *
     * @return void
     */
    protected function loadOpCustomers() {
        if ($this->altCfg['OPENPAYZ_SUPPORT']) {
            $openPayz=new OpenPayz(false,true);
            $allCustomerPaymentIds=$openPayz->getCustomers();
            $this->opCustomers=array_flip($allCustomerPaymentIds);
        }
    }

    /**
     * Inits templates and filters database abstraction layers
     *
     * @return void
     */
    protected function initTemplatesFiltersDb() {
        $this->templatesDb = new NyanORM(self::TABLE_TEMPLATES);
        $this->filtersDb = new NyanORM(self::TABLE_FILTERS);
    }

    /**
     * Loads existing templates from database
     *
     * @return void
     */
    protected function loadTemplates() {
        $this->templatesDb->orderBy('id', 'ASC');
        $this->templates = $this->templatesDb->getAll('id');
    }

    /**
     * Loads existing filters from database
     *
     * @return void
     */
    protected function loadFilters() {
        $this->filtersDb->orderBy('id', 'ASC');
        $this->filters = $this->filtersDb->getAll('id');
    }

    /**
     * Loads bot auth data
     *
     * @return void
     */
    protected function loadBotAuth() {
        if (!empty($this->botInstance)) {
            $this->botAuth = $this->botInstance->getAuthDataAll();
        }
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
     * Returns user online left days
     *
     * @param string $login
     *
     * @return int
     */
    protected function getUserOnlineLeftDayCount($login) {
        $result = 0;
        if (empty($this->fundsFlow)) {
            $this->initFundsFlow();
        }
        $onlineLeftCount = $this->fundsFlow->getOnlineLeftCountFast($login);
        if ($onlineLeftCount >= 0) {
            $result = $onlineLeftCount;
        }
        return ($result);
    }

    /**
     * Returns user online to date
     *
     * @param string $login
     *
     * @return string
     */
    protected function getUserOnlineToDate($login) {
        $result = date("d.m.Y");
        if (empty($this->fundsFlow)) {
            $this->initFundsFlow();
        }
        $daysOnLine = $this->fundsFlow->getOnlineLeftCountFast($login);
        if ($daysOnLine >= 0) {
            $result = date("d.m.Y", time() + ($daysOnLine * 24 * 60 * 60));
        }
        return ($result);
    }

    /**
     * Renders module control panel
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_USERS . '=true', web_icon_search() . ' ' . __('Users'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CONFIG . '=true', web_icon_extended() . ' ' . __('Configuration'), false, 'ubButton') . ' ';
        if (!empty($this->altCfg['SENDDOG_ENABLED'])) {
            $result .= wf_Link($this->urlSending(), wf_img('skins/icon_telegram_16.png') . ' ' . __('Messages sending'), false, 'ubButton') . ' ';
        }
        return ($result);
    }

    /**
     * Returns URL for message sending section with optional extra query params
     *
     * @param string $extra
     *
     * @return string
     */
    public function urlSending($extra = '') {
        $result = self::URL_ME . '&' . self::ROUTE_SENDING . '=true';
        if (!empty($extra)) {
            $result .= '&' . $extra;
        }
        return ($result);
    }

    /**
     * Renders message sending section sub-navigation
     *
     * @return string
     */
    public function renderSendingControls() {
        $result = '';
        $result .= wf_Link($this->urlSending(), wf_img('skins/icon_sms_micro.gif') . ' ' . __('Send'), false, 'ubButton') . ' ';
        $result .= wf_Link($this->urlSending(self::ROUTE_TEMPLATES . '=true'), wf_img('skins/icon_template.png') . ' ' . __('Templates'), false, 'ubButton') . ' ';
        $result .= wf_Link($this->urlSending(self::ROUTE_FILTERS . '=true'), web_icon_extended() . ' ' . __('Filters'), false, 'ubButton') . ' ';

        if (ubRouting::checkGet(self::ROUTE_TEMPLATES)) {
            $result .= wf_delimiter(1);
            if (ubRouting::checkGet('edittemplate')) {
                $result .= wf_BackLink($this->urlSending(self::ROUTE_TEMPLATES . '=true')) . ' ';
            } else {
                $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new template'), __('Create new template'), $this->renderTemplateCreateForm(), 'ubButton');
            }
        }

        if (ubRouting::checkGet(self::ROUTE_FILTERS)) {
            $result .= wf_delimiter(1);
            $result .= wf_modalAuto(web_icon_create() . ' ' . __('New filter creation'), __('New filter creation'), $this->renderFilterCreateForm(), 'ubButton');
        }
        return ($result);
    }

    /**
     * Creates new message text template
     *
     * @param string $name
     * @param string $text
     *
     * @return int
     */
    public function createTemplate($name, $text) {
        $name = ubRouting::filters($name, 'mres');
        $text = ubRouting::filters($text, 'emsafe');
        $text = ubRouting::filters($text, 'mres');
        $this->templatesDb->data('name', $name);
        $this->templatesDb->data('text', $text);
        $this->templatesDb->create();
        $newId = $this->templatesDb->getLastId();
        log_register('CLAPTRAPMGR TEMPLATE CREATE [' . $newId . ']');
        return ($newId);
    }

    /**
     * Deletes existing template
     *
     * @param int $templateId
     *
     * @return string
     */
    public function deleteTemplate($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $this->templatesDb->where('id', '=', $templateId);
            $this->templatesDb->delete();
            log_register('CLAPTRAPMGR TEMPLATE DELETE [' . $templateId . ']');
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
     * @return string
     */
    public function saveTemplate($templateId, $name, $text) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $name = ubRouting::filters($name, 'mres');
            $text = ubRouting::filters($text, 'emsafe');
            $text = ubRouting::filters($text, 'mres');
            $this->templatesDb->data('name', $name);
            $this->templatesDb->data('text', $text);
            $this->templatesDb->where('id', '=', $templateId);
            $this->templatesDb->save(true, true);
            log_register('CLAPTRAPMGR TEMPLATE CHANGE [' . $templateId . ']');
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
        $form = wf_Form($this->urlSending(self::ROUTE_TEMPLATES . '=true'), 'POST', $inputs, 'glamour');
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
            $form = wf_Form($this->urlSending(self::ROUTE_TEMPLATES . '=true&edittemplate=' . $templateId), 'POST', $inputs, 'glamour');
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
     * Renders existing templates list with controls
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
                $actLinks = wf_JSAlert($this->urlSending(self::ROUTE_TEMPLATES . '=true&deletetemplate=' . $each['id']), web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_JSAlert($this->urlSending(self::ROUTE_TEMPLATES . '=true&edittemplate=' . $each['id']), web_edit_icon(), $this->messages->getEditAlert());
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
     * Renders filter creation form
     *
     * @return string
     */
    public function renderFilterCreateForm() {
        $result = '';
        $citiesParams = array('' => __('Any'));
        if (!empty($this->allCities)) {
            foreach ($this->allCities as $cityId => $cityName) {
                $citiesParams[$cityId] = $cityName;
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

        $inputs = wf_TextInput('newfiltername', __('Filter name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '30');
        $inputs .= wf_Selector('newfiltercity', $citiesParams, __('City'), '', true, false);
        $inputs .= wf_TextInput('newfilteraddress', __('Address contains'), '', true, '40');
        $inputs .= wf_TextInput('newfilterrealname', __('Real Name') . ' ' . __('contains'), '', true, '30');
        $inputs .= wf_TextInput('newfilterip', __('IP contains'), '', true, '20');
        $inputs .= wf_CheckInput('newfiltercashmonth', __('Balance is not enough for the next month'), true, false);
        $inputs .= wf_TextInput('newfiltercashdays', __('Balance is enought less than days'), '', true, '5');
        $inputs .= wf_TextInput('newfiltercashgreater', __('Balance is greater than'), '', true, '5');
        $inputs .= wf_TextInput('newfiltercashlesser', __('Balance is less than'), '', true, '5');
        $inputs .= wf_CheckInput('newfiltercashlesszero', __('Balance is less than zero'), true, false);
        $inputs .= wf_CheckInput('newfiltercashzero', __('Balance is zero'), true, false);
        $inputs .= wf_Selector('newfiltertags', $tagsParams, __('User have tag assigned'), '', true, false);
        $inputs .= wf_CheckInput('newfiltercreditset', __('User have credit'), true, false);
        $inputs .= wf_CheckInput('newfilterpassive', __('User is frozen'), true, false);
        $inputs .= wf_CheckInput('newfilternotpassive', __('User is not frozen'), true, false);
        $inputs .= wf_CheckInput('newfilteractive', __('User is active'), true, false);
        $inputs .= wf_CheckInput('newfilterdown', __('User is down'), true, false);
        $inputs .= wf_CheckInput('newfilterao', __('User is AlwaysOnline'), true, true);
        $inputs .= wf_Selector('newfiltertariff', $tariffParams, __('User have tariff'), '', true, false);
        $inputs .= wf_TextInput('newfiltertariffcontain', __('User tariff contains'), '', true, '15');
        $inputs .= wf_CheckInput('newfiltertariffnm', __('Planned tariff change'), true, false);
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Create'));
        $result = wf_Form($this->urlSending(self::ROUTE_FILTERS . '=true'), 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new filter in database
     *
     * @return string
     */
    public function createFilter() {
        $result = '';
        if (ubRouting::checkPost('newfiltername')) {
            $filterName = ubRouting::post('newfiltername');
            $filterNameF = ubRouting::filters($filterName, 'mres');
            if (!empty($filterNameF)) {
                $filterParams = array();
                foreach (ubRouting::rawPost() as $io => $each) {
                    if (ispos($io, 'newfilter')) {
                        $filterParams[$io] = $each;
                    }
                }
                if (!empty($filterParams)) {
                    $filterParams = json_encode($filterParams);
                    $filterParams = ubRouting::filters($filterParams, 'mres');
                    $this->filtersDb->data('name', $filterNameF);
                    $this->filtersDb->data('filters', $filterParams);
                    $this->filtersDb->create();
                    $newId = $this->filtersDb->getLastId();
                    log_register('CLAPTRAPMGR FILTER CREATE [' . $newId . '] `' . $filterName . '`');
                }
            } else {
                $result = __('Something went wrong') . ': EX_FILTER_NAME_EMPTY';
            }
        }
        return ($result);
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
                    if ($io == 'newfiltername' or $io == 'newfilterdirection') {
                        continue;
                    }
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
                $actLinks = wf_JSAlert($this->urlSending(self::ROUTE_FILTERS . '=true&deletefilterid=' . $each['id']), web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
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
     * @return string
     */
    public function deleteFilter($filterId) {
        $result = '';
        $filterId = vf($filterId, 3);
        if (isset($this->filters[$filterId])) {
            $this->filtersDb->where('id', '=', $filterId);
            $this->filtersDb->delete();
            log_register('CLAPTRAPMGR FILTER DELETE [' . $filterId . ']');
        } else {
            $result = __('Something went wrong') . ': FILTER_ID_NOT_EXISTS';
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
            if (isset($this->inetTags[$login][$tagId])) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Initializes base filtered entities from active bot subscribers
     *
     * @return void
     */
    protected function initBaseFilteredEntities() {
        $this->filteredEntities = array();
        if (!empty($this->botAuth) and !empty($this->allUserData)) {
            foreach ($this->botAuth as $eachLogin => $eachAuth) {
                if (!empty($eachAuth['active']) and $eachAuth['active'] == '1' and !empty($eachAuth['chatid'])) {
                    if (isset($this->allUserData[$eachLogin])) {
                        $this->filteredEntities[$eachLogin] = $this->allUserData[$eachLogin];
                    }
                }
            }
        }
    }

    /**
     * Extracts chat IDs from filtered entities
     *
     * @return void
     */
    protected function extractChatIds() {
        $this->filteredChatIds = array();
        if (!empty($this->filteredEntities)) {
            foreach ($this->filteredEntities as $eachLogin => $each) {
                if (isset($this->botAuth[$eachLogin])) {
                    if (!empty($this->botAuth[$eachLogin]['chatid'])) {
                        $this->filteredChatIds[$eachLogin] = $this->botAuth[$eachLogin]['chatid'];
                    }
                }
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
     * Renders filtered processing stats chart
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
     * Renders template and filters selection form
     *
     * @return string
     */
    public function renderSendingForm() {
        $result = '';
        $curTemplateId = (ubRouting::checkPost('sendingtemplateid')) ? ubRouting::post('sendingtemplateid') : '';
        $curFilterId = (ubRouting::checkPost('sendingfilterid')) ? ubRouting::post('sendingfilterid') : '';
        $curVisualFlag = (ubRouting::checkPost('sendingvisualfilters')) ? true : false;

        if (!(empty($this->templates)) and (!empty($this->filters))) {
            $templatesParams = array();
            foreach ($this->templates as $io => $each) {
                $templatesParams[$each['id']] = $each['name'];
            }

            $filterParams = array();
            foreach ($this->filters as $io => $each) {
                $filterParams[$each['id']] = $each['name'];
            }

            $inputs = wf_Selector('sendingtemplateid', $templatesParams, __('Template'), $curTemplateId, false) . ' ';
            $inputs .= wf_Selector('sendingfilterid', $filterParams, __('Filter'), $curFilterId, false) . ' ';
            $inputs .= wf_CheckInput('sendingvisualfilters', __('Visual'), false, $curVisualFlag) . ' ';
            $inputs .= wf_CheckInput('sendingperform', __('Perform real sending'), false, false) . ' ';
            $inputs .= wf_Submit(__('Send'));
            $result .= wf_Form($this->urlSending(), 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('No existing templates or filters available'), 'warning');
        }
        return ($result);
    }

    /**
     * Performs filter entities preprocessing
     *
     * @param int $filterId
     * @param int $templateId
     *
     * @return array
     */
    public function filtersPreprocessing($filterId, $templateId) {
        $result = array();
        $filterId = vf($filterId, 3);
        $templateId = vf($templateId, 3);
        if (isset($this->filters[$filterId])) {
            $filterData = $this->filters[$filterId]['filters'];
            $filterData = json_decode($filterData, true);
            if (!empty($filterData)) {
                $this->initBaseFilteredEntities();
                $this->saveFilterStats('atstart', sizeof($this->filteredEntities));
                foreach ($filterData as $eachFilter => $eachFilterParam) {
                    if ((ispos($eachFilter, 'newfilter')) and ($eachFilter != 'newfiltername')) {
                        $filterMethodName = str_replace('new', '', $eachFilter);
                        if (method_exists($this, $filterMethodName)) {
                            $this->$filterMethodName($eachFilterParam);
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

        if ((!empty($this->filteredEntities)) and (isset($this->templates[$templateId]))) {
            if (ubRouting::checkPost('sendingvisualfilters')) {
                show_window(__('Filters workflow visualization'), $this->renderFilterStats());
            }
            $this->extractChatIds();
            $sendingStats = $this->messages->getStyledMessage(__('Entities filtered') . ': ' . sizeof($this->filteredEntities) . ' ' . __('Chat IDs extracted') . ': ' . sizeof($this->filteredChatIds), 'info');
            if (ubRouting::checkPost('sendingperform')) {
                if (!empty($this->token) and !empty($this->telegram)) {
                    $sendingStats .= $this->messages->getStyledMessage(__('Messages for all extracted chat IDs stored in sending queue'), 'success');
                } else {
                    $sendingStats .= $this->messages->getStyledMessage(__('Token or hook URL is empty'), 'error');
                }
            }
            show_window('', $sendingStats);
            $this->generateMessagePool($filterId, $templateId);
            show_window(__('Preview'), $this->renderMessagePoolPreviewContainer($filterId, $templateId));
        } else {
            show_warning(__('Nothing found'));
        }
        return ($result);
    }

    /**
     * Renders message pool preview container
     *
     * @param int $filterId
     * @param int $templateId
     *
     * @return string
     */
    protected function renderMessagePoolPreviewContainer($filterId, $templateId) {
        $columns = array(__('User'), 'Chat ID', __('Text'), __('Text size'));
        $result = wf_JqDtLoader($columns, $this->urlSending('ajpreview=true&filterid=' . $filterId . '&templateid=' . $templateId), false, __('Messages'), 100);
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
        $previewCacheName = self::POOL_PATH . 'CT_PREVIEW_' . $filterId . '_' . $templateId;
        if (file_exists($previewCacheName)) {
            die(file_get_contents($previewCacheName));
        } else {
            $reply = array('aaData' => array());
            die(json_encode($reply));
        }
    }

    /**
     * Generates message text for sending/preview
     *
     * @param int $templateId
     * @param string $login
     *
     * @return string
     */
    protected function generateMessageText($templateId, $login) {
        $result = $this->templates[$templateId]['text'];
        $result = str_ireplace('{LOGIN}', $this->filteredEntities[$login]['login'], $result);
        $result = str_ireplace('{REALNAME}', $this->filteredEntities[$login]['realname'], $result);
        $result = str_ireplace('{TARIFF}', $this->filteredEntities[$login]['Tariff'], $result);
        $result = str_ireplace('{TARIFFPRICE}', @$this->allTariffPrices[$this->filteredEntities[$login]['Tariff']], $result);
        $result = str_ireplace('{TARIFFNM}', $this->filteredEntities[$login]['TariffChange'], $result);
        $result = str_ireplace('{TARIFFNMPRICE}', @$this->allTariffPrices[$this->filteredEntities[$login]['TariffChange']], $result);
        $result = str_ireplace('{TARIFFPERIOD}', __(@$this->allTariffs[$this->filteredEntities[$login]['Tariff']]['period']), $result);
        $result = str_ireplace('{PAYMENTID}', @$this->opCustomers[$this->filteredEntities[$login]['login']], $result);
        $result = str_ireplace('{CREDIT}', $this->filteredEntities[$login]['Credit'], $result);
        $result = str_ireplace('{CASH}', $this->filteredEntities[$login]['Cash'], $result);
        if (@empty($this->filteredEntities[$login]['TariffChange'])) {
            $lackCash = @$this->allTariffPrices[$this->filteredEntities[$login]['Tariff']] - $this->filteredEntities[$login]['Cash'];
        } else {
            $lackCash = @$this->allTariffPrices[$this->filteredEntities[$login]['TariffChange']] - $this->filteredEntities[$login]['Cash'];
        }
        $result = str_ireplace('{LACK}', $lackCash, $result);
        $result = str_ireplace('{ROUNDCASH}', round($this->filteredEntities[$login]['Cash'], 2), $result);
        $result = str_ireplace('{IP}', $this->filteredEntities[$login]['ip'], $result);
        $result = str_ireplace('{MAC}', $this->filteredEntities[$login]['mac'], $result);
        $result = str_ireplace('{FULLADDRESS}', $this->filteredEntities[$login]['fulladress'], $result);
        $result = str_ireplace('{PHONE}', $this->filteredEntities[$login]['phone'], $result);
        $result = str_ireplace('{MOBILE}', $this->filteredEntities[$login]['mobile'], $result);
        $result = str_ireplace('{CONTRACT}', $this->filteredEntities[$login]['contract'], $result);
        $result = str_ireplace('{EMAIL}', $this->filteredEntities[$login]['email'], $result);
        $result = str_ireplace('{CURDATE}', date("Y-m-d"), $result);
        $result = str_ireplace('{PASSWORD}', $this->filteredEntities[$login]['Password'], $result);
        $result = str_ireplace('{USERONLINELEFTDAY}', $this->getUserOnlineLeftDayCount($login), $result);
        $result = str_ireplace('{USERONLINETODATE}', $this->getUserOnlineToDate($login), $result);
        return ($result);
    }

    /**
     * Generates message pool for preview or queue sending
     *
     * @param int $filterId
     * @param int $templateId
     *
     * @return void
     */
    protected function generateMessagePool($filterId, $templateId) {
        $json = new wf_JqDtHelper();
        $data = array();
        $realSending = (ubRouting::checkPost('sendingperform')) ? true : false;
        $sendCounter = 0;

        if (!empty($this->filteredChatIds) and isset($this->templates[$templateId])) {
            foreach ($this->filteredChatIds as $userLogin => $chatId) {
                if (!empty($chatId)) {
                    $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . $this->filteredEntities[$userLogin]['fulladress']);
                    $messageText = $this->generateMessageText($templateId, $userLogin);
                    $textLen = mb_strlen($messageText, 'utf-8');

                    $data[] = $userLink . ' ' . $this->filteredEntities[$userLogin]['realname'];
                    $data[] = $chatId;
                    $data[] = $messageText;
                    $data[] = $textLen;
                    $json->addRow($data);
                    unset($data);

                    if ($realSending and !empty($this->token) and !empty($this->telegram)) {
                        $this->telegram->sendMessage($chatId, $messageText, false, 'CLAPTRAPMGR', $this->token);
                        $sendCounter++;
                    }
                }
            }
            if ($realSending) {
                log_register('CLAPTRAPMGR SENDING TEMPLATE [' . $templateId . '] FILTER [' . $filterId . '] COUNT `' . $sendCounter . '`');
            }
        }
        file_put_contents(self::POOL_PATH . 'CT_PREVIEW_' . $filterId . '_' . $templateId, $json->extractJson());
    }

    /**
     * City filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercity($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['cityname'] != $param) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Address filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filteraddress($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                $search = trim($param);
                foreach ($this->filteredEntities as $io => $entity) {
                    if (!ispos($entity['fulladress'], $search)) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Real name filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filterrealname($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                $search = trim($param);
                foreach ($this->filteredEntities as $io => $entity) {
                    if (!ispos($entity['realname'], $search)) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * IP substring filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filterip($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                $search = trim($param);
                foreach ($this->filteredEntities as $io => $entity) {
                    if (!ispos($entity['ip'], $search)) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Month cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashmonth($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
                foreach ($this->filteredEntities as $io => $entity) {
                    $daysLeft = $this->fundsFlow->getOnlineLeftCountFast($entity['login']);
                    if ($daysLeft > 0) {
                        $curMonthDays = date("t");
                        $curDate = date("j");
                        $curMonthOffset = strtotime(date("Y-m-05"));
                        $nextMonthDays = date("t", strtotime(date('Y-m-d', strtotime('+1 month', $curMonthOffset))));
                        $expr = ($curMonthDays - $curDate) + $nextMonthDays;
                        if ($daysLeft > $expr) {
                            unset($this->filteredEntities[$entity['login']]);
                        }
                    } else {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Days less cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashdays($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                if (empty($this->fundsFlow)) {
                    $this->initFundsFlow();
                }
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
            }
        }
    }

    /**
     * Greater cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashgreater($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Cash'] < $param) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Lesser cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashlesser($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Cash'] > $param) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Lesser zero cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashlesszero($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Cash'] >= 0) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Zero cash filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercashzero($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Cash'] != 0) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Credit filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtercreditset($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Credit'] == 0) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Tags filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtertags($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if (!$this->checkInetTagId($entity['login'], $param)) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * AlwaysOnline filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filterao($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['AlwaysOnline'] != '1') {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Passive filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filterpassive($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Passive'] != '1') {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Not passive filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filternotpassive($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Passive'] != '0') {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * User activity filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filteractive($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
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
            }
        }
    }

    /**
     * Down filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filterdown($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($this->downUsers[$entity['login']] != '1') {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Tariff filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtertariff($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if ($entity['Tariff'] != $param) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Tariff contains filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtertariffcontain($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if (!ispos($entity['Tariff'], $param)) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Planned tariff change filter
     *
     * @param string $param
     *
     * @return void
     */
    protected function filtertariffnm($param) {
        if (!empty($param)) {
            if (!empty($this->filteredEntities)) {
                foreach ($this->filteredEntities as $io => $entity) {
                    if (empty($entity['TariffChange'])) {
                        unset($this->filteredEntities[$entity['login']]);
                    }
                }
            }
        }
    }

    /**
     * Returns actual bot hook state as array
     *
     * @return array
     */
    public function getActualHookInfo() {
        $result = array();
        if (!empty($this->token)) {
            $rawData = $this->telegram->getWebHookInfo();
            if (!empty($rawData)) {
                if (json_validate($rawData)) {
                    $result = json_decode($rawData, true);
                }
            }
        }
        return ($result);
    }

    /**
     * Checks if hook URL is valid
     *
     * @param string $hookUrl
     *
     * @return string
     */
    protected function isHookUrlValid($hookUrl) {
        $result = '';
        if (!empty($hookUrl)) {
            if (!strpos($hookUrl, 'https://') === 0) {
                $result = __('Only HTTPS allowed');
            }

            $urlHandle = new OmaeUrl($hookUrl);
            $urlHandle->setTimeout(10);
            $urlHandle->setUserAgent('Ubilling/ClapTrapMgr');
            $urlHandle->dataPost(ClapTrapBot::PROUTE_VALIDATE, 'true');
            $handlerReply = $urlHandle->response();
            if ($urlHandle->error() or $urlHandle->httpCode() != 200) {
                $result = __('Hook URL is not accepting requests') . ': ' . __('Connection error');
            } else {
                if (!ispos($handlerReply, ClapTrapBot::VALIDATION_RESULT)) {
                    $result = __('Not valid ClapTrapBot hook URL');
                }
            }
        } else {
            $result = __('Hook URL is empty');
        }
        return ($result);
    }

    /**
     * Renders install hook form
     *
     * @return string
     */
    public function renderInstallHookForm() {
        $result = '';
        if (!empty($this->token) and !empty($this->hookUrl)) {
            $newHookUrl = $this->hookUrl;
            $inputs = wf_TextInput(self::PROUTE_HOOK_URL, __('Hook URL'), $newHookUrl, false, 38, 'url') . ' ';
            $inputs .= wf_Submit(__('Install'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Token or hook URL is empty'), 'error');
            $result .= wf_BackLink(self::URL_ME);
        }
        return ($result);
    }

    /**
     * Installs hook for ClapTrapBot
     *
     * @param string $hookUrl
     *
     * @return string
     */
    public function installHook($hookUrl) {
        $result = '';
        if (!empty($this->token) and !empty($hookUrl)) {
            $validationError = $this->isHookUrlValid($hookUrl);
            if (empty($validationError)) {
                $allHookPids = rcms_scandir(ClapTrapBot::HOOK_PID_PATH, '*.hook');
                if (!empty($allHookPids)) {
                    foreach ($allHookPids as $eachHookPid) {
                        if (ispos($eachHookPid, 'ClapTrapBot')) {
                            unlink(ClapTrapBot::HOOK_PID_PATH . $eachHookPid);
                        }
                    }
                }
                $this->botInstance->installWebHook($hookUrl);
            } else {
                $result = $this->messages->getStyledMessage($validationError, 'error');
            }
        }
        return ($result);
    }

    /**
     * Renders actual bot hook state as readable table
     *
     * @param array $hookInfo
     *
     * @return string
     */
    public function renderHookInfo($hookInfo) {
        $result = '';
        if (!empty($hookInfo) and !empty($hookInfo['ok'])) {
            if (!empty($hookInfo['result'])) {
                $hData = $hookInfo['result'];
                if (!empty($hData['url'])) {
                    $cells = wf_TableCell(__('Hook URL'));
                    $cells .= wf_TableCell($hData['url']);
                    $rows = wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Has custom certificate'));
                    $cells .= wf_TableCell($hData['has_custom_certificate']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Pending update count'));
                    $cells .= wf_TableCell($hData['pending_update_count']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Last error date'));
                    $cells .= wf_TableCell(@$hData['last_error_date']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Last error message'));
                    $cells .= wf_TableCell(@$hData['last_error_message']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Max connections'));
                    $cells .= wf_TableCell($hData['max_connections']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('IP address'));
                    $cells .= wf_TableCell($hData['ip_address']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $result = wf_TableBody($rows, '100%', 0);
                } else {
                    $result = $this->messages->getStyledMessage(__('No web hook URL has been set up for this bot'), 'warning');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Empty hook info received'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Invalid hook info'), 'error');
        }
        return ($result);
    }

    /**
     * Renders auth data as users list with additional columns
     *
     * @return string
     */
    public function renderAuthData() {
        $result = '';
        $authData = $this->botAuth;
        if (empty($authData) and !empty($this->botInstance)) {
            $authData = $this->botInstance->getAuthDataAll();
        }
        if (!empty($authData)) {
            $userLogins = array();
            $extraColumns = array();
            $allChatIds = array();
            $allRegDates = array();
            $allTgActive = array();
            foreach ($authData as $eachLogin => $eachData) {
                $userLogins[] = $eachLogin;
                $allChatIds[$eachLogin] = $eachData['chatid'];
                $allRegDates[$eachLogin] = $eachData['date'];
                $allTgActive[$eachLogin] = web_bool_led($eachData['active']);
            }
            $extraColumns['Chat ID'] = $allChatIds;
            $extraColumns['Signup date'] = $allRegDates;
            $extraColumns['Telegram active'] = $allTgActive;
            $result .= web_UserArrayShower($userLogins, $extraColumns, true);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }
}
