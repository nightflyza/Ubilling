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
     * Some other predefined stuff
     */
    const RIGHT_VIEW = 'PSEUDOCRM';
    const RIGHT_LEADS = 'PSEUDOCRMLEADS';
    const RIGHT_ACTIVITIES = 'PSEUDOCRMACTS';
    const RIGHT_TASKS = 'TASKMAN';

    /**
     * database shortcuts
     */
    const TABLE_LEADS = 'crm_leads';
    const TABLE_ACTIVITIES = 'crm_activities';
    const TABLE_STATES_LOG = 'crm_stateslog';

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

    /**
     * post-routes
     */
    const PROUTE_LEAD_CREATE = 'leadcreatenew';
    const PROUTE_LEAD_SAVE = 'leadeditexisting';
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

    /**
     * Creates new PseudoCRM instance
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setActivitiesStatesList();
        $this->initLeadsDb();
        $this->initActivitiesDb();
        $this->loadEmployeeData();
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
    }

    /**
     * Sets available activities states list
     * 
     * @return void
     */
    protected function setActivitiesStatesList() {
        $this->activitiesStatesList = array(
            self::STIGMA_ACT_TYPE => __('Marketing type'),
            self::STIGMA_ACT_RESULT => __('Post-marketing status'),
            self::STIGMA_ACT_TARGET => __('Marketing target'),
        );
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
            }
        }
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
        $columns = array('ID', 'Type', 'Full address', 'Real Name', 'Mobile', 'Notes', 'Actions');
        $url = self::URL_ME . '&' . self::ROUTE_LEADS_LIST_AJ . '=true';
        $customStyling = wf_tag('style');
        $customStyling .= file_get_contents('skins/pseudocrm.css');
        $customStyling .= wf_tag('style', true);
        $result .= $customStyling;
        $result .= wf_JqDtLoader($columns, $url, false, __('Leads'), 50, '"order": [[ 0, "desc" ]]');
        return($result);
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
                $data[] = $each['id'];
                $data[] = $leadType;
                $data[] = $each['address'];
                $data[] = $each['realname'];
                $data[] = $each['mobile'];
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

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = '';
        $inputs .= wf_HiddenInput(self::PROUTE_LEAD_CREATE, 'true');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_ADDR, __('Full address') . $sup, '', true, '40', '');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_NAME, __('Real Name') . $sup, '', true, '40', '');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_MOBILE, __('Mobile') . $sup, '', true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_EXTMOBILE, __('Additional mobile'), '', true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_PHONE, __('Phone'), '', true, '15', 'mobile');
        $inputs .= wf_TextInput(self::PROUTE_LEAD_EMAIL, __('Email'), '', true, '15', 'email');
        if ($this->branchesFlag) {
            $branchesParams = array('' => '-');
            $branchesParams += $this->allBranches;
            $inputs .= wf_Selector(self::PROUTE_LEAD_BRANCH, $branchesParams, __('Branch'), '', true);
        } else {
            $inputs .= wf_HiddenInput(self::PROUTE_LEAD_BRANCH, '0');
        }


        $tariffsParams = array('' => '-');
        $tariffsParams += $this->allTariffs;
        $inputs .= wf_Selector(self::PROUTE_LEAD_TARIFF, $tariffsParams, __('Tariff'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_LEAD_LOGIN, __('Login'), '', true, '15', 'login');
        $employeeParams = array('' => '-');
        $employeeParams += $this->allActiveEmployee;
        $inputs .= wf_Selector(self::PROUTE_LEAD_EMPLOYEE, $employeeParams, __('Worker'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_LEAD_NOTES, __('Notes') . $sup, '', true, '40', '');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
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
        return($newId);
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
        return($result);
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
        return($result);
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
        return($result);
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
        return($result);
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

            $cells = wf_TableCell(__('Login'), '30%', 'row2');
            $cells .= wf_TableCell($leadData['login']);
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
        return($result);
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
        return($result);
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

        return($leadId);
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
        return($result);
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
        return($result);
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
        return($result);
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
        return($result);
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
        return($result);
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
            $activityData = $this->getActivityData($activityId);
            $leadId = $activityData['leadid'];
            $this->activitiesDb->data('state', 1);
            $this->activitiesDb->where('id', '=', $activityId);
            $this->activitiesDb->save();
            log_register('CRM CLOSE ACTIVITY [' . $activityId . '] FOR LEAD [' . $leadId . ']');
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
            $activityData = $this->getActivityData($activityId);
            $leadId = $activityData['leadid'];
            $this->activitiesDb->data('state', 0);
            $this->activitiesDb->where('id', '=', $activityId);
            $this->activitiesDb->save();
            log_register('CRM OPEN ACTIVITY [' . $activityId . '] FOR LEAD [' . $leadId . ']');
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
        return($result);
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
                if ($activityData['state']) {
                    $actOpenUrl = self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityId . '&' . self::ROUTE_ACTIVITY_UNDONE . '=' . $activityId;
                    $activityControls .= wf_Link($actOpenUrl, wf_img('skins/icon_unlock.png') . ' ' . __('Open'), false, 'ubButton') . ' ';
                } else {
                    $actCloseUrl = self::URL_ME . '&' . self::ROUTE_ACTIVITY_PROFILE . '=' . $activityId . '&' . self::ROUTE_ACTIVITY_DONE . '=' . $activityId;
                    $activityControls .= wf_Link($actCloseUrl, wf_img('skins/icon_lock.png') . ' ' . __('Close'), false, 'ubButton') . ' ';
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

            $stateLabel = ($activityData['state']) ? __('New') : __('Closed');
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
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exception') . ': ' . __('Activity record') . ' [' . $activityId . '] ' . __('Not exists'), 'error');
        }
        return($result);
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
        return($result);
    }

    /**
     * Renders previous lead activities list
     * 
     * @param int $leadId
     * 
     * @return string
     */
    public function renderLeadActivitiesList($leadId) {
        $result = '';
        $previousActivities = $this->getLeadActivities($leadId);
        if (!empty($previousActivities)) {
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
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
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
        return($result);
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
        return($result);
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
        } else {
            if (ubRouting::checkGet(self::ROUTE_LEADS_LIST)) {
                if (cfr(self::RIGHT_LEADS)) {
                    $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new lead'), __('Create new lead'), $this->renderLeadCreateForm(), 'ubButton') . ' ';
                }
            }
        }


        if (ubRouting::checkGet(self::ROUTE_ACTIVITY_PROFILE)) {
            // ????
        }

        return($result);
    }
}
