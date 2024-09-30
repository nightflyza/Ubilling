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
    const PROUTE_SP_CREATE = 'createnewspec';
    const PROUTE_SP_EDIT = 'editspecid';
    const PROUTE_SP_STRAT = 'specstratid';
    const PROUTE_SP_AGENT = 'specagentid';
    const PROUTE_SP_TYPE = 'spectype';
    const PROUTE_SP_VALUE = 'specvalue';
    const PROUTE_SP_CUSTDATA = 'speccustomdata';



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
            'percent' => __('Percent'),
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
            }
        }
        $this->allSpecs = $this->specsDb->getAll('id');
        if (!empty($this->allSpecs)) {
            foreach ($this->allSpecs as $io => $each) {
                if (isset($this->allStrategies[$each['stratid']])) {
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
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderStrategyCreateForm(), 'ubButton');
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
     * @param string $customData Optional. Additional custom data for the specification.
     *
     * @return void
     */

    public function createSpec($stratId, $agentId, $type, $value, $customData = '') {
        $stratId = ubRouting::filters($stratId, 'int');
        $agentId = ubRouting::filters($agentId, 'int');
        $type = ubRouting::filters($type, 'mres');
        $value = ubRouting::filters($value, 'int');
        $customData = ubRouting::filters($customData, 'mres');

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
     * @param string $customData Optional custom data for the specification.
     *
     * @return void
     */
    public function saveSpec($specId, $agentId, $type, $value, $customData = '') {
        $specId = ubRouting::filters($specId, 'int');
        $agentId = ubRouting::filters($agentId, 'int');
        $type = ubRouting::filters($type, 'mres');
        $value = ubRouting::filters($value, 'int');
        $customData = ubRouting::filters($customData, 'mres');
        if (isset($this->allSpecs[$specId])) {
            $specData = $this->allSpecs[$specId];
            $stratId = $specData['stratid'];
            $this->specsDb->where('id', '=', $specId);
            $this->specsDb->data('agentid', $agentId);
            $this->specsDb->data('type', $type);
            $this->specsDb->data('value', $value);
            $this->specsDb->data('customdata', $customData);
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
            $inputs .= wf_Selector(self::PROUTE_SP_TYPE, $this->specTypes, __('Type'), '', false);
            $inputs .= wf_TextInput(self::PROUTE_SP_VALUE, __('Value'), '', true, 5, 'digits');
            $inputs .= wf_TextArea(self::PROUTE_SP_CUSTDATA, '', '', true, '55x5');
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
            $inputs .= wf_Selector(self::PROUTE_SP_TYPE, $this->specTypes, __('Type'), $specData['type'], false);
            $inputs .= wf_TextInput(self::PROUTE_SP_VALUE, __('Value'), $specData['value'], true, 5, 'digits');
            $inputs .= wf_TextArea(self::PROUTE_SP_CUSTDATA, '', $specData['customdata'], true, '55x5');
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
                    $delTitle = __('Delete') . '?';
                    $actControls .= wf_ConfirmDialog($deletionUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, $delTitle) . ' ';
                    $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderSpecEditForm($each['id']), '');
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
}
