<?php

/**
 * Per-agent geography report by settlements and premises
 */
class AgentGeoReport {

    /**
     * Contains cities as id=>name
     *
     * @var array
     */
    protected $cities = array();

    /**
     * Contains streets as id=>streetdata
     *
     * @var array
     */
    protected $streets = array();

    /**
     * Contains builds as id=>builddata
     *
     * @var array
     */
    protected $builds = array();

    /**
     * Contains available agents as id=>name
     *
     * @var array
     */
    protected $agents = array();

    /**
     * Raw street assigns from ahenassign
     *
     * @var array
     */
    protected $allAssigns = array();

    /**
     * Strict per-login assigns as login=>agentid
     *
     * @var array
     */
    protected $strictAssigns = array();

    /**
     * Full city addresses as login=>address
     *
     * @var array
     */
    protected $allAddress = array();

    /**
     * Cached user data as login=>userdata
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * User builds as login=>buildid
     *
     * @var array
     */
    protected $userBuilds = array();

    /**
     * Aggregated report data as agentId=>cityId=>counters
     *
     * @var array
     */
    protected $reportData = array();

    /**
     * Default agent ID if set
     *
     * @var int
     */
    protected $defaultAgentId = 0;

    /**
     * Agents assign feature flag
     *
     * @var mixed
     */
    protected $agentsAssignFlag = 0;

    /**
     * KATOTTG enabling flag
     *
     * @var bool
     */
    protected $katottgEnabled = false;

    /**
     * KATOTTG object placeholder
     *
     * @var object
     */
    protected $katottg = '';

    public function __construct() {
        $this->loadConfig();
        $this->loadCities();
        $this->loadStreets();
        $this->loadBuilds();
        $this->loadAgents();
        $this->loadAssigns();
        $this->loadUsers();
        $this->preprocess();
    }

    /**
     * Loads config options into protected properties
     *
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->defaultAgentId = $ubillingConfig->getAlterParam('DEFAULT_ASSIGN_AGENT', 0);
        $this->agentsAssignFlag = $ubillingConfig->getAlterParam('AGENTS_ASSIGN');
        $this->katottgEnabled = $ubillingConfig->getAlterParam('KATOTTG_ENABLED');
        if ($this->katottgEnabled) {
            $this->katottg = new KATOTTG();
        }
    }

    /**
     * Loads available cities from database
     *
     * @return void
     */
    protected function loadCities() {
        $this->cities = zb_AddressGetFullCityNames();
    }

    /**
     * Loads available streets from database
     *
     * @return void
     */
    protected function loadStreets() {
        $this->streets = zb_AddressGetStreetsDataAssoc();
    }

    /**
     * Loads available builds from database
     *
     * @return void
     */
    protected function loadBuilds() {
        $this->builds = zb_AddressGetBuildAllDataAssoc();
    }

