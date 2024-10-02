<?php

/**
 * Some goose doin some resistance
 */
class GRes {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available userdata as login=>userData
     *
     * @var array
     */
    protected $allUserData = array();
    /**
     * Contains all available agents data as id=>agentData
     *
     * @var array
     */
    protected $allAgents = array();

    /**
     * Contains all available agents extended data as agentId=>[extDataArr]
     *
     * @var array
     */
    protected $allAgentsExtInfo = array();

    /**
     * Contains preprocessed all agents data as agentId=>[data]+[exinfo]+[split]
     *
     * @var array
     */
    protected $fullAgentData = array();

    /**
     * Contains all existing agents names as id=>name
     *
     * @var array
     */
    protected $allAgentNames = array();

    /**
     * Contains all address based assigns as agentId=>street
     *
     * @var array
     */
    protected $allAssigns = array();
    /**
     * Contains all available strict assigns as login=>agentId
     *
     * @var array
     */
    protected $allAssignsStrict = array();

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Avarice instance placeholder
     *
     * @var object
     */
    protected $avarice = '';

    /**
     * Strategy database abstraction layer
     *
     * @var object
     */
    protected $strategyDb = '';

    /**
     * Strategy specs database abstraction layer
     *
     * @var object
     */
    protected $specsDb = '';

    /**
     * Contains existing strategies full data as stratId=>data/specs
     *
     * @var array
     */
    protected $allStrategies = array();

    /**
     * Contains all existing strat specs as id=>data
     *
     * @var array
     */
    protected $allSpecs = array();

    /**
     * Contains all strategies with enabled use assigns flag and theis primary agents as stratId=>agentId
     *
     * @var array
     */
    protected $allPrimaryAgents = array();

    /**
     * Contains available strategy spec types
     *
     * @var array
     */
    protected $specTypes = array();

    /**
     * Contains money ammount to apply strategy
     *
     * @var float
     */
    protected $amount = 0;

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * OpenPayz object placeholder
     *
     * @var object
     */
    protected $openPayz = '';

    /**
     * Contains all PaymentIds as paymentId=>userLogin
     *
     * @var array
     */
    protected $allPaymentIds = array();

    /**
     * Contains all users paymentIds as userLogin=>paymentId
     *
     * @var array
     */
    protected $allCustomerPaymentIds = array();

    /**
     * Contains current instance runtime which will be returned with every reply
     *
     * @var string
     */
    protected $runtime = '';

    /**
     * some predefined stuff here
     */
    const TABLE_STRATEGY = 'gr_strat';
    const TABLE_SPECS = 'gr_spec';
    const URL_ME = '?module=gooseresistance';

    const ROUTE_ST_DELETE = 'deletestrategyid';
    const PROUTE_ST_CREATE = 'createnewstrategy';
    const PROUTE_ST_EDIT = 'editstrategyid';
    const PROUTE_ST_NAME = 'strategyname';
    const PROUTE_ST_ASSIGNS = 'strategyassignsflag';
    const PROUTE_ST_AGENTID = 'strategyprimaryagentid';

    const ROUTE_SP_DELETE = 'deletespecid';
    const ROUTE_SP_EDIT = 'editstrateryspecs';
    const ROUTE_SP_CUSTDATA = 'speccustdataedit';
    const PROUTE_SP_CREATE = 'createnewspec';
    const PROUTE_SP_EDIT = 'editspecid';
    const PROUTE_SP_STRAT = 'specstratid';
    const PROUTE_SP_AGENT = 'specagentid';
    const PROUTE_SP_TYPE = 'spectype';
    const PROUTE_SP_VALUE = 'specvalue';

    const ROUTE_CD_DELKEY = 'delcustdatakey';
    const PROUTE_CD_SPEC = 'newcustomdataspecid';
    const PROUTE_CD_KEY = 'newcustomdatakey';
    const PROUTE_CD_VAL = 'newcustomdatavalue';

    const PROUTE_CH_USER = 'runcheckuser';
    const PROUTE_CH_AMOUNT = 'runcheckamount';
    const PROUTE_CH_STRAT = 'runcheckstratid';


