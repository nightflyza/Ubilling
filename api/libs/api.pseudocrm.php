<?php

/**
 * It is definitely not CRM and does not even look like it
 */
class PseudoCRM {

    /**
     * 
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Leads database abstraction layer
     * 
     * @var object
     */
    protected $leadsDb = '';

    /**
     * Activities database abstraction layer
     * 
     * @var object
     */
    protected $activitiesDb = '';

    /**
     * Messages system helper placeholder
     * 
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all leads data as id=>leadData
     * 
     * @var array
     */
    protected $allLeads = array();

    /**
     * Contains all activities data as id=>activityData
     * 
     * @var array
     */
    protected $allActivities = array();

    /**
     * Contains all employee data as id=>name
     * 
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains all employee Telegram chatId as id=>chatid
     * 
     * @var array
     */
    protected $allEmployeeChatIds = array();

    /**
     * Contains all active employee data as id=>name
     * 
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Contains all employee administator logins as login=>employeeId
     * 
     * @var array
     */
    protected $allEmployeeLogins = array();

    /**
     * Contains branches data as id=>name
     * 
     * @var array
     */
    protected $allBranches = array();

    /**
     * Is branches enabled flag?
     * 
     * @var bool
     */
    protected $branchesFlag = false;

    /**
     * Contains available states stigma scopes for activities as SCOPE=>__(name)
     * 
     * @var array
     */
    protected $activitiesStatesList = array();

    /**
     * Contains all available tariff names as tariffName=>__(tariffName)
     * 
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all available users data as login=>userData
     * 
     * @var array
     */
    protected $allUserData = array();

    /**
     * Is senddog enabled flag?
     * 
     * @var bool
     */
    protected $sendDogEnabled = false;

    /**
     * Activities protection mechanics flag
     * 
     * @var bool
     */
    protected $activityProtectedFlag = false;

    /**
     * Contains current administrator login
     * 
     * @var string
     */
    protected $myLogin = '';

    /**
     * Some other predefined stuff
     */
    const RIGHT_VIEW = 'PSEUDOCRM';
    const RIGHT_LEADS = 'PSEUDOCRMLEADS';
    const RIGHT_ACTIVITIES = 'PSEUDOCRMACTS';
    const RIGHT_ACT_MANAGER = 'PSEUDOCRMACTMGR';
    const RIGHT_TASKS = 'TASKMAN';

    /**
     * database shortcuts
     */
    const TABLE_LEADS = 'crm_leads';
    const TABLE_ACTIVITIES = 'crm_activities';
    const TABLE_STATES_LOG = 'crm_stateslog';
    const OPT_ACT_CUSTSTATES = 'PSEUDOCRM_ACT_CUSTSTATES';
    const OPT_ACT_PROTECTED = 'PSEUDOCRM_ACT_PROTECTED';

    /**
     * routes here
     */
    const URL_ME = '?module=pseudocrm';
    const ROUTE_LEADS_LIST = 'leadslist';
    const ROUTE_LEADS_LIST_AJ = 'ajaxleadslist';
    const ROUTE_LEAD_PROFILE = 'showlead';
    const ROUTE_ACTIVITY_PROFILE = 'showactivity';
    const ROUTE_ACTIVITY_CREATE = 'createnewactivity';
    const ROUTE_LEAD_DETECT = 'username';
    const ROUTE_ACTIVITY_DONE = 'setactivitydone';
    const ROUTE_ACTIVITY_UNDONE = 'setactivityundone';
    const ROUTE_REPORT_SOURCES = 'reportleadsources';
    const ROUTE_REPORT_STATESLOG = 'reportstates';
    const ROUTE_REPORT_STATESLOG_AJ = 'ajaxtstatesreport';

    /**
     * post-routes
     */
    const PROUTE_LEAD_CREATE = 'leadcreatenew';
    const PROUTE_LEAD_SAVE = 'leadeditexisting';
    const PROUTE_LEAD_ASSIGN = 'assignlogintolead';
    const PROUTE_LEAD_ASSIGN_ID = 'leadidtoassign';
    const PROUTE_LEAD_ADDR = 'leadaddress';
    const PROUTE_LEAD_NAME = 'leadname';
    const PROUTE_LEAD_PHONE = 'leadphone';
    const PROUTE_LEAD_MOBILE = 'leadmobile';
    const PROUTE_LEAD_EXTMOBILE = 'leadextmobile';
    const PROUTE_LEAD_EMAIL = 'leademail';
    const PROUTE_LEAD_BRANCH = 'leadbranchid';
    const PROUTE_LEAD_TARIFF = 'leadtariff';
    const PROUTE_LEAD_LOGIN = 'leadlogin';
    const PROUTE_LEAD_EMPLOYEE = 'leademployee';
    const PROUTE_LEAD_NOTES = 'leadnotes';
    const PROUTE_ACTIVITY_EDIT = 'editactivityid';
    const PROUTE_ACTIVITY_NOTE = 'newactivitynote';

    /**
     * stigma lead/activity scopes here
     */
    const PHOTO_ACT_SCOPE = 'CRMACTIVITY';
    const STIGMA_LEAD_SOURCE = 'CRMSOURCE';
    const STIGMA_ACT_TYPE = 'CRMACTTYPE';
    const STIGMA_ACT_RESULT = 'CRMACTRESULT';
    const STIGMA_ACT_TARGET = 'CRMACTTARGET';
    const ADCOMM_ACT_SCOPE = 'ADCRMACTIVITY';

    /**
     * Creates new PseudoCRM instance
     */
    public function __construct() {
        $this->initMessages();
        $this->setMyLogin();
        $this->loadAlter();
        $this->setActivitiesStatesList();
        $this->setActivitiesCustomStates();
        $this->initLeadsDb();
        $this->initActivitiesDb();
        $this->loadEmployeeData();
        $this->loadUserData();
        $this->loadTariffs();
        $this->loadBranches();
        $this->loadLeads();
        $this->loadActivities();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads alter.ini config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->sendDogEnabled = $this->altCfg['SENDDOG_ENABLED'];
        $this->activityProtectedFlag = $this->altCfg['PSEUDOCRM_ACT_PROTECTED'];
    }

    /**
     * Sets current administrator username property
     * 
     * @return void
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Sets available activities states list. May be configurable in future.
     * 
     * @return void
     */
    protected function setActivitiesStatesList() {
        $this->activitiesStatesList = array(
            self::STIGMA_ACT_TYPE => __('Marketing type'),
            self::STIGMA_ACT_TARGET => __('Marketing target'),
            self::STIGMA_ACT_RESULT => __('Post-marketing status'),
        );
    }

