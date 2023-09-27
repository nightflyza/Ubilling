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
     * Some other predefined stuff
     */
    const RIGHT_VIEW = 'PSEUDOCRM';
    const RIGHT_LEADS = 'PSEUDOCRMLEADS';
    const RIGHT_ACTIVITIES = 'PSEUDOCRMACTS';

    /**
     * database shortcuts
     */
    const TABLE_LEADS = 'crm_leads';
    const TABLE_ACTIVITIES = 'crm_activities';

    /**
     * routes here
     */
    const URL_ME = '?module=pseudocrm';
    const ROUTE_LEADS_LIST = 'leadslist';
    const ROUTE_LEADS_LIST_AJ = 'ajaxleadslist';
    const ROUTE_LEAD_PROFILE = 'showlead';

    /**
     * post-routes
     */
    const PROUTE_LEAD_CREATE = 'leadcreatenew';
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

    /**
     * Creates new PseudoCRM instance
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->initLeadsDb();
        $this->loadEmployeeData();
        $this->loadBranches();
        $this->loadLeads();
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
     * Renders existing leads list
     * 
     * @return string
     */
    public function renderLeadsList() {
        $result = '';
        $columns = array('Full address', 'Real Name', 'Phone', 'Mobile', 'Actions');
        $url = self::URL_ME . '&' . self::ROUTE_LEADS_LIST_AJ . '=true';
        $result .= wf_JqDtLoader($columns, $url, false, __('Leads'), 100, '"order": [[ 0, "desc" ]]');
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
                $data[] = $each['address'];
                $data[] = $each['realname'];
                $data[] = $each['phone'];
                $data[] = $each['mobile'];
                $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_LEAD_PROFILE . '=' . $each['if'], web_edit_icon());
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
        /**
         *  
          `address` VARCHAR(255) NOT NULL,
          `realname` VARCHAR(255) NOT NULL,
          `phone` VARCHAR(32) DEFAULT NULL,
          `mobile` VARCHAR(32) NOT NULL,
          `extmobile` VARCHAR(32) DEFAULT NULL,
          `email` VARCHAR(64) DEFAULT NULL,
          `branch` INT(11) DEFAULT NULL,
          `tariff` VARCHAR(64) DEFAULT NULL,
          `login` VARCHAR(64) DEFAULT NULL,
          `employeeid` INT(11) DEFAULT NULL,
          `notes` VARCHAR(255) DEFAULT NULL,
         */
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
        }

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders primary module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        if (cfr(self::RIGHT_LEADS)) {
            $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new lead'), __('Create new lead'), $this->renderLeadCreateForm(), 'ubButton');
        }
        return($result);
    }
}
