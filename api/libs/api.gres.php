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
     * some predefined stuff here
     */
    const TABLE_STRATEGY = 'gr_strat';
    const TABLE_SPECS = 'gr_spec';
    const URL_ME = '?module=gooseresistance';

    const PROUTE_ST_CREATE = 'createnewstrategy';
    const PROUTE_ST_EDIT = 'editstrategyid';
    const PROUTE_ST_NAME = 'strategyname';
    const PROUTE_ST_ASSIGNS = 'strategyassignsflag';
    const PROUTE_ST_AGENTID = 'strategyprimaryagentid';




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
        $this->initDb();
        $this->loadStrategies();
        $this->loadUserData();
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
            }
        }
        $this->allSpecs = $this->specsDb->getAll('id');
        if (!empty($this->allSpecs)) {
            foreach ($this->allSpecs as $io => $each) {
                if (isset($this->allStrategies[$each['stratid']])) {
                    $this->allStrategies[$each['strategyid']]['specs'][] = $each;
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
     * Returns some runtime array
     *
     * @param string $name
     * 
     * @return array
     */
    public function getRuntime($name) {
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
            $result .= $this->messages->getStyledMessage(__('Strategy') . ' [' . $stratId. '] ' . __('Not exists'), 'error');
        }
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
                $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderStrategyEditForm($each['id']));
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        $result .= wf_delimiter();
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderStrategyCreateForm(), 'ubButton');
        return ($result);
    }
}
