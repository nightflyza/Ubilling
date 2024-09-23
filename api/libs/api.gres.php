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
    protected $allStrategies=array();

    /**
     * some predefined stuff here
     */
    const TABLE_STRATEGY = 'gr_strat';
    const TABLE_SPECS = 'gr_spec';
    const URL_ME = '?module=gooseresistance';


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
        $this->loadConfigs();
        $this->initDb();
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

    protected function loadStrategies() {
        $this->allStrategies=$this->strategyDb->getAll('id');
        
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


}