    // ⠸⣿⣦⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⡠⠔⠒⠒⠒⢤⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠙⠻⣿⣷⣦⣀⠀⠀⠀⢀⣾⣷⠀⠘⠀⠀⠀⠙⢆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠉⠛⠙⢏⢩⣶⣿⣿⠿⠖⠒⠤⣄⠀⠀⠈⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠋⢅⡈⠐⠠⢀⠈⢆⠀⠀⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠐⠠⢀⠩⠀⢸⠀⠀⢸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⣿⣹⠆⣿⣉⢀⡟⡄⣰⠉⠂⢸⣏⠁⠀⠀⠀⡌⠀⠀⠸⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠛⠀⠀⠓⠒⠘⠉⠛⠘⠒⠃⠘⠒⠂⠀⠀⢰⠁⠀⠀⠀⠑⢤⣀⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⢦⢠⡄⡄⢠⣦⠀⣔⠢⠀⠀⠀⠀⡠⠃⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠙⠒⠒⠤⢄⣀⠤⠔⠒⡄
    // ⠀⠀⠀⠸⠏⠳⠃⠟⠺⠆⠬⠽⠀⠀⠀⢰⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡇
    // ⠀⣄⢀⡀⣠⠀⢠⡀⣠⢠⡀⠀⣠⢀⡀⢸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡼⠀
    // ⠀⡏⢿⡇⣿⣒⠈⣧⡇⢸⣒⡂⣿⢺⡁⠀⢧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡤⠊⠀⠀
    // ⠀⠀⠈⠀⠀⠀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⢧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡸⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⣼⣳⠀⡟⣼⠀⠀⠀⠀⠀⠀⠀⠈⢆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⠇⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠃⠈⠃⠃⠘⠀⠀⠀⠀⠀⠀⠀⠀⠈⢆⣀⣀⣀⡀⠀⠀⠀⠀⠀⠀⠀⢀⠎⠀⠀⠀⠀⠀
    // ⡖⢲⡄⣶⣲⡆⢲⠒⣶⢀⡖⢲⠀⡶⡄⡆⠀⠀⠀⠀⣿⠁⠀⠈⠑⠢⣄⠀⠀⠀⢠⠎⠀⠀⠀⠀⠀⠀
    // ⠳⠼⠃⠿⠀⠀⠸⠀⠿⠈⠣⠞⠀⠇⠹⠇⠀⠀⠀⢸⣿⠀⠀⠀⠀⠀⠀⠙⣢⡴⠁⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠠⣶⣶⣶⣾⣿⡿⠀⠀⠀⠀⠀⠀⠀⣿⠇⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠛⠛⠿⠛⠉⠀⠀⠀⠀⠀⠀⠀⢀⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⣤⣴⣶⣿⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠻⠛⠉⠻⠀⠀⠀⠀⠀⠀⠀⠀⠀

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->setSpecTypes();
        $this->initDb();
        $this->loadStrategies();
        $this->loadUserData();
        $this->initOpayz();
        $this->loadAgents();
        $this->loadAssigns();
        $this->loadAgentsExtInfo();
        $this->preprocessAgentData();
    }

    /**
     * Inits system message helper for further usage
     * 
     *  @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads some required configs
     *
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets default strategy spec types
     *
     * @return void
     */
    protected function setSpecTypes() {
        $this->specTypes = array(
            'none' => __('None'),
            'percent' => __('Percent of sum'),
            'percentleft' => __('Percent of remains'),
            'absolute' => __('Absolute'),
            'leftovers' => __('Leftovers')
        );
    }

    /**
     * Inits required database layers
     *
     * @return void
     */
    protected function initDb() {
        $this->strategyDb = new NyanORM(self::TABLE_STRATEGY);
        $this->specsDb = new NyanORM(self::TABLE_SPECS);
    }

    /**
     * Preloads all existing strategies from database
     *
     * @return void
     */
    protected function loadStrategies() {
        $this->allStrategies = $this->strategyDb->getAll('id');
        if (!empty($this->allStrategies)) {
            foreach ($this->allStrategies  as $io => $each) {
                $this->allStrategies[$each['id']]['specs'] = array();
                if ($each['useassigns'] and $each['primaryagentid']) {
                    $this->allPrimaryAgents[$each['id']] = $each['primaryagentid'];
                }
            }
        }

        $this->allSpecs = $this->specsDb->getAll('id');
        if (!empty($this->allSpecs)) {
            foreach ($this->allSpecs as $io => $each) {
                if (isset($this->allStrategies[$each['stratid']])) {
                    $customData = array();
                    if (!empty($each['customdata'])) {
                        $customData = json_decode($each['customdata'], true);
                    }
                    $each['customdata'] = $customData;
                    $this->allSpecs[$each['id']]['customdata'] = $customData;
                    $this->allStrategies[$each['stratid']]['specs'][$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Loads existing agents data from database
     *
     * @return void
     */
    protected function loadAgents() {
        $this->allAgents = zb_ContrAhentGetAllDataAssoc();
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
     * Preloads all address based and strict assigns into priavate props
     *
     * @return void
     */
    protected function loadAssigns() {
        $this->allAssigns = zb_AgentAssignGetAllData();
        $this->allAssignsStrict = zb_AgentAssignStrictGetAllData();
    }

    /**
     * Loads extended agents infor from database
     *
     * @return void
     */
    protected function loadAgentsExtInfo() {
        if (@$this->altCfg['AGENTS_EXTINFO_ON']) {
            $extInfTmp = zb_GetAgentExtInfo('', '', '', '', '');
            if (!empty($extInfTmp)) {
                foreach ($extInfTmp as $io => $each) {
                    $this->allAgentsExtInfo[$each['agentid']][] = $each;
                }
            }
        }
    }

    /**
     * Inits OpenPayz instance and preloads some paymentId=>login mappings
     *
     * @return void
     */
    protected function initOpayz() {
        $this->openPayz = new OpenPayz(false, true);
        $this->allPaymentIds = $this->openPayz->getCustomers();
        $this->allCustomerPaymentIds = $this->openPayz->getCustomersPaymentIds();
    }

    /**
     * Preprocesses all existing agents and extinfo data in some protected prop
     *
     * @return void
     */
    protected function preprocessAgentData() {
        if (!empty($this->allAgents)) {
            foreach ($this->allAgents as $eachAgentId => $eachAgentData) {
                $this->allAgentNames[$eachAgentId] = $eachAgentData['contrname'];
                $this->fullAgentData[$eachAgentId]['data'] = $eachAgentData;
                $this->fullAgentData[$eachAgentId]['split'] = array();
                if (isset($this->allAgentsExtInfo[$eachAgentId])) {
                    $this->fullAgentData[$eachAgentId]['extinfo'] = $this->allAgentsExtInfo[$eachAgentId];
                } else {
                    $this->fullAgentData[$eachAgentId]['extinfo'] = array();
                }
            }
        }
    }

    /**
     * Sets current instance amount property
     *
     * @param float $amount
     * 
     * @return void
     */
    public function setAmount($amount = 0) {
        $this->amount = $amount;
    }

    /**
     * Sets the user login after applying necessary filters.
     *
     * @param string $userLogin The user login to be set. Default is an empty string.
     * 
     * @return void
     */
    public function setUserLogin($userLogin = '') {
        $this->userLogin = ubRouting::filters($userLogin, 'login');
    }

    /**
     * Sets current instance runtime ID
     *
     * @param string $runtime
     * 
     * @return void
     */
    public function setRuntime($runtime = '') {
        $this->runtime = ubRouting::filters($runtime, 'vf');
    }

    /**
     * Returns some runtime array
     *
     * @param string $name
     * 
     * @return array
     */
    protected function getRuntime($name) {
        if (empty($this->avarice)) {
            $this->avarice = new Avarice();
        }
        $result = $this->avarice->runtime($name);
        return ($result);
    }

    /**
     * Returns some existing user assigned agent data
     *
     * @param string $userLogin
     * 
     * @return array
     */
    public function getUserAssignedAgentData($userLogin) {
        $result = array();
        if (isset($this->allUserData[$userLogin])) {
            $userData = $this->allUserData[$userLogin];
            $userAddress = $userData['cityname'] . ' ' . $userData['streetname'] . ' ' . $userData['buildnum'] . '/' . $userData['apt'];
        } else {
            $userAddress = '';
        }
        $assignedAgentId = zb_AgentAssignCheckLoginFast($userLogin, $this->allAssigns, $userAddress, $this->allAssignsStrict);
        if (isset($this->allAgents[$assignedAgentId])) {
            $result = $this->allAgents[$assignedAgentId];
        }
        return ($result);
    }

    /**
     * Renders the form for creating a new strategy.
     *
     * @return string
     */
    public function renderStrategyCreateForm() {
        $result = '';
        $agentParams = array(0 => __('No'));
        $agentParams += $this->allAgentNames;
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_HiddenInput(self::PROUTE_ST_CREATE, 'true');
        $inputs .= wf_TextInput(self::PROUTE_ST_NAME, __('Name') . $sup, '', true, 20);
        $inputs .= wf_CheckInput(self::PROUTE_ST_ASSIGNS, __('Use address based assigns'), true, false);
        $inputs .= wf_Selector(self::PROUTE_ST_AGENTID, $agentParams, __('Primary agent'), '', true);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new strategy database record
     *
     * @param string $name
     * @param bool $assigns
     * @param int $primaryAgentId
     * 
     * @return void
     */
    public function createStrategy($name, $assigns = false, $primaryAgentId = 0) {
        $nameF = ubRouting::filters($name, 'safe');
        $assigns = ($assigns) ? 1 : 0;
        $primaryAgentId = ubRouting::filters($primaryAgentId, 'int');
        $this->strategyDb->data('name', $nameF);
        $this->strategyDb->data('useassigns', $assigns);
        $this->strategyDb->data('primaryagentid', $primaryAgentId);
        $this->strategyDb->create();
        $newId = $this->strategyDb->getLastId();
        log_register('GOOSE STRAT CREATE [' . $newId . '] `' . $name . '`');
    }

    /**
     * Save or update an existing strategy in the database.
     *
     * @param int $stratId The ID of the strategy to save or update.
     * @param string $name The name of the strategy.
     * @param bool $assigns Whether the strategy uses address assignments. Defaults to false.
     * @param int $primaryAgentId The ID of the primary agent. Defaults to 0.
     * 
     * @return void
     */
    public function saveStrategy($stratId, $name, $assigns = false, $primaryAgentId = 0) {
        $stratId = ubRouting::filters($stratId, 'int');
        $nameF = ubRouting::filters($name, 'safe');
        $assigns = ($assigns) ? 1 : 0;
        $primaryAgentId = ubRouting::filters($primaryAgentId, 'int');
        if (isset($this->allStrategies[$stratId])) {
            $this->strategyDb->where('id', '=', $stratId);
            $this->strategyDb->data('name', $nameF);
            $this->strategyDb->data('useassigns', $assigns);
            $this->strategyDb->data('primaryagentid', $primaryAgentId);
            $this->strategyDb->save();
            log_register('GOOSE STRAT EDIT [' . $stratId . '] `' . $name . '`');
        }
    }

    /**
     * Deletes a strategy from the database.
     *
     * @param int $stratId The ID of the strategy to be deleted.
     * 
     * @return void
     */
    public function deleteStrategy($stratId) {
        $stratId = ubRouting::filters($stratId, 'int');
        if (isset($this->allStrategies[$stratId])) {
            $this->strategyDb->where('id', '=', $stratId);
            $this->strategyDb->delete();
            log_register('GOOSE STRAT DELETE [' . $stratId . ']');
            $this->flushStrategySpecs($stratId);
        }
    }


    /**
     * Flushed all strategy specs on strategy deletion
     *
     * @param int $stratId
     * 
     * @return void
     */
    protected function flushStrategySpecs($stratId) {
        $stratId = ubRouting::filters($stratId, 'int');
        $this->specsDb->where('stratid', '=', $stratId);
        $this->specsDb->delete();
        log_register('GOOSE STRAT [' . $stratId . '] FLUSH SPECS');
    }

    /**
     * Renders the form for editing existing strategy.
     *
     * @return string
     */
    public function renderStrategyEditForm($stratId) {
        $result = '';
        $stratId = ubRouting::filters($stratId, 'int');
        $agentParams = array(0 => __('No'));
        $agentParams += $this->allAgentNames;

        if (isset($this->allStrategies[$stratId])) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $stratData = $this->allStrategies[$stratId];
            $assignsFlag = ($stratData['useassigns']) ? true : false;
            $inputs = wf_HiddenInput(self::PROUTE_ST_EDIT, $stratId);
            $inputs .= wf_TextInput(self::PROUTE_ST_NAME, __('Name') . $sup, $stratData['name'], true, 20);
            $inputs .= wf_CheckInput(self::PROUTE_ST_ASSIGNS, __('Use address based assigns'), true, $assignsFlag);
            $inputs .= wf_Selector(self::PROUTE_ST_AGENTID, $agentParams, __('Primary agent'), $stratData['primaryagentid'], true);
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strategy') . ' [' . $stratId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders strategies dry-run testing form
     *
     * @return string
     */
    protected function renderStratTestingForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_CH_USER, __('Login'), ubRouting::post(self::PROUTE_CH_USER), false, 10, 'login') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_CH_AMOUNT, __('Payment sum'), ubRouting::post(self::PROUTE_CH_AMOUNT), false, 5, 'finance') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_CH_STRAT, __('Strategy'), ubRouting::post(self::PROUTE_CH_STRAT), false, 2, 'digits') . ' ';
        $inputs .= wf_Submit(__('Testing'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders a list of available strategies in a table format.
     *
     * @return string
     */
    public function renderStrategiesList() {
        $result = '';
        if (!empty($this->allStrategies)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Use assigns'));
            $cells .= wf_TableCell(__('Primary agent'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allStrategies as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell(web_bool_led($each['useassigns']));
                $agentName = (isset($this->allAgentNames[$each['primaryagentid']])) ? $this->allAgentNames[$each['primaryagentid']] : __('No');
                $cells .= wf_TableCell($agentName);
                $actControls = '';
                $deletionUrl = self::URL_ME . '&' . self::ROUTE_ST_DELETE . '=' . $each['id'];
                $delTitle = __('Delete') . ' ' . $each['name'] . '?';
                $actControls .= wf_ConfirmDialog($deletionUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', self::URL_ME, $delTitle);
                $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderStrategyEditForm($each['id'])) . ' ';
                $actControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_SP_EDIT . '=' . $each['id'], web_icon_extended(__('Config')));
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        $result .= wf_delimiter();
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderStrategyCreateForm(), 'ubButton') . ' ';
        if (!empty($this->allStrategies)) {
            $result .= wf_modalAuto(wf_img('skins/icon_testing.png') . ' ' . __('Testing'), __('Testing'), $this->renderStratTestingForm(), 'ubButton');
        }
        return ($result);
    }

    /**
     * Retrieves the name of a strategy based on its ID.
     *
     * @param int $stratId The ID of the strategy.
     * 
     * @return string The name of the strategy if found, otherwise an empty string.
     */
    public function getStrategyName($stratId) {
        $result = '';
        if (isset($this->allStrategies[$stratId])) {
            $result .= $this->allStrategies[$stratId]['name'];
        }
        return ($result);
    }
    /**
     * Creates a new specification entry in the database.
     *
     * @param int $stratId The strategy ID to associate with the specification.
     * @param int $agentId The agent ID to associate with the specification.
     * @param string $type The type of the specification.
     * @param int $value The value of the specification.
     *
     * @return void
     */

    public function createSpec($stratId, $agentId, $type, $value) {
        $stratId = ubRouting::filters($stratId, 'int');
        $agentId = ubRouting::filters($agentId, 'int');
        $type = ubRouting::filters($type, 'mres');
        $value = ubRouting::filters($value, 'int');
        $customData = json_encode(array());

        $this->specsDb->data('stratid', $stratId);
        $this->specsDb->data('agentid', $agentId);
        $this->specsDb->data('type', $type);
        $this->specsDb->data('value', $value);
        $this->specsDb->data('customdata', $customData);
        $this->specsDb->create();
        $newId = $this->specsDb->getLastId();
        log_register('GOOSE STRAT [' . $stratId . '] CREATE SPEC [' . $newId . '] AGENT [' . $agentId . '] `' . $type . '` VALUE `' . $value . '`');
    }

    /**
     * Save or update a specification in the database.
     *
     * @param int $specId The ID of the specification.
     * @param int $agentId The ID of the agent.
     * @param string $type The type of the specification.
     * @param int $value The value of the specification.
     *
     * @return void
     */
    public function saveSpec($specId, $agentId, $type, $value) {
        $specId = ubRouting::filters($specId, 'int');
        $agentId = ubRouting::filters($agentId, 'int');
        $type = ubRouting::filters($type, 'mres');
        $value = ubRouting::filters($value, 'int');
        if (isset($this->allSpecs[$specId])) {
            $specData = $this->allSpecs[$specId];
            $stratId = $specData['stratid'];
            $this->specsDb->where('id', '=', $specId);
            $this->specsDb->data('agentid', $agentId);
            $this->specsDb->data('type', $type);
            $this->specsDb->data('value', $value);
            $this->specsDb->save();
            $newId = $this->specsDb->getLastId();
            log_register('GOOSE STRAT [' . $stratId . '] EDIT SPEC [' . $specId . '] AGENT [' . $agentId . '] `' . $type . '` VALUE `' . $value . '`');
        }
    }

    /**
     * Deletes a specific specification by its ID.
     *
     * @param int $specId The ID of the specification to delete.
     * 
     * @return void
     */
    public function deleteSpec($specId) {
        $specId = ubRouting::filters($specId, 'int');
        if (isset($this->allSpecs[$specId])) {
            $specData = $this->allSpecs[$specId];
            $stratId = $specData['stratid'];
            $this->specsDb->where('id', '=', $specId);
            $this->specsDb->delete();
            log_register('GOOSE STRAT [' . $stratId . '] DELETE SPEC [' . $specId . ']');
        }
    }

    /**
     * Renders a form for creating a new specification.
     *
     * @param int $stratId The ID of the strategy for which the form is being created.
     * @return string 
     */
    public function renderSpecCreateForm($stratId) {
        $stratId = ubRouting::filters($stratId, 'int');
        $result = '';
        if (isset($this->allStrategies[$stratId])) {
            $inputs = wf_HiddenInput(self::PROUTE_SP_CREATE, 'true');
            $inputs .= wf_HiddenInput(self::PROUTE_SP_STRAT, $stratId);
            $inputs .= wf_Selector(self::PROUTE_SP_AGENT, $this->allAgentNames, __('Agent'), '', false);
            $inputs .= wf_Selector(self::PROUTE_SP_TYPE, $this->specTypes, __('Distribution'), '', false);
            $inputs .= wf_TextInput(self::PROUTE_SP_VALUE, __('Value'), '', true, 5, 'digits');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Strategy') . ' [' . $stratId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders a form for editing a existing specification.
     *
     * @param int $specId The ID of the strategy spec database record
     * @return string 
     */
    public function renderSpecEditForm($specId) {
        $specId = ubRouting::filters($specId, 'int');
        $result = '';
        if (isset($this->allSpecs[$specId])) {
            $specData = $this->allSpecs[$specId];
            $inputs = wf_HiddenInput(self::PROUTE_SP_EDIT, $specId);
            $inputs .= wf_HiddenInput(self::PROUTE_SP_STRAT, $specData['stratid']);
            $inputs .= wf_Selector(self::PROUTE_SP_AGENT, $this->allAgentNames, __('Agent'), $specData['agentid'], false);
            $inputs .= wf_Selector(self::PROUTE_SP_TYPE, $this->specTypes, __('Distribution'), $specData['type'], false);
            $inputs .= wf_TextInput(self::PROUTE_SP_VALUE, __('Value'), $specData['value'], true, 5, 'digits');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Spec') . ' [' . $specId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders the strategy specifications list for a given strategy ID.
     *
     * @param int $stratId The ID of the strategy to render specifications for.
     * 
     * @return string
     */
    public function renderStratSpecsList($stratId) {
        $stratId = ubRouting::filters($stratId, 'int');
        $result = '';
        if (isset($this->allStrategies[$stratId])) {
            $stratSpecs = $this->allStrategies[$stratId]['specs'];
            if (!empty($stratSpecs)) {
                $cells = wf_TableCell(__('Agent'));
                $cells .= wf_TableCell(__('Type'));
                $cells .= wf_TableCell(__('Value'));
                $cells .= wf_TableCell(__('Custom data'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($stratSpecs as $io => $each) {
                    $agentName = (isset($this->allAgentNames[$each['agentid']])) ? $this->allAgentNames[$each['agentid']] : __('No');
                    $cells = wf_TableCell($agentName);
                    $cells .= wf_TableCell($this->specTypes[$each['type']]);
                    $cells .= wf_TableCell($each['value']);
                    $cells .= wf_TableCell(web_bool_led($each['customdata']));
                    $actControls = '';
                    $deletionUrl = self::URL_ME . '&' . self::ROUTE_SP_DELETE . '=' . $each['id'] . '&' . self::ROUTE_SP_EDIT . '=' . $stratId;
                    $cancelUrl = self::URL_ME . '&' . self::ROUTE_SP_EDIT . '=' . $stratId;
                    $agentName = (isset($this->allAgentNames[$each['agentid']])) ? $this->allAgentNames[$each['agentid']] : __('Deleted');
                    $delTitle = __('Delete') . ' ' . $agentName . '?';
                    $actControls .= wf_ConfirmDialog($deletionUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, $delTitle) . ' ';
                    $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderSpecEditForm($each['id']), '');
                    $actControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_SP_CUSTDATA . '=' . $each['id'], wf_img('skins/grcustdata.png', __('Custom data')));
                    $cells .= wf_TableCell($actControls);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strategy') . ' [' . $stratId . '] ' . __('Not exists'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME) . ' ';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Append'), __('Append'), $this->renderSpecCreateForm($stratId), 'ubButton');

        return ($result);
    }

    /**
     * Creates new or replaces custom data field record for some spec
     *
     * @param int $specId
     * @param string $key
     * @param string $value
     * 
     * @return void
     */
    public function setCustDataField($specId, $key, $value = '') {
        $specId = ubRouting::filters($specId, 'int');
        $keyF = ubRouting::filters($key, 'mres');
        $valueF = ubRouting::filters($value, 'mres');
        if (isset($this->allSpecs[$specId])) {
            $currentCustomData = $this->allSpecs[$specId]['customdata'];
            $dataToSave = $currentCustomData;
            $dataToSave[$keyF] = $valueF;
            $dataToSave = json_encode($dataToSave);
            $this->specsDb->where('id', '=', $specId);
            $this->specsDb->data('customdata', $dataToSave);
            $this->specsDb->save();
            log_register('GOOSE SPEC [' . $specId . '] CUSTDATA SET `' . $key . '` ON `' . $value . '`');
        }
    }

    /**
     * Deletes custom data field record for some spec
     *
     * @param int $specId
     * @param string $key
     * 
     * @return void
     */
    public function deleteCustDataField($specId, $key) {
        $specId = ubRouting::filters($specId, 'int');
        $keyF = ubRouting::filters($key, 'mres');
        if (isset($this->allSpecs[$specId])) {
            $currentCustomData = $this->allSpecs[$specId]['customdata'];
            $dataToSave = $currentCustomData;
            unset($dataToSave[$key]);
            $dataToSave = json_encode($dataToSave);
            $this->specsDb->where('id', '=', $specId);
            $this->specsDb->data('customdata', $dataToSave);
            $this->specsDb->save();
            log_register('GOOSE SPEC [' . $specId . '] CUSTDATA DELETE `' . $key . '`');
        }
    }

    /**
     * Renders a custom data fields creation form based on the provided specification ID.
     *
     * @param int $specId 
     * 
     * @return string
     */
    protected function renderCustomDataCreateForm($specId) {
        $result = '';
        $specId = ubRouting::filters($specId, 'int');
        if (isset($this->allSpecs[$specId])) {
            $inputs = wf_HiddenInput(self::PROUTE_CD_SPEC, $specId);
            $inputs .= wf_TextInput(self::PROUTE_CD_KEY, __('Key'), '', false, 10);
            $inputs .= wf_TextInput(self::PROUTE_CD_VAL, __('Value'), '', false, 20);
            $inputs .= wf_Submit(__('Set'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Renders spec custom data list and editors
     *
     * @param int $specId
     * 
     * @return string
     */
    public function renderCustomDataEditor($specId) {
        $specId = ubRouting::filters($specId, 'int');
        $result = '';
        if (isset($this->allSpecs[$specId])) {
            $specData = $this->allSpecs[$specId];
            if (!empty($specData['customdata'])) {
                $cells = wf_TableCell(__('Key'));
                $cells .= wf_TableCell(__('Value'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($specData['customdata'] as $key => $value) {
                    $cells = wf_TableCell($key);
                    $cells .= wf_TableCell($value);
                    $delUrl = self::URL_ME . '&' . self::ROUTE_SP_CUSTDATA . '=' . $specId . '&' . self::ROUTE_CD_DELKEY . '=' . $key;
                    $cancelUrl = self::URL_ME . '&' . self::ROUTE_SP_CUSTDATA . '=' . $specId;
                    $actLinks = wf_ConfirmDialog($delUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, __('Delete') . '?');
                    $cells .= wf_TableCell($actLinks);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }

            $result .= wf_delimiter();
            $result .= $this->renderCustomDataCreateForm($specId);
            $result .= wf_delimiter();
            $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_SP_EDIT . '=' . $specData['stratid']);
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': [' . $specId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders strategy-run testing results
     *
     * @param array $stratData
     * 
     * @return string
     */
    public function renderStratTestingResults($stratData) {
        $result = '';
        if (!empty($stratData)) {
            if ($stratData['user']) {
                $result .= $this->messages->getStyledMessage(__('User') . ' `' . $stratData['user']['login'] . '` ' . __('exists'), 'success');
                $result .= $this->messages->getStyledMessage(__('Address') . ': ' . $stratData['user']['fulladress'], 'success');
            } else {
                $result .= $this->messages->getStyledMessage(__('User') . ' `' . $stratData['userlogin'] . '` ' . __('not exists'), 'error');
            }

            if ($stratData['amount'] > 0) {
                $result .= $this->messages->getStyledMessage(__('Payment sum') . ': ' . $stratData['amount'], 'success');
            } else {
                $result .= $this->messages->getStyledMessage(__('Payment sum') . ': ' . $stratData['amount'], 'warning');
            }

            $result .= $this->messages->getStyledMessage(__('Strategy used') . ': ' . '[' . $stratData['id'] . '] ' . $stratData['name'], 'info');

            if (!empty($stratData['agents'])) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Contrahent name'));
                $cells .= wf_TableCell(__('Bank account'));
                $cells .= wf_TableCell(__('EDRPOU'));
                $cells .= wf_TableCell(__('Distribution'));
                $cells .= wf_TableCell(__('Value'));
                $cells .= wf_TableCell(__('Cash'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($stratData['agents'] as $eachAgentId => $eachAgentData) {
                    $cells = wf_TableCell($eachAgentData['id']);
                    $cells .= wf_TableCell($eachAgentData['contrname']);
                    $cells .= wf_TableCell($eachAgentData['bankacc']);
                    $cells .= wf_TableCell($eachAgentData['edrpo']);
                    $cells .= wf_TableCell($this->specTypes[$eachAgentData['splittype']]);
                    $cells .= wf_TableCell($eachAgentData['splitvalue']);
                    $cells .= wf_TableCell($eachAgentData['splitamount']);

                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_delimiter(0);
                $result .= wf_tag('b') . __('Money distribution') . ':' . wf_tag('b', true);
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Contrahens') . ' ' . __('is empty') . '!', 'error');
            }

            //raw json preview
            $inputs = wf_tag('textarea', false, 'fileeditorarea', 'name="editfilecontent" cols="145" rows="30" spellcheck="false"');
            $inputs .= print_r($stratData, true);
            $inputs .= wf_tag('textarea', true);
            $inputs .= wf_tag('br');
            $rawData= wf_Form('', 'POST', $inputs, 'glamour');

            $result .= wf_Spoiler($rawData, __('Preview').' '.__('JSON'), true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Data') . ' ' . __('is empty'), 'error');
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME);
        return ($result);
    }


    /**
     * Preprocess some agents data depend on strategy specs
     *
     * @param array $specs
     * @param float $amount
     * 
     * @return array
     */
    protected function calcAgents($specs, $amount = 0) {
        $result = array();
        $origAmount = $amount;
        $specAmount = $amount;
        $leftoversCount = 0;
        if (!empty($specs)) {
            //specs processing
            foreach ($specs as $io => $each) {
                $agentId = $each['agentid'];
                if (isset($this->allAgents[$agentId])) {
                    $result[$agentId] = $this->allAgents[$agentId];
                    $result[$agentId]['splitamount'] = 0;
                    $result[$agentId]['splittype'] = $each['type'];
                    $result[$agentId]['splitvalue'] = $each['value'];
                    if ($each['type'] == 'leftovers') {
                        $leftoversCount++;
                    }
                    //appending legacy extinfo
                    if (isset($this->allAgentsExtInfo[$agentId])) {
                        $result[$agentId]['extinfo'] = $this->allAgentsExtInfo[$agentId];
                    } else {
                        $result[$agentId]['extinfo'] = array();
                    }
                    //appending custom data section
                    $result[$agentId]['customdata'] = $each['customdata'];
                }
            }

            if ($specAmount > 0) {
                //agents post-processing
                if (!empty($result)) {
                    //absolute values
                    foreach ($result as $io => $each) {
                        if ($each['splittype'] == 'absolute') {
                            if ($each['splitvalue'] > 0) {
                                $splitAmount = $each['splitvalue'];
                                $specAmount = $specAmount - $splitAmount;
                                $result[$each['id']]['splitamount'] = $splitAmount;
                            }
                        }
                    }

                    //percent of sum values
                    foreach ($result as $io => $each) {
                        if ($each['splittype'] == 'percent') {
                            if ($each['splitvalue'] > 0) {
                                $splitAmount = zb_Percent($origAmount, $each['splitvalue']);
                                $specAmount = $specAmount - $splitAmount;
                                $result[$each['id']]['splitamount'] = $splitAmount;
                            }
                        }
                    }
                    //percent of remains values
                    foreach ($result as $io => $each) {
                        if ($each['splittype'] == 'percentleft') {
                            if ($each['splitvalue'] > 0) {
                                $splitAmount = zb_Percent($specAmount, $each['splitvalue']);
                                $specAmount = $specAmount - $splitAmount;
                                $result[$each['id']]['splitamount'] = $splitAmount;
                            }
                        }
                    }

                    //leftovers at end
                    foreach ($result as $io => $each) {
                        if ($each['splittype'] == 'leftovers') {
                            if ($leftoversCount > 1) {
                                $splitAmount = $specAmount / $leftoversCount;
                            } else {
                                $splitAmount = $specAmount;
                            }
                            $result[$each['id']]['splitamount'] = $splitAmount;
                        }
                    }
                }
            }
        }

        return ($result);
    }


    /**
     * Detects the strategy ID assigned to a user based on their login.
     *
     * This method checks if the user has an assigned agent and then matches that agent
     * to a strategy ID from the list of all primary agents.
     *
     * @param string $userLogin The login identifier of the user.
     * 
     * @return int
     **/
    protected function detectUserStrategyId($userLogin) {
        $result = 0;
        if (!empty($userLogin)) {
            if (!empty($this->allStrategies)) {
                $assignedAgentData = $this->getUserAssignedAgentData($userLogin);
                if (!empty($assignedAgentData)) {
                    $assignedAgentId = $assignedAgentData['id'];
                    if ($assignedAgentId) {
                        //based on default address based assigns
                        if (!empty($this->allPrimaryAgents)) {
                            foreach ($this->allPrimaryAgents as $eachStratId => $eachAgentId) {
                                if ($eachAgentId == $assignedAgentId) {
                                    $result = $eachStratId;
                                    break;
                                }
                            }
                        }

                        //use first available strategy if still not detected
                        if ($result == 0) {
                            $firstStratData = reset($this->allStrategies);
                            $result = $firstStratData['id'];
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns strategy data by its ID
     *
     * @param int $stratId implict strategy Id or 0 for auto detect
     * 
     * @return array
     */
    public function getStrategyData($stratId = 0) {
        $stratId = ubRouting::filters($stratId, 'int');
        $result = array();
        //automatic strategy detection
        if ($stratId == 0) {
            $stratId = $this->detectUserStrategyId($this->userLogin);
        }

        if (!empty($stratId)) {
            if (isset($this->allStrategies[$stratId])) {
                $stratData = $this->allStrategies[$stratId];
                $result['amount'] = $this->amount;
                $result['userlogin'] = $this->userLogin;
                $result += $stratData;
                $result['agents'] = array();
                $result['user'] = array();
                $result['runtime'] = array();

                if (!empty($stratData['specs'])) {
                    $result['agents'] = $this->calcAgents($stratData['specs'], $this->amount);
                    //cleanup spec raw data
                    unset($result['specs']);
                }

                //appending user data
                if (!empty($this->userLogin)) {
                    if (isset($this->allUserData[$this->userLogin])) {
                        $result['user'] = $this->allUserData[$this->userLogin];
                        if (isset($this->allCustomerPaymentIds[$this->userLogin])) {
                            $result['user']['paymentid'] = $this->allCustomerPaymentIds[$this->userLogin];
                        }
                    }
                }

                //appending runtime if required
                if (!empty($this->runtime)) {
                    $result['runtime'] = $this->getRuntime($this->runtime);
                }
            }
        }
        return ($result);
    }
}