    /**
     * Loads contragent names into protected property
     *
     * @return void
     */
    protected function loadAgents() {
        $tmpArr = zb_ContrAhentGetAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->agents[$each['id']] = $each['contrname'];
            }
        }
    }

    /**
     * Loads street assigns, strict assigns and user addresses
     *
     * @return void
     */
    protected function loadAssigns() {
        $this->allAssigns = zb_AgentAssignGetAllData();
        $this->strictAssigns = zb_AgentAssignStrictGetAllData();
        $this->allAddress = zb_AddressGetFullCityaddresslist();
    }

    /**
     * Loads users data and login to build mapping
     *
     * @return void
     */
    protected function loadUsers() {
        $this->allUsers = zb_UserGetAllDataCache();
        $this->userBuilds = zb_AddressGetBuildUsers();
    }

    /**
     * Returns city ID for a build
     *
     * @param int $buildId
     *
     * @return int
     */
    protected function getBuildCityId($buildId) {
        $result = 0;
        if (isset($this->builds[$buildId])) {
            $streetId = $this->builds[$buildId]['streetid'];
            if (isset($this->streets[$streetId])) {
                $result = $this->streets[$streetId]['cityid'];
            }
        }
        return ($result);
    }

    /**
     * Returns agent ID for a street by ahenassign match or default agent
     *
     * @param int $cityId
     * @param int $streetId
     *
     * @return mixed
     */
    protected function getStreetAgentId($cityId, $streetId) {
        $result = $this->defaultAgentId;
        if ($this->agentsAssignFlag) {
            $cityName = '';
            $streetName = '';
            if (isset($this->cities[$cityId])) {
                $cityName = $this->cities[$cityId];
            }
            if (isset($this->streets[$streetId])) {
                $streetName = $this->streets[$streetId]['streetname'];
            }
            $addrString = $cityName . ' ' . $streetName;
            if (!empty($this->allAssigns) and !empty($addrString)) {
                foreach ($this->allAssigns as $io => $eachassign) {
                    if (strpos($addrString, $eachassign['streetname']) !== false) {
                        $result = $eachassign['ahenid'];
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Ensures report row exists for agent and city
     *
     * @param mixed $agentId
     * @param int $cityId
     *
     * @return void
     */
    protected function ensureRow($agentId, $cityId) {
        if (!isset($this->reportData[$agentId])) {
            $this->reportData[$agentId] = array();
        }
        if (!isset($this->reportData[$agentId][$cityId])) {
            $this->reportData[$agentId][$cityId] = array(
                'builds' => array(),
                'premises' => array(),
                'users' => 0,
                'active' => 0
            );
        }
    }

    /**
     * Prepares aggregated report data
     *
     * @return void
     */
    protected function preprocess() {
        $occupiedBuilds = array();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $login => $userData) {
                $address = '';
                if (isset($this->allAddress[$login])) {
                    $address = $this->allAddress[$login];
                }
                $agentId = zb_AgentAssignCheckLoginFast($login, $this->allAssigns, $address, $this->strictAssigns);
                if (!empty($agentId)) {
                    if (isset($this->userBuilds[$login])) {
                        $buildId = $this->userBuilds[$login];
                        $cityId = $this->getBuildCityId($buildId);
                        if (!empty($cityId)) {
                            $this->ensureRow($agentId, $cityId);
                            $this->reportData[$agentId][$cityId]['users']++;
                            $this->reportData[$agentId][$cityId]['builds'][$buildId] = 1;
                            $occupiedBuilds[$buildId] = 1;
                            $alive = zb_UserIsAlive($userData);
                            if ($alive == 1) {
                                $this->reportData[$agentId][$cityId]['active']++;
                                $this->reportData[$agentId][$cityId]['premises'][$buildId] = 1;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($this->builds)) {
            foreach ($this->builds as $buildId => $buildData) {
                if (!isset($occupiedBuilds[$buildId])) {
                    $cityId = $this->getBuildCityId($buildId);
                    if (!empty($cityId)) {
                        $agentId = $this->getStreetAgentId($cityId, $buildData['streetid']);
                        if (!empty($agentId)) {
                            $this->ensureRow($agentId, $cityId);
                            $this->reportData[$agentId][$cityId]['builds'][$buildId] = 1;
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns KATOTTG codes for a city
     *
     * @param int $cityId
     *
     * @return array
     */
    protected function getCityKatottg($cityId) {
        $result = array();
        if ($this->katottgEnabled and !empty($this->katottg)) {
            $result = $this->katottg->getCodeDataByCity($cityId);
        }
        return ($result);
    }

    /**
     * Renders a KATOTTG code with an external lookup link
     *
     * @param string $code
     *
     * @return string
     */
    protected function renderKatottgCode($code) {
        $result = '';
        if (!empty($code) and !empty($this->katottg)) {
            $result = $code . ' ' . $this->katottg->renderLookupControl($code);
        }
        return ($result);
    }

    /**
     * Renders one agent table by cities
     *
     * @param mixed $agentId
     * @param array $cityRows
     *
     * @return string
     */
    protected function renderAgentTable($agentId, $cityRows) {
        $result = '';
        $agentName = $agentId;
        if (isset($this->agents[$agentId])) {
            $agentName = $this->agents[$agentId];
        }
        $result .= wf_tag('h2') . $agentName . wf_tag('h2', true);

        $cells = '';
        if ($this->katottgEnabled) {
            $cells .= wf_TableCell(__('KATOTTG settlement'));
            $cells .= wf_TableCell(__('KATOTTG'));
            $cells .= wf_TableCell(__('Oblast'));
            $cells .= wf_TableCell(__('District'));
            $cells .= wf_TableCell(__('Territorial community'));
        }
        $cells .= wf_TableCell(__('City'));
        $cells .= wf_TableCell(__('Builds'));
        $cells .= wf_TableCell(__('Users'));
        $cells .= wf_TableCell(__('Active users'));
        $cells .= wf_TableCell(__('Premises'));
        $rows = wf_TableRow($cells, 'row1');

        $totalBuilds = 0;
        $totalUsers = 0;
        $totalActive = 0;
        $totalPremises = 0;

        $sortedCities = array();
        foreach ($cityRows as $cityId => $counters) {
            if (isset($this->cities[$cityId])) {
                $sortedCities[$cityId] = $this->cities[$cityId];
            } else {
                $sortedCities[$cityId] = $cityId;
            }
        }
        asort($sortedCities);

        foreach ($sortedCities as $cityId => $cityName) {
            $counters = $cityRows[$cityId];
            $buildsCount = sizeof($counters['builds']);
            $premisesCount = sizeof($counters['premises']);
            $totalBuilds += $buildsCount;
            $totalUsers += $counters['users'];
            $totalActive += $counters['active'];
            $totalPremises += $premisesCount;

            $cells = '';
            if ($this->katottgEnabled) {
                $katData = $this->getCityKatottg($cityId);
                $katName = '';
                $katCi = '';
                $katOb = '';
                $katRa = '';
                $katTg = '';
                if (isset($katData['name'])) {
                    $katName = $katData['name'];
                }
                if (isset($katData['ci'])) {
                    $katCi = $this->renderKatottgCode($katData['ci']);
                }
                if (isset($katData['ob'])) {
                    $katOb = $this->renderKatottgCode($katData['ob']);
                }
                if (isset($katData['ra'])) {
                    $katRa = $this->renderKatottgCode($katData['ra']);
                }
                if (isset($katData['tg'])) {
                    $katTg = $this->renderKatottgCode($katData['tg']);
                }
                $cells .= wf_TableCell($katName);
                $cells .= wf_TableCell($katCi);
                $cells .= wf_TableCell($katOb);
                $cells .= wf_TableCell($katRa);
                $cells .= wf_TableCell($katTg);
            }
            $cells .= wf_TableCell($cityName);
            $cells .= wf_TableCell($buildsCount);
            $cells .= wf_TableCell($counters['users']);
            $cells .= wf_TableCell($counters['active']);
            $cells .= wf_TableCell($premisesCount);
            $rows .= wf_TableRow($cells, 'row5');
        }

        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= __('Builds') . ': ' . $totalBuilds;
        $result .= wf_tag('br');
        $result .= __('Users') . ': ' . $totalUsers;
        $result .= wf_tag('br');
        $result .= __('Active users') . ': ' . $totalActive;
        $result .= wf_tag('br');
        $result .= __('Premises') . ': ' . $totalPremises;
        $result .= wf_delimiter();
        return ($result);
    }

    /**
     * Renders full per-agent geography report
     *
     * @return string
     */
    public function render() {
        $result = '';
        if (!empty($this->reportData)) {
            $agentOrder = array();
            foreach ($this->reportData as $agentId => $cityRows) {
                if (isset($this->agents[$agentId])) {
                    $agentOrder[$agentId] = $this->agents[$agentId];
                } else {
                    $agentOrder[$agentId] = $agentId;
                }
            }
            asort($agentOrder);
            foreach ($agentOrder as $agentId => $agentName) {
                $result .= $this->renderAgentTable($agentId, $this->reportData[$agentId]);
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

}