    /**
     * Sets or overrides custom activities states list depends on config option
     * 
     * @return void
     */
    protected function setActivitiesCustomStates() {
        if (isset($this->altCfg[self::OPT_ACT_CUSTSTATES])) {
            if (!empty($this->altCfg[self::OPT_ACT_CUSTSTATES])) {
                $rawList = explode(',', $this->altCfg[self::OPT_ACT_CUSTSTATES]);
                if (!empty($rawList)) {
                    foreach ($rawList as $io => $each) {
                        if (ispos($each, ':')) {
                            $actStatesCustom = explode(':', $each);
                            //at least two required sections available
                            if (isset($actStatesCustom[0]) and isset($actStatesCustom[1])) {
                                $customScope = strtoupper($actStatesCustom[0]);
                                $customStateName = __($actStatesCustom[1]);
                                $this->activitiesStatesList[$customScope] = $customStateName;
                            }
                        } else {
                            show_error(__('Wrong element format') . ' `' . $each . '` IN ' . self::OPT_ACT_CUSTSTATES);
                        }
                    }
                }
            }
        }
    }

    /**
     * Loads all existing tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $allTariffsTmp = zb_TariffsGetAll();
        if (!empty($allTariffsTmp)) {
            foreach ($allTariffsTmp as $io => $each) {
                $this->allTariffs[$each['name']] = __($each['name']);
            }
        }
    }

    /**
     * Preloads branches data, if its enabled
     * 
     * @return void
     */
    protected function loadBranches() {
        if ($this->altCfg['BRANCHES_ENABLED']) {
            $this->branchesFlag = true;
            $branchesDb = new NyanORM('branches');
            $branchesDb->orderBy('id', 'DESC');
            $allBranchesTmp = $branchesDb->getAll();
            if (!empty($allBranchesTmp)) {
                foreach ($allBranchesTmp as $io => $each) {
                    $this->allBranches[$each['id']] = $each['name'];
                }
            }
        }
    }

    /**
     * Preloads all existing employee data
     * 
     * @return void
     */
    protected function loadEmployeeData() {
        $allEmployeeTmp = ts_GetAllEmployeeData();
        if (!empty($allEmployeeTmp)) {
            foreach ($allEmployeeTmp as $io => $each) {
                $this->allEmployee[$each['id']] = $each['name'];
                if (!empty($each['admlogin'])) {
                    $this->allEmployeeLogins[$each['admlogin']] = $each['id'];
                }
                if ($each['active']) {
                    $this->allActiveEmployee[$each['id']] = $each['name'];
                }
                if ($each['telegram']) {
                    $this->allEmployeeChatIds[$each['id']] = $each['telegram'];
                }
            }
        }
    }

    /**
     * Loads all existing users data
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Inits leads database abstraction layer
     * 
     * @return void
     */
    protected function initLeadsDb() {
        $this->leadsDb = new NyanORM(self::TABLE_LEADS);
    }

    /**
     * Loads existing leads into protected property
     * 
     * @return void
     */
    protected function loadLeads() {
        $this->allLeads = $this->leadsDb->getAll('id');
    }

    /**
     * Inits activities database abstraction layer
     * 
     * @return void
     */
    protected function initActivitiesDb() {
        $this->activitiesDb = new NyanORM(self::TABLE_ACTIVITIES);
    }

    /**
     * Loads existing leads into protected property
     * 
     * @return void
     */
    protected function loadActivities() {
        $this->activitiesDb->orderBy('id', 'DESC');
        $this->allActivities = $this->activitiesDb->getAll('id');
    }

    /**
     * Renders existing leads list
     * 
     * @return string
     */
    public function renderLeadsList() {
        $result = '';
        $columns = array('ID', 'Type', 'Full address', 'Real Name', 'Mobile', 'Worker', 'Notes', 'Actions');
        $url = self::URL_ME . '&' . self::ROUTE_LEADS_LIST_AJ . '=true';
        $customStyling = wf_tag('style');
        $customStyling .= file_get_contents('skins/pseudocrm.css');
        $customStyling .= wf_tag('style', true);
        $result .= $customStyling;
        $result .= wf_JqDtLoader($columns, $url, false, __('Leads'), 50, '"order": [[ 0, "desc" ]]');
        return ($result);
    }

    /**
     * Returns ajax data for existing leads list
     * 
     * @return void
     */
    public function ajLeadsList() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allLeads)) {
            foreach ($this->allLeads as $io => $each) {
                $leadType = (empty($each['login'])) ? __('Potential') : __('Existing');
                $leadProfileUrl = self::URL_ME . '&' . self::ROUTE_LEAD_PROFILE . '=' . $each['id'];
                $employeeName = (isset($this->allEmployee[$each['employeeid']])) ? $this->allEmployee[$each['employeeid']] : '';
                $data[] = $each['id'];
                $data[] = $leadType;
                $data[] = $each['address'];
                $data[] = $each['realname'];
                $data[] = $each['mobile'];
                $data[] = $employeeName;
                $data[] = $each['notes'];
                $actLinks = wf_Link($leadProfileUrl, web_edit_icon());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Returns new lead creation form
     * 
     * @return string
     */
    protected function renderLeadCreateForm() {
        $result = '';
        //previous, may be failed form submitted data
        $prevAddress = ubRouting::post(self::PROUTE_LEAD_ADDR);
        $prevName = ubRouting::post(self::PROUTE_LEAD_NAME);
        $prevMobile = ubRouting::post(self::PROUTE_LEAD_MOBILE);
        $prevExtMobile = ubRouting::post(self::PROUTE_LEAD_EXTMOBILE);
        $prevPhone = ubRouting::post(self::PROUTE_LEAD_PHONE);
        $prevEmail = ubRouting::post(self::PROUTE_LEAD_EMAIL);
        $prevBranch = ubRouting::post(self::PROUTE_LEAD_BRANCH);
        $prevTariff = ubRouting::post(self::PROUTE_LEAD_TARIFF);
        $prevLogin = ubRouting::post(self::PROUTE_LEAD_LOGIN);
        $prevEmployee = ubRouting::post(self::PROUTE_LEAD_EMPLOYEE);
        $prevNotes = ubRouting::post(self::PROUTE_LEAD_NOTES);

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = '';
        $inputs .= wf_HiddenInput(self::PROUTE_LEAD_CREATE, 'true');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_ADDR, __('Full address') . $sup, $prevAddress, true, '40', '');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_NAME, __('Real Name') . $sup, $prevName, true, '40', '');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_MOBILE, __('Mobile') . $sup, $prevMobile, true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_EXTMOBILE, __('Additional mobile'), $prevExtMobile, true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_PHONE, __('Phone'), $prevPhone, true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_EMAIL, __('Email'), $prevEmail, true, '15', 'email');
        if ($this->branchesFlag) {
            $branchesParams = array('' => '-');
            $branchesParams += $this->allBranches;
            $inputs .= wf_Selector(self::PROUTE_LEAD_BRANCH, $branchesParams, __('Branch'), $prevBranch, true);
        } else {
            $inputs .= wf_HiddenInput(self::PROUTE_LEAD_BRANCH, '0');
        }


        $tariffsParams = array('' => '-');
        $tariffsParams += $this->allTariffs;
        $inputs .= wf_Selector(self::PROUTE_LEAD_TARIFF, $tariffsParams, __('Tariff'), $prevTariff, true);
        $inputs .= wf_TextInput(self::PROUTE_LEAD_LOGIN, __('Login'), $prevLogin, true, '15', 'login');
        $employeeParams = array('' => '-');
        $employeeParams += $this->allActiveEmployee;
        $inputs .= wf_Selector(self::PROUTE_LEAD_EMPLOYEE, $employeeParams, __('Worker'), $prevEmployee, true);
        $inputs .= wf_TextInput(self::PROUTE_LEAD_NOTES, __('Notes') . $sup, $prevNotes, true, '40', '');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new lead in database
     * 
     * @param string $address
     * @param string $realname
     * @param string $phone
     * @param string $mobile
     * @param string $extmobile
     * @param string $email
     * @param int $branch
     * @param string $tariff
     * @param string $login
     * @param int $employeeid
     * @param string $notes
     * 
     * @return int
     */
    public function createLead($address, $realname, $phone, $mobile, $extmobile, $email, $branch, $tariff, $login, $employeeid, $notes) {
        $addressF = ubRouting::filters($address, 'mres');
        $realnameF = ubRouting::filters($realname, 'mres');
        $phoneF = ubRouting::filters($phone, 'mres');
        $mobileF = ubRouting::filters($mobile, 'mres');
        $extmobileF = ubRouting::filters($extmobile, 'mres');
        $emailF = ubRouting::filters($email, 'mres');
        $branchF = ubRouting::filters($branch, 'int');
        $tariffF = ubRouting::filters($tariff, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $employeeidF = ubRouting::filters($employeeid, 'int');
        $notesF = ubRouting::filters($notes, 'mres');

        $this->leadsDb->data('address', $addressF);
        $this->leadsDb->data('realname', $realnameF);
        $this->leadsDb->data('phone', $phoneF);
        $this->leadsDb->data('mobile', $mobileF);
        $this->leadsDb->data('extmobile', $extmobileF);
        $this->leadsDb->data('email', $emailF);
        $this->leadsDb->data('branch', $branchF);
        $this->leadsDb->data('tariff', $tariffF);
        $this->leadsDb->data('login', $loginF);
        $this->leadsDb->data('employeeid', $employeeidF);
        $this->leadsDb->data('notes', $notesF);

        $this->leadsDb->create();
        $newId = $this->leadsDb->getLastId();
        log_register('CRM CREATE LEAD [' . $newId . ']');
        return ($newId);
    }

    /**
     * Returns existing lead data
     * 
     * @param int $leadId
     * 
     * @return array
     */
    public function getLeadData($leadId) {
        $result = array();
        if (isset($this->allLeads[$leadId])) {
            $result = $this->allLeads[$leadId];
        }
        return ($result);
    }

    /**
     * Checks is lead exist or not by its ID
     * 
     * @param int $leadId
     * 
     * @return bool
     */
    public function isLeadExists($leadId) {
        $result = false;
        if (isset($this->allLeads[$leadId])) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns existing lead profile title
     * 
     * @param int $leadId
     * 
     * @return string
     */
    public function getLeadLabel($leadId) {
        $result = '';
        $leadData = $this->getLeadData($leadId);
        if (!empty($leadData)) {
            $result .= $leadData['address'] . ', ' . $leadData['realname'];
        }
        return ($result);
    }

    /**
     * Renders existing lead source controls
     * 
     * @param int $leadId
     * 
     * @return string
     */
    public function renderLeadSource($leadId) {
        $result = '';
        if ($this->isLeadExists($leadId)) {
            $leadSource = new Stigma(self::STIGMA_LEAD_SOURCE, $leadId);
            $readOnly = true;
            if (cfr(self::RIGHT_LEADS)) {
                $readOnly = false;
                $leadSource->stigmaController('CUSTOM:' . self::TABLE_STATES_LOG);
            }
            $result .= $leadSource->render($leadId, '54', $readOnly);
        }
        return ($result);
    }

    /**
     * Renders existing lead profile
     * 
     * @param int $leadId
     * 
     * @return string
     */
    public function renderLeadProfile($leadId) {
        $result = '';
        $leadId = ubRouting::filters($leadId, 'int');
        if ($this->isLeadExists($leadId)) {
            $leadData = $this->getLeadData($leadId);
            $rows = '';

            $cells = wf_TableCell(__('Type'), '30%', 'row2');
            $leadType = (empty($leadData['login'])) ? __('Potential') : __('Existing');
            $cells .= wf_TableCell($leadType);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Full address'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['address']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Real Name'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['realname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Phone'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['phone']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Mobile'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['mobile']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Additional mobile'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['extmobile']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Email'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['email']);
            $rows .= wf_TableRow($cells, 'row3');

            if ($this->branchesFlag) {
                $cells = wf_TableCell(__('Branch'), '30%', 'row2');
                $cells .= wf_TableCell(@$this->allBranches[$leadData['branch']]);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $cells = wf_TableCell(__('Tariff'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['tariff']);
            $rows .= wf_TableRow($cells, 'row3');

            $userLabel = '';
            if (!empty($leadData['login'])) {
                if (isset($this->allUserData[$leadData['login']])) {
                    $userData = $this->allUserData[$leadData['login']];
                    $userUrl = UserProfile::URL_PROFILE . $leadData['login'];
                    $userLabel = wf_Link($userUrl, wf_img_sized('skins/icon_user.gif', '', 10) . ' ' . $userData['fulladress'] . ', ' . $userData['realname']);
                }
            }

            $cells = wf_TableCell(__('Login'), '30%', 'row2');
            $cells .= wf_TableCell($userLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Worker'), '30%', 'row2');
            $cells .= wf_TableCell(@$this->allEmployee[$leadData['employeeid']]);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('Lead') . ' [' . $leadId . '] ' . __('Not exists'), 'error');
            $result .= wf_delimiter();
            $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_LEADS_LIST . '=true');
        }
        return ($result);
    }

    /**
     * Returns existing lead editing form
     * 
     * @param int $leadId
     * 
     * @return string
     */
    protected function renderLeadEditForm($leadId) {
        $leadId = ubRouting::filters($leadId, 'int');
        $result = '';
        $leadData = $this->getLeadData($leadId);
        if (!empty($leadData)) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = '';
            $inputs .= wf_HiddenInput(self::PROUTE_LEAD_SAVE, $leadId);
            $inputs .= wf_TextInput(self::PROUTE_LEAD_ADDR, __('Full address') . $sup, $leadData['address'], true, '40', '');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_NAME, __('Real Name') . $sup, $leadData['realname'], true, '40', '');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_MOBILE, __('Mobile') . $sup, $leadData['mobile'], true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_EXTMOBILE, __('Additional mobile'), $leadData['extmobile'], true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_PHONE, __('Phone'), $leadData['phone'], true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_EMAIL, __('Email'), $leadData['email'], true, '15', 'email');
            if ($this->branchesFlag) {
                $branchesParams = array('' => '-');
                $branchesParams += $this->allBranches;
                $inputs .= wf_Selector(self::PROUTE_LEAD_BRANCH, $branchesParams, __('Branch'), $leadData['branch'], true);
            } else {
                $inputs .= wf_HiddenInput(self::PROUTE_LEAD_BRANCH, $leadData['branch']);
            }


            $tariffsParams = array('' => '-');
            $tariffsParams += $this->allTariffs;
            $inputs .= wf_Selector(self::PROUTE_LEAD_TARIFF, $tariffsParams, __('Tariff'), $leadData['tariff'], true);
            $inputs .= wf_TextInput(self::PROUTE_LEAD_LOGIN, __('Login'), $leadData['login'], true, '15', 'login');
            $employeeParams = array('' => '-');
            $employeeParams += $this->allActiveEmployee;
            $inputs .= wf_Selector(self::PROUTE_LEAD_EMPLOYEE, $employeeParams, __('Worker'), $leadData['employeeid'], true);
            $inputs .= wf_TextInput(self::PROUTE_LEAD_NOTES, __('Notes') . $sup, $leadData['notes'], true, '40', '');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('Lead') . ' [' . $leadId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Changes existing lead database record
     * 
     * @param int $leadId
     * @param string $address
     * @param string $realname
     * @param string $phone
     * @param string $mobile
     * @param string $extmobile
     * @param string $email
     * @param int $branch
     * @param string $tariff
     * @param string $login
     * @param int $employeeid
     * @param string $notes
     * 
     * @return int
     */
    public function saveLead($leadId, $address, $realname, $phone, $mobile, $extmobile, $email, $branch, $tariff, $login, $employeeid, $notes) {
        $leadId = ubRouting::filters($leadId, 'int');
        $addressF = ubRouting::filters($address, 'mres');
        $realnameF = ubRouting::filters($realname, 'mres');
        $phoneF = ubRouting::filters($phone, 'mres');
        $mobileF = ubRouting::filters($mobile, 'mres');
        $extmobileF = ubRouting::filters($extmobile, 'mres');
        $emailF = ubRouting::filters($email, 'mres');
        $branchF = ubRouting::filters($branch, 'int');
        $tariffF = ubRouting::filters($tariff, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $employeeidF = ubRouting::filters($employeeid, 'int');
        $notesF = ubRouting::filters($notes, 'mres');

        $this->leadsDb->data('address', $addressF);
        $this->leadsDb->data('realname', $realnameF);
        $this->leadsDb->data('phone', $phoneF);
        $this->leadsDb->data('mobile', $mobileF);
        $this->leadsDb->data('extmobile', $extmobileF);
        $this->leadsDb->data('email', $emailF);
        $this->leadsDb->data('branch', $branchF);
        $this->leadsDb->data('tariff', $tariffF);
        $this->leadsDb->data('login', $loginF);
        $this->leadsDb->data('employeeid', $employeeidF);
        $this->leadsDb->data('notes', $notesF);

        $this->leadsDb->where('id', '=', $leadId);
        $this->leadsDb->save();
        log_register('CRM EDIT LEAD [' . $leadId . ']');

        return ($leadId);
    }

    /**
     * Renders new lead activity record creation dialog
     * 
     * @param int $leadId
     * 
     * @return string
     */
    protected function renderActivityCreateForm($leadId) {
        $result = '';
        if ($this->isLeadExists($leadId)) {
            $urlCreate = self::URL_ME . '&' . self::ROUTE_ACTIVITY_CREATE . '=' . $leadId;
            $urlCancel = self::URL_ME . '&' . self::ROUTE_LEAD_PROFILE . '=' . $leadId;
            $label = __('Are you realy want to create record for this lead') . '?';
            $result .= wf_ConfirmDialog($urlCreate, web_icon_create() . ' ' . __('Create new record'), $label, 'ubButton', $urlCancel, __('Create new record'));
        }
        return ($result);
    }

    /**
     * Creates new activity database record for existing lead
     * 
     * @param int $leadId
     * 
     * @return int/zero on error
     */
    public function createActivity($leadId) {
        $result = 0;
        $leadId = ubRouting::filters($leadId, 'int');
        if ($this->isLeadExists($leadId)) {
            $adminLogin = whoami();
            $currentEmployeeId = 0;
            if (isset($this->allEmployeeLogins[$adminLogin])) {
                $currentEmployeeId = $this->allEmployeeLogins[$adminLogin];
            }

            $this->activitiesDb->data('leadid', $leadId);
            $this->activitiesDb->data('date', curdatetime());
            $this->activitiesDb->data('admin', $adminLogin);
            $this->activitiesDb->data('employeeid', $currentEmployeeId);
            $this->activitiesDb->data('state', 0);
            $this->activitiesDb->data('notes', '');
            $this->activitiesDb->create();

            $newId = $this->activitiesDb->getLastId();
            $result = $newId;
            log_register('CRM CREATE ACTIVITY [' . $newId . '] FOR LEAD [' . $leadId . ']');
        }
        return ($result);
    }

    /**
     * Checks existence of activity by its ID
     * 
     * @param int $activityId
     * 
     * @return bool
     */
    public function isActivityExists($activityId) {
        $result = false;
        if (isset($this->allActivities[$activityId])) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns existing activity record data
     * 
     * @param int $activityId
     * 
     * @return array
     */
    public function getActivityData($activityId) {
        $result = array();
        if (isset($this->allActivities[$activityId])) {
            $result = $this->allActivities[$activityId];
        }
        return ($result);
    }

    /**
     * Render existing activity states controllers
     * 
     * @param int $activityId
     * @param int $size
     * 
     * @return string
     */
    protected function renderActivityStatesController($activityId, $size = 128) {
        $activityId = ubRouting::filters($activityId, 'int');
        $result = '';
        $readOnly = cfr(self::RIGHT_ACTIVITIES) ? false : true;
        $activityData = $this->getActivityData($activityId);
        //preventing state changes on closed activities
        if ($activityData['state']) {
            $readOnly = true;
        }
        $stigmaInstances = array();
        if (!empty($this->activitiesStatesList)) {
            foreach ($this->activitiesStatesList as $eachScope => $eachTitle) {
                //creating some instances
                $stigmaInstances[$eachScope] = new Stigma($eachScope, $activityId);
                //render state here
                $result .= wf_tag('strong', false) . __($eachTitle) . wf_tag('strong', true) . wf_delimiter(0);
                if (cfr(self::RIGHT_ACTIVITIES)) {
                    $stigmaInstances[$eachScope]->stigmaController('CUSTOM:' . self::TABLE_STATES_LOG);
                }
                $result .= $stigmaInstances[$eachScope]->render($activityId, $size, $readOnly);
            }
        }
        return ($result);
    }

    /**
     * Checks have user activity access rights to manage it open/closed states or not.
     * 
     * @param int $activityId
     * 
     * @return bool
     */
    protected function checkActivityAccess($activityId) {
        $result = false;
        //only if activity protection option enabled
        if ($this->activityProtectedFlag) {
            $activityId = ubRouting::filters($activityId, 'int');
            if (cfr(self::RIGHT_ACT_MANAGER)) {
                //user have total rights to manage all activities
                $result = true;
            } else {
                //checking some activity access
                if ($this->isActivityExists($activityId)) {
                    $activityData = $this->getActivityData($activityId);
                    $activityOwner = $activityData['admin'];
                    if ($activityOwner == $this->myLogin) {
                        //yep, thats is our activity!
                        $result = true;
                    }
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Sets existing activity database record as processed
     * 
     * @param int $activityId
     * 
     * @return void
     */
    public function setActivityDone($activityId) {
        $activityId = ubRouting::filters($activityId, 'int');
        if ($this->isActivityExists($activityId)) {
            if ($this->checkActivityAccess($activityId)) {
                $activityData = $this->getActivityData($activityId);
                $leadId = $activityData['leadid'];
                $this->activitiesDb->data('state', 1);
                $this->activitiesDb->where('id', '=', $activityId);
                $this->activitiesDb->save();
                log_register('CRM CLOSE ACTIVITY [' . $activityId . '] FOR LEAD [' . $leadId . ']');
            }
        }
    }

    /**
     * Sets existing activity database record as not processed
     * 
     * @param int $activityId
     * 
     * @return void
     */
    public function setActivityUndone($activityId) {
        $activityId = ubRouting::filters($activityId, 'int');
        if ($this->isActivityExists($activityId)) {
            if ($this->checkActivityAccess($activityId)) {
                $activityData = $this->getActivityData($activityId);
                $leadId = $activityData['leadid'];
                $this->activitiesDb->data('state', 0);
                $this->activitiesDb->where('id', '=', $activityId);
                $this->activitiesDb->save();
                log_register('CRM OPEN ACTIVITY [' . $activityId . '] FOR LEAD [' . $leadId . ']');
            }
        }
    }

    /**
     * Renders existing activity notes aka result editing form
     * 
     * @param int $activityId
     * 
     * @return string
     */
    protected function renderActivityResultEditForm($activityId) {
        $result = '';
        $activityId = ubRouting::filters($activityId, 'int');
        if ($this->isActivityExists($activityId)) {
            $activityData = $this->getActivityData($activityId);
            $currentNote = $activityData['notes'];
            $inputs = wf_HiddenInput(self::PROUTE_ACTIVITY_EDIT, $activityId);
            $inputs .= wf_TextInput(self::PROUTE_ACTIVITY_NOTE, __('Result'), $currentNote, false, 40) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, '');
        }
        return ($result);
    }

    /**
     * Changes activity record notes aka result
     * 
     * @param int $activityId
     * @param string $notes
     * 
     * @return void
     */
    public function setActivityResult($activityId, $notes = '') {
        $activityId = ubRouting::filters($activityId, 'int');
        $notes = ubRouting::filters($notes, 'mres');
        if ($this->isActivityExists($activityId)) {
            $activityData = $this->getActivityData($activityId);
            $leadId = $activityData['leadid'];
            $this->activitiesDb->data('notes', $notes);
            $this->activitiesDb->where('id', '=', $activityId);
            $this->activitiesDb->save();
            log_register('CRM CHANGE ACTIVITY [' . $activityId . '] RESULT FOR LEAD [' . $leadId . ']');
        }
    }

    /**
     * Renders existing activity record profile with state controllers
     * 
     * @param int $activityId
     * 
     * @return string
     */
    public function renderActivityProfile($activityId) {
        $result = '';
        $activityId = ubRouting::filters($activityId, 'int');
        if ($this->isActivityExists($activityId)) {
            $activityData = $this->getActivityData($activityId);
            $leadId = $activityData['leadid'];

            $readOnly = cfr(self::RIGHT_ACTIVITIES) ? false : true;

            //preventing state changes on closed activities
            if ($activityData['state']) {
                $readOnly = true;
            }



            //appending lead profile here
            $result .= $this->renderLeadProfile($leadId);

            //and some controls
            $leadBackLink = wf_BackLink(self::URL_ME . '&' . self::ROUTE_LEAD_PROFILE . '=' . $leadId) . ' ';
            $activityControls = $leadBackLink;
            if (cfr(self::RIGHT_ACTIVITIES)) {
                if ($this->checkActivityAccess($activityId)) {
                    if ($activityData['state']) {
                        $actOpenUrl = self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityId . '&' . self::ROUTE_ACTIVITY_UNDONE . '=' . $activityId;
                        $activityControls .= wf_Link($actOpenUrl, wf_img('skins/icon_unlock.png') . ' ' . __('Open'), false, 'ubButton') . ' ';
                    } else {
                        $actCloseUrl = self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityId . '&' . self::ROUTE_ACTIVITY_DONE . '=' . $activityId;
                        $activityControls .= wf_Link($actCloseUrl, wf_img('skins/icon_lock.png') . ' ' . __('Close'), false, 'ubButton') . ' ';
                    }
                }
            }

            $result .= $activityControls;

            //activity basic data
            $result .= wf_delimiter(0);

            $result .= wf_tag('div', false, 'dashtask');
            $result .= __('Date') . ': ' . $activityData['date'];
            $result .= wf_tag('div', true);

            $result .= wf_tag('div', false, 'dashtask');
            $result .= __('Worker') . ': ' . @$this->allEmployee[$activityData['employeeid']];
            $result .= wf_tag('div', true);

            $stateLabel = ($activityData['state']) ? __('Closed') : __('New');
            $result .= wf_tag('div', false, 'dashtask');
            $result .= __('Status') . ': ' . $stateLabel;
            $result .= wf_tag('div', true);

            //here result editing/display
            $result .= wf_tag('div', false, 'dashtask');
            if ($readOnly) {
                $result .= __('Result') . ': ' . $activityData['notes'];
            } else {
                $result .= $this->renderActivityResultEditForm($activityId);
            }
            $result .= wf_tag('div', true);

            $result .= wf_CleanDiv();
            //some state controllers here
            $result .= $this->renderActivityStatesController($activityId, 64);
            $result .= wf_delimiter(0);

            //photostorage here
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photostorage = new PhotoStorage(self::PHOTO_ACT_SCOPE, $activityId);
                $photostorageUrl = $photostorage::MODULE_URL . '&scope=' . self::PHOTO_ACT_SCOPE . '&itemid=' . $activityId . '&mode=list';
                $result .= wf_Link($photostorageUrl, wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
                $result .= wf_delimiter();
                $result .= $photostorage->renderImagesRaw();
            }

            //few additional comments here
            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                //catching notification for lead assidned employee
                $this->catchADcommentNotification($leadId, $activityData);
                //rendering adcomments
                $adComments = new ADcomments(self::ADCOMM_ACT_SCOPE);
                $result .= wf_tag('strong', false) . __('Additional comments') . wf_tag('strong', true) . wf_delimiter(0);
                $result .= $adComments->renderComments($activityId);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('Activity record') . ' [' . $activityId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Catches and sends notification to telegram, on new additional comment creation to lead assigned employee
     * 
     * @param int $leadId
     * @param array $activityData
     * 
     * @return void
     */
    protected function catchADcommentNotification($leadId = 0, $activityData = array()) {
        if ($this->sendDogEnabled) {
            //someone posting new additional comment
            if (ubRouting::checkPost(ADcomments::PROUTE_NEW_TEXT)) {
                $leadData = $this->getLeadData($leadId);
                if ($leadData) {
                    $leadEmployeeId = $leadData['employeeid'];
                    if (isset($this->allEmployeeChatIds[$leadEmployeeId])) {
                        $employeeChatId = $this->allEmployeeChatIds[$leadEmployeeId];
                        if ($employeeChatId) {
                            $telegram = new UbillingTelegram();
                            $billingUrl = ($this->altCfg['FULL_BILLING_URL']) ? $this->altCfg['FULL_BILLING_URL'] : '';
                            $message = __('New comment on lead') . ' ' . $this->getLeadLabel($leadId) . ' ';
                            $message .= __('for activity') . ' #' . $activityData['id'] . ' ' . __('from') . ' ' . $activityData['date'] . '. ';
                            $commentTextPreview = zb_cutString(ubRouting::post(ADcomments::PROUTE_NEW_TEXT), 40);
                            $message .= __('Comment') . ': "' . $commentTextPreview . '". ';
                            if ($billingUrl) {
                                $activityUrl = $billingUrl . self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityData['id'];
                                $message .= wf_Link($activityUrl, __('Show'));
                            }
                            $message .= 'parseMode:{html}';
                            $telegram->sendMessage($employeeChatId, $message, false, 'PSEUDOCRM');
                        }
                    }
                }
            }
        }
    }

    /**
     * Sends Telegram notification about open activities to activity employee
     * 
     * @return void
     */
    public function notifyOpenActivities() {
        if ($this->sendDogEnabled) {
            $telegram = new UbillingTelegram();
            $billingUrl = ($this->altCfg['FULL_BILLING_URL']) ? $this->altCfg['FULL_BILLING_URL'] : '';
            $activityBaseUrl = $billingUrl . self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=';
            $sendingQueue = array(); //employeeId=>activitiesList
            $eol = '\r\n';
            if (!empty($this->allActivities)) {
                foreach ($this->allActivities as $io => $each) {
                    //activity open?
                    if ($each['state'] == 0) {
                        $activityEmployeeId = $each['employeeid'];
                        $activityLink = '';
                        $activityLink = ' #' . $each['id'] . ' ' . __('from') . ' ' . $each['date'] . '. ';
                        if ($billingUrl) {
                            $activityLink .= wf_Link($activityBaseUrl . $each['id'], __('Show'));
                        }
                        if (isset($sendingQueue[$activityEmployeeId])) {
                            $sendingQueue[$activityEmployeeId] .= $activityLink . $eol;
                        } else {
                            $sendingQueue[$activityEmployeeId] = $activityLink . $eol;
                        }
                    }
                }

                if (!empty($sendingQueue)) {
                    foreach ($sendingQueue as $eachEmployeeId => $eachMessages) {
                        if (isset($this->allEmployeeChatIds[$eachEmployeeId])) {
                            $employeeChatId = $this->allEmployeeChatIds[$eachEmployeeId];
                            if (!empty($eachMessages)) {
                                $message = __('The following activities are open for you') . ':' . $eol;
                                $message .= $eachMessages;
                                $message .= ' parseMode:{html}';
                                $telegram->sendMessage($employeeChatId, $message, false, 'PSEUDOCRM');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns array of all lead previous activity records
     * 
     * @param int $leadId
     * 
     * @return array
     */
    protected function getLeadActivities($leadId) {
        $result = array();
        if (!empty($this->allActivities)) {
            foreach ($this->allActivities as $activityId => $eachActivityData) {
                if ($eachActivityData['leadid'] == $leadId) {
                    $result[$activityId] = $eachActivityData;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders previous lead activities list
     * 
     * @param int $leadId
     * @param bool $onlyLast
     * 
     * @return string
     */
    public function renderLeadActivitiesList($leadId, $onlyLast = false) {
        $result = '';
        $previousActivities = $this->getLeadActivities($leadId);
        if (!empty($previousActivities)) {
            if ($onlyLast) {
                $previousActivities = array_slice($previousActivities, 0, 1, true);
            }

            //performing stigma instances creation
            $stigmaInstances = array();
            if (!empty($this->activitiesStatesList)) {
                foreach ($this->activitiesStatesList as $eachScope => $eachTitle) {
                    //creating some instances
                    $stigmaInstances[$eachScope] = new Stigma($eachScope);
                }
            }

            $result .= wf_CleanDiv();
            foreach ($previousActivities as $activityId => $activityData) {
                $activityUrl = self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityId;
                $activityClass = ($activityData['state']) ? 'donetask' : 'undone';
                $employeeLabel = $activityData['admin'];
                if (isset($this->allEmployeeLogins[$activityData['admin']])) {
                    $employeeId = $this->allEmployeeLogins[$activityData['admin']];
                    $employeeLabel = $this->allEmployee[$employeeId];
                }
                $activityLabel = web_edit_icon() . ' ' . $activityData['date'] . ' - ' . $employeeLabel;

                //getting and appending each activity states
                if (!empty($stigmaInstances)) {
                    foreach ($stigmaInstances as $eachScope => $eachStigma) {
                        $activityLabel .= ' ' . $stigmaInstances[$eachScope]->textRender($activityId, ' ', 16);
                    }
                }

                //appending comment as result if not empty
                if (!empty($activityData['notes'])) {
                    $activityLabel .= ', ' . $activityData['notes'];
                } else {
                    $activityLabel .= ', ' . __('No result');
                }
                $result .= wf_tag('div', false, $activityClass, 'style="padding: 10px; margin: 10px;"');
                $result .= wf_Link($activityUrl, $activityLabel, false, '', 'style="color: #FFFFFF;"');
                $result .= wf_tag('div', true);
            }
        } else {
            if (!$onlyLast) {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        }
        return ($result);
    }

    /**
     * Returns lead task creation form 
     * 
     * @param int $leadId
     * 
     * @return string
     */
    public function renderLeadTaskCreateForm($leadId) {
        $result = '';
        $leadId = ubRouting::filters($leadId, 'int');
        if ($this->isLeadExists($leadId)) {
            $leadData = $this->getLeadData($leadId);
            $taskAddress = $leadData['address'];
            $taskMobile = $leadData['mobile'];
            $taskPhone = $leadData['phone'];
            $taskExtMobile = $leadData['extmobile'];
            if (!empty($taskExtMobile)) {
                $taskMobile .= ' ' . $taskExtMobile;
            }
            $taskLogin = $leadData['login'];
            $taskForm = ts_TaskCreateFormUnified($taskAddress, $taskMobile, $taskPhone, $taskLogin);
            $result .= wf_modal(wf_img('skins/createtask.gif') . ' ' . __('Create task'), __('Create task'), $taskForm, 'ubButton', '450', '540');
        }
        return ($result);
    }

    /**
     * Returns sticky note creation form
     * 
     * @return string
     */
    protected function renderStickyCreateForm($textPreset) {
        $inputs = wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
        $inputs .= wf_TextArea('newtext', '', $textPreset, true, '50x15');
        $inputs .= wf_CheckInput('newactive', __('Create note as active'), true, true);
        $inputs .= wf_DatePickerPreset('newreminddate', '');
        $inputs .= wf_tag('label') . __('Remind only after this date') . wf_tag('label', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_TimePickerPreset('newremindtime', '', __('Remind time'), false);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Create'));

        $result = wf_Form(StickyNotes::URL_ME, 'POST', $inputs, 'glamour');

        return ($result);
    }


    /**
     * Returns lead sticky note creation form control
     * 
     * @param int $leadId
     * 
     * @return string
     */
    protected function renderLeadStickyControl($leadId) {
        $result = '';
        $leadId = ubRouting::filters($leadId, 'int');
        if ($this->isLeadExists($leadId)) {
            $leadData = $this->getLeadData($leadId);
            $textPreset = '';
            $textPreset .= __('Lead') . ': ' . $leadData['address'] . PHP_EOL;
            $textPreset .= __('Real Name') . ': ' . $leadData['realname'] . PHP_EOL;
            if (!empty($leadData['mobile'])) {
                $textPreset .= __('Mobile') . ': ' . $leadData['mobile'] . PHP_EOL;
            }
            if (!empty($leadData['phone'])) {
                $textPreset .= __('Phone') . ': ' . $leadData['phone'] . PHP_EOL;
            }
            if (!empty($leadData['extmobile'])) {
                $textPreset .= __('Additional mobile') . ': ' . $leadData['extmobile'] . PHP_EOL;
            }
            $textPreset .= '======' . PHP_EOL;


            $stickyForm = $this->renderStickyCreateForm($textPreset);
            $result .= wf_modalAuto(wf_img('skins/pushpin.png') . ' ' . __('Create new personal note'), __('Create new personal note'), $stickyForm, 'ubButton');
        }
        return ($result);
    }

    /**
     * Searches lead Id by assigned login, returns 0 if not found.
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    public function searchLeadByLogin($userLogin) {
        $result = 0;
        if (!empty($this->allLeads)) {
            foreach ($this->allLeads as $io => $eachLead) {
                if ($eachLead['login'] == $userLogin) {
                    $result = $eachLead['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders leads sources basic report
     * 
     * @return string
     */
    public function renderReportLeadSources() {
        $result = '';
        $sources = new Stigma(self::STIGMA_LEAD_SOURCE);
        $result .= $sources->renderBasicReport();
        return ($result);
    }

    /**
     * Renders states report
     * 
     * @return string
     */
    public function renderReportStatesLog() {
        $result = '';
        $columns = array('Date', 'Worker', 'Status', 'Activity record', 'Event', 'Value');
        $opts = ' "order": [[ 0, "desc" ]]';
        $ajaxUrl = self::URL_ME . '&' . self::ROUTE_REPORT_STATESLOG_AJ . '=true';
        $result .= wf_JqDtLoader($columns, $ajaxUrl, false, __('Events'), 100, $opts);
        return ($result);
    }

    /**
     * Renders states log report ajax data
     * 
     * @return void
     */
    public function ajStatesLog() {
        $json = new wf_JqDtHelper();
        $statesLogDb = new NyanORM(self::TABLE_STATES_LOG);
        $statesLogDb->where('scope', '!=', self::STIGMA_LEAD_SOURCE);
        $statesLogDb->orderBy('id', 'DESC');
        $allStatesLog = $statesLogDb->getAll();

        if (!empty($allStatesLog)) {
            //preloading stigma isnstances
            $stigmaInstances = array();
            $allStatesNames = array();
            $allStatesIcons = array();
            if (!empty($this->activitiesStatesList)) {
                foreach ($this->activitiesStatesList as $eachScope => $scopeName) {
                    $stigmaInstances[$eachScope] = new Stigma($eachScope);
                    $allStatesNames[$eachScope] = $stigmaInstances[$eachScope]->getAllStates();
                    if (!empty($allStatesNames[$eachScope])) {
                        foreach ($allStatesNames[$eachScope] as $eachStateId => $eachStateName) {
                            $allStatesIcons[$eachScope][$eachStateId] = $stigmaInstances[$eachScope]->getStateIcon($eachStateId);
                        }
                    }
                }
            }

            foreach ($allStatesLog as $io => $each) {
                $data[] = $each['date'];
                $adminLabel = (isset($this->allEmployeeLogins[$each['admin']])) ? $this->allEmployee[$this->allEmployeeLogins[$each['admin']]] : $each['admin'];
                $data[] = $adminLabel;
                $scopeName = (isset($this->activitiesStatesList[$each['scope']])) ? __($this->activitiesStatesList[$each['scope']]) : __('Unknown');
                $data[] = $scopeName;
                $actityLink = wf_Link(self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $each['itemid'], $each['itemid']);
                $data[] = $actityLink;
                $data[] = __($each['action']);
                $stateName = $each['state'];
                $stateIcon = '';
                $stateIconCode = '';
                if (isset($allStatesNames[$each['scope']])) {
                    if (isset($allStatesNames[$each['scope']][$stateName])) {
                        $stateName = $allStatesNames[$each['scope']][$stateName];
                        $stateIcon = $allStatesIcons[$each['scope']][$each['state']];
                        if (!empty($stateIcon)) {
                            $stateIconCode = wf_img_sized($stateIcon, '', 10) . ' ';
                        }
                    }
                }
                $data[] = $stateIconCode . __($stateName);
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders lead assign form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderLeadAssignForm($login) {
        $result = '';
        if (isset($this->allUserData[$login])) {
            $inputs = wf_HiddenInput(self::PROUTE_LEAD_ASSIGN, $login);
            $availableLeadsParams = array('' => '-');
            if (!empty($this->allLeads)) {
                foreach ($this->allLeads as $io => $each) {
                    //lead have no user assigned yet
                    if (empty($each['login'])) {
                        $availableLeadsParams[$each['id']] = $each['address'] . ' ' . $each['realname'];
                    }
                }
            }

            $inputs .= wf_SelectorSearchable(self::PROUTE_LEAD_ASSIGN_ID, $availableLeadsParams, __('Lead'), '', false);
            $inputs .= wf_Submit(__('Assign'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('User not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders lead creation form 
     * 
     * @param string $login
     * 
     * @return string 
     */
    public function renderUserLeadCreationForm($login) {
        $result = '';
        if (isset($this->allUserData[$login])) {
            $userData = $this->allUserData[$login];

            //some prefilled user data here
            $prevAddress = $userData['fulladress'];
            $prevName = $userData['realname'];
            $prevMobile = $userData['mobile'];
            $prevExtMobile = '';
            if ($this->altCfg['MOBILES_EXT']) {
                $extMobiles = new MobilesExt();
                $userAdditionalMobiles = $extMobiles->getUserMobiles($login);
                if (!empty($userAdditionalMobiles)) {
                    foreach ($userAdditionalMobiles as $io => $each) {
                        $prevExtMobile = $each['mobile'];
                    }
                }
            }
            $prevPhone = $userData['phone'];
            $prevEmail = $userData['email'];
            $prevBranch = 0;
            if ($this->altCfg['BRANCHES_ENABLED']) {
                global $branchControl;
                $prevBranch = $branchControl->userGetBranch($login);
            }
            $prevTariff = $userData['Tariff'];

            $prevLogin = $login;
            $prevEmployee = '';
            $curAdmLogin = whoami();
            if (isset($this->allEmployeeLogins[$curAdmLogin])) {
                $prevEmployee = $this->allEmployeeLogins[$curAdmLogin];
            }

            $prevNotes = '';

            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = '';
            $inputs .= wf_HiddenInput(self::PROUTE_LEAD_CREATE, 'true');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_ADDR, __('Full address') . $sup, $prevAddress, true, '40', '');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_NAME, __('Real Name') . $sup, $prevName, true, '40', '');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_MOBILE, __('Mobile') . $sup, $prevMobile, true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_EXTMOBILE, __('Additional mobile'), $prevExtMobile, true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_PHONE, __('Phone'), $prevPhone, true, '15', 'mobile');
            $inputs .= wf_TextInput(self::PROUTE_LEAD_EMAIL, __('Email'), $prevEmail, true, '15', 'email');
            if ($this->branchesFlag) {
                $branchesParams = array('' => '-');
                $branchesParams += $this->allBranches;
                $inputs .= wf_Selector(self::PROUTE_LEAD_BRANCH, $branchesParams, __('Branch'), $prevBranch, true);
            } else {
                $inputs .= wf_HiddenInput(self::PROUTE_LEAD_BRANCH, '0');
            }


            $tariffsParams = array('' => '-');
            $tariffsParams += $this->allTariffs;
            $inputs .= wf_Selector(self::PROUTE_LEAD_TARIFF, $tariffsParams, __('Tariff'), $prevTariff, true);
            $inputs .= wf_HiddenInput(self::PROUTE_LEAD_LOGIN, $prevLogin);
            $employeeParams = array('' => '-');
            $employeeParams += $this->allActiveEmployee;
            $inputs .= wf_Selector(self::PROUTE_LEAD_EMPLOYEE, $employeeParams, __('Worker'), $prevEmployee, true);
            $inputs .= wf_TextInput(self::PROUTE_LEAD_NOTES, __('Notes') . $sup, $prevNotes, true, '40', '');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Create new lead'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('User not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Assigns some login to existing lead
     * 
     * @param int $leadId
     * @param string $login
     * 
     * @return void/string on error
     */
    public function setLeadLogin($leadId, $login) {
        $result = '';
        $leadId = ubRouting::filters($leadId, 'int');
        $loginF = ubRouting::filters($login, 'mres');
        if ($this->isLeadExists($leadId)) {
            if (isset($this->allUserData[$loginF])) {
                $this->leadsDb->data('login', $loginF);
                $this->leadsDb->where('id', '=', $leadId);
                $this->leadsDb->save();
                log_register('CRM LEAD [' . $leadId . '] ASSIGN (' . $login . ')');
            } else {
                $result .= __('Strange exception') . ': ' . __('User not exists') . ' (' . $login . ')';
            }
        } else {
            $result .= __('Strange exception') . ': ' . __('Lead') . ' [' . $leadId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Renders primary module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';

        if (ubRouting::checkGet(self::ROUTE_LEAD_PROFILE)) {
            $leadId = ubRouting::get(self::ROUTE_LEAD_PROFILE, 'int');
            $leadData = $this->getLeadData($leadId);
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_LEADS_LIST . '=true', wf_img('skins/ukv/users.png') . ' ' . __('Existing leads'), false, 'ubButton') . ' ';

            if (cfr(self::RIGHT_LEADS)) {
                $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit lead'), __('Edit lead'), $this->renderLeadEditForm($leadId), 'ubButton');
            }

            if (cfr(self::RIGHT_ACTIVITIES)) {
                $result .= $this->renderActivityCreateForm($leadId);
            }
            if (!empty($leadData)) {
                if ($leadData['login']) {
                    $result .= wf_Link(UserProfile::URL_PROFILE . $leadData['login'], web_profile_icon() . ' ' . __('User profile'), false, 'ubButton') . ' ';
                }
            }
            if (cfr(self::RIGHT_TASKS)) {
                $result .= $this->renderLeadTaskCreateForm($leadId);
            }

            if (cfr('STICKYNOTES')) {
                if ($this->altCfg['STICKY_NOTES_ENABLED']) {
                    $result .= $this->renderLeadStickyControl($leadId);
                }
            }
        }

        if (ubRouting::checkGet(self::ROUTE_LEADS_LIST)) {
            if (cfr(self::RIGHT_LEADS)) {
                $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new lead'), __('Create new lead'), $this->renderLeadCreateForm(), 'ubButton') . ' ';
            }
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_REPORT_SOURCES . '=true', wf_img('skins/icon_funnel16.png') . ' ' . __('Leads sources'), false, 'ubButton') . ' ';
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_REPORT_STATESLOG . '=true', wf_img('skins/icon_note.gif') . ' ' . __('States log'), false, 'ubButton') . ' ';
        }

        if (ubRouting::checkGet(self::ROUTE_REPORT_SOURCES)) {
            $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_LEADS_LIST . '=true') . ' ';
        }

        if (ubRouting::checkGet(self::ROUTE_REPORT_STATESLOG)) {
            $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_LEADS_LIST . '=true') . ' ';
        }


        if (ubRouting::checkGet(self::ROUTE_ACTIVITY_PROFILE)) {
            // ????
        }

        return ($result);
    }
}
