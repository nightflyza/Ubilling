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
     * Users count per build as buildId=>count
     *
     * @var array
     */
    protected $usersPerBuild = array();

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

    /**
     * Autonomy hours without electricity for coverage export
     *
     * @var int
     */
    protected $powerAutonomyHours = 72;

    /**
     * Coverage export entity mode: builds or premises
     *
     * @var string
     */
    protected $exportMode = 'builds';

    /**
     * Coverage export with coordinates flag
     *
     * @var int
     */
    protected $exportWithGeo = 0;

    /**
     * Minimum users in build for coverage export
     *
     * @var int
     */
    protected $exportMinUsers = 0;

    /**
     * Coverage export file format: csv or xlsx
     *
     * @var string
     */
    protected $exportFormat = 'xlsx';

    /**
     * Use build DB id in coverage export ID column
     *
     * @var int
     */
    protected $exportUseBuildId = 1;

    /**
     * Export only apartment houses flag
     *
     * @var int
     */
    protected $exportOnlyApartmentHouses = 0;

    /**
     * BUILD_EXTENDED option based flag
     *
     * @var bool
     */
    protected $buildPassportsFlag = false;

    /**
     * Build passports instance placeholder
     *
     * @var object
     */
    protected $buildPassports = '';

    /**
     * Some predefined stuff
     */
    const URL_ME = '?module=report_agentgeo';
    const EXPORT_PATH = './exports/';
    const ROUTE_EXPORT_AGENT = 'exportagent';
    const ROUTE_EXPORT_CITY = 'exportcity';
    const ROUTE_EXPORT_MODE = 'exportmode';
    const ROUTE_EXPORT_GEO = 'exportgeo';
    const ROUTE_EXPORT_MINUSERS = 'exportminusers';
    const ROUTE_EXPORT_FORMAT = 'exportformat';
    const ROUTE_EXPORT_BUILDID = 'exportbuildid';
    const ROUTE_EXPORT_APARTMENT = 'exportapartment';
    const ROUTE_EXPORT_PREVIEW = 'exportpreview';
    const MODE_BUILDS = 'builds';
    const MODE_PREMISES = 'premises';
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    public function __construct() {
        $this->loadConfig();
        $this->loadExportParams();
        $this->loadCities();
        $this->loadStreets();
        $this->loadBuilds();
        $this->initBuildPassports();
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
        if ($ubillingConfig->getAlterParam('BUILD_EXTENDED')) {
            $this->buildPassportsFlag = true;
        }
    }

    /**
     * Loads coverage export filter params from request
     *
     * @return void
     */
    protected function loadExportParams() {
        $this->exportMode = self::MODE_BUILDS;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_MODE)) {
            $mode = ubRouting::get(self::ROUTE_EXPORT_MODE);
            if ($mode == self::MODE_PREMISES) {
                $this->exportMode = self::MODE_PREMISES;
            }
        }

        $this->exportWithGeo = 0;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_GEO)) {
            if (ubRouting::get(self::ROUTE_EXPORT_GEO) == '1') {
                $this->exportWithGeo = 1;
            }
        }

        $this->exportMinUsers = 0;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_MINUSERS)) {
            $this->exportMinUsers = ubRouting::get(self::ROUTE_EXPORT_MINUSERS, 'int');
            if ($this->exportMinUsers < 0) {
                $this->exportMinUsers = 0;
            }
        }

        $this->exportFormat = self::FORMAT_XLSX;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_FORMAT)) {
            $format = ubRouting::get(self::ROUTE_EXPORT_FORMAT);
            if ($format == self::FORMAT_CSV) {
                $this->exportFormat = self::FORMAT_CSV;
            }
        }

        $this->exportUseBuildId = 1;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_AGENT) and ubRouting::checkGet(self::ROUTE_EXPORT_CITY)) {
            $this->exportUseBuildId = 0;
            if (ubRouting::checkGet(self::ROUTE_EXPORT_BUILDID)) {
                $this->exportUseBuildId = 1;
            }
        }

        $this->exportOnlyApartmentHouses = 0;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_APARTMENT)) {
            $this->exportOnlyApartmentHouses = 1;
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
     * Inits build passports object when BUILD_EXTENDED is enabled
     *
     * @return void
     */
    protected function initBuildPassports() {
        if ($this->buildPassportsFlag) {
            $this->buildPassports = new BuildPassport();
        }
    }

    /**
     * Checks is build marked as apartment house in passport
     *
     * @param int $buildId
     *
     * @return bool
     */
    protected function isApartmentHouse($buildId) {
        $result = false;
        if ($this->buildPassportsFlag and !empty($this->buildPassports)) {
            $passportData = $this->buildPassports->getPassportData($buildId);
            if (!empty($passportData)) {
                if ($passportData['anthill']) {
                    $result = true;
                }
            }
        }
        return ($result);
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
                if (isset($this->userBuilds[$login])) {
                    $buildId = $this->userBuilds[$login];
                    if (!isset($this->usersPerBuild[$buildId])) {
                        $this->usersPerBuild[$buildId] = 0;
                    }
                    $this->usersPerBuild[$buildId]++;
                }

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
     * Parses build geo string into lat/lon parts
     *
     * @param string $geo
     *
     * @return array
     */
    protected function parseBuildGeo($geo) {
        $result = array(
            'lat' => '',
            'lon' => ''
        );
        if (!empty($geo) and zb_checkGeoFormat($geo)) {
            $parts = explode(',', $geo);
            $result['lat'] = trim($parts[0]);
            $result['lon'] = trim($parts[1]);
        }
        return ($result);
    }

    /**
     * Escapes one CSV field for semicolon-separated export
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeCsvField($value) {
        $result = str_replace('"', '""', strval($value));
        if ((strpos($result, ';') !== false) or (strpos($result, '"') !== false) or (strpos($result, "\n") !== false)) {
            $result = '"' . $result . '"';
        }
        return ($result);
    }

    /**
     * Renders coverage export options form for one agent and city
     *
     * @param mixed $agentId
     * @param int $cityId
     *
     * @return string
     */
    protected function renderCityExportForm($agentId, $cityId) {
        $modeParams = array(
            self::MODE_BUILDS => __('Builds'),
            self::MODE_PREMISES => __('Premises')
        );
        $geoParams = array(
            '0' => __('Without coordinates'),
            '1' => __('With coordinates')
        );
        $formatParams = array(
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_XLSX => 'XLSX'
        );

        $inputs = wf_HiddenInput('module', 'report_agentgeo');
        $inputs .= wf_HiddenInput(self::ROUTE_EXPORT_AGENT, $agentId);
        $inputs .= wf_HiddenInput(self::ROUTE_EXPORT_CITY, $cityId);
        $inputs .= wf_Selector(self::ROUTE_EXPORT_MODE, $modeParams, __('Coverage'), self::MODE_BUILDS, true);
        $inputs .= wf_Selector(self::ROUTE_EXPORT_GEO, $geoParams, __('Place coordinates'), '1', true);
        $inputs .= wf_TextInput(self::ROUTE_EXPORT_MINUSERS, __('Minimum users in build'), '0', true, 3);
        $inputs .= wf_Selector(self::ROUTE_EXPORT_FORMAT, $formatParams, __('Export format'), self::FORMAT_XLSX, true);
        $inputs .= wf_CheckInput(self::ROUTE_EXPORT_BUILDID, __('Use build ID'), true, true);
        if ($this->buildPassportsFlag) {
            $inputs .= wf_CheckInput(self::ROUTE_EXPORT_APARTMENT, __('Apartment house') . ' ' . __('only'), true, true);
        }
        $inputs .= wf_SubmitClassed('1', '', self::ROUTE_EXPORT_PREVIEW, __('Preview')) . ' ';
        $inputs .= wf_Submit(__('Export'));
        $result = wf_Form('', 'GET', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders export modal control for one agent and city
     *
     * @param mixed $agentId
     * @param int $cityId
     * @param string $cityName
     *
     * @return string
     */
    protected function renderCityExportControl($agentId, $cityId, $cityName) {
        $modalTitle = __('Export') . ' ' . __('Coverage') . ': ' . $cityName;
        $result = wf_modalAuto(wf_img('skins/excel.gif', $modalTitle), $modalTitle, $this->renderCityExportForm($agentId, $cityId));
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
        $cells .= wf_TableCell(__('Actions'));
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
            $cells .= wf_TableCell($this->renderCityExportControl($agentId, $cityId, $cityName));
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
            $result .= $messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Checks is coverage preview requested
     *
     * @return bool
     */
    public function isPreviewRequest() {
        $result = false;
        if (ubRouting::checkGet(self::ROUTE_EXPORT_AGENT) and ubRouting::checkGet(self::ROUTE_EXPORT_CITY)) {
            if (ubRouting::checkGet(self::ROUTE_EXPORT_PREVIEW)) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Handles coverage export request if present
     *
     * @return void
     */
    public function catchExportRequest() {
        if (ubRouting::checkGet(self::ROUTE_EXPORT_AGENT) and ubRouting::checkGet(self::ROUTE_EXPORT_CITY)) {
            if (!$this->isPreviewRequest()) {
                $agentId = ubRouting::get(self::ROUTE_EXPORT_AGENT, 'int');
                $cityId = ubRouting::get(self::ROUTE_EXPORT_CITY, 'int');
                if ($this->exportFormat == self::FORMAT_XLSX) {
                    $this->exportCoverageXLSX($agentId, $cityId, $this->exportMode, $this->exportWithGeo, $this->exportMinUsers);
                } else {
                    $this->exportCoverageCSV($agentId, $cityId, $this->exportMode, $this->exportWithGeo, $this->exportMinUsers);
                }
            }
        }
    }

    /**
     * Renders coverage export preview map and table
     *
     * @return string
     */
    public function renderCoveragePreview() {
        $result = '';
        $messages = new UbillingMessageHelper();
        $agentId = ubRouting::get(self::ROUTE_EXPORT_AGENT, 'int');
        $cityId = ubRouting::get(self::ROUTE_EXPORT_CITY, 'int');
        $rows = $this->collectCoverageRows($agentId, $cityId, $this->exportMode, $this->exportWithGeo, $this->exportMinUsers);
        $cityName = $this->getCityName($cityId);
        $agentName = $agentId;
        if (isset($this->agents[$agentId])) {
            $agentName = $this->agents[$agentId];
        }

        $backUrl = self::URL_ME;
        $result .= wf_BackLink($backUrl) . wf_delimiter();
        $result .= wf_tag('b') . $agentName . ' / ' . $cityName . wf_tag('b', true);
        $result .= wf_delimiter(0);

        if (empty($rows)) {
            $result .= $messages->getStyledMessage(__('Nothing found'), 'warning');
        } else {
            $result .= $this->renderCoveragePreviewMap($rows);

            $result .= wf_delimiter(0);
            $result .= __('Coverage') . ': ';
        if ($this->exportMode == self::MODE_PREMISES) {
            $result .= __('Premises');
        } else {
            $result .= __('Builds');
        }
        $result .= ', ';
        if ($this->exportWithGeo) {
            $result .= __('With coordinates');
        } else {
            $result .= __('Without coordinates');
        }
        if ($this->exportMinUsers > 0) {
            $result .= ', ' . __('Minimum users in build') . ': ' . $this->exportMinUsers;
        }
        if ($this->buildPassportsFlag and $this->exportOnlyApartmentHouses) {
            $result .= ', ' . __('Apartment house') . ' ' . __('only');
        }
            $result .= wf_delimiter(0);
            $result .= $this->renderCoveragePreviewTable($rows);
        }
        return ($result);
    }

    /**
     * Renders coverage preview map for collected rows
     *
     * @param array $rows
     *
     * @return string
     */
    protected function renderCoveragePreviewMap($rows) {
        $result = '';
        $messages = new UbillingMessageHelper();
        $map = new MapCore('agentgeopreview');
        
        $mappedCount = 0;
        $centerGeo = '';

        if (!empty($rows)) {
            foreach ($rows as $io => $row) {
                $buildId = 0;
                if (isset($row['build_id'])) {
                    $buildId = $row['build_id'];
                }
                $geoRaw = '';
                if (isset($this->builds[$buildId])) {
                    if (isset($this->builds[$buildId]['geo'])) {
                        $geoRaw = $this->builds[$buildId]['geo'];
                    }
                }
                $geoParts = $this->parseBuildGeo($geoRaw);
                if (empty($geoParts['lat']) or empty($geoParts['lon'])) {
                    continue;
                }

                $coords = $geoParts['lat'] . ',' . $geoParts['lon'];
                if (empty($centerGeo)) {
                    $centerGeo = $coords;
                }

                $title = $row['street'] . ' ' . $row['build'];
                $popup = __('Street') . ': ' . $row['street'] . wf_tag('br');
                $popup .= __('Build') . ': ' . $row['build'] . wf_tag('br');
                $usersCount = 0;
                if (isset($this->usersPerBuild[$buildId])) {
                    $usersCount = $this->usersPerBuild[$buildId];
                }
                $popup .= __('Users') . ': ' . $usersCount . wf_tag('br');
                $popup .= __('Geo location') . ': ' . $coords;

                $icon = 'marker.building';
                if ($this->buildPassportsFlag) {
                    if ($this->isApartmentHouse($buildId)) {
                        $icon = 'marker.building';
                    } else {
                        $icon = 'marker.house';
                    }
                }

                $map->addMarker($coords, $popup, array(
                    'icon' => $icon,
                    'tooltip' => $title,
                    'popupTitle' => $title
                ));
                $mappedCount++;
            }
        }

        if ($mappedCount > 0) {
            if (!empty($centerGeo)) {
                $map->setCenter($centerGeo);
            }
            $result .= $map->renderContainer('100%', '500px');
            $result .= $map->render();
            $result .= wf_tag('br') . __('Builds') . ' ' . __('on') . ' ' . __('Map') . ': ' . $mappedCount;
        } else {
            $result .= $messages->getStyledMessage(__('No builds with geo location found'), 'info');
        }

        $totalRows = sizeof($rows);
        if ($totalRows > $mappedCount) {
            $result .= wf_tag('br') . __('Without coordinates') . ': ' . ($totalRows - $mappedCount);
        }
        return ($result);
    }

    /**
     * Renders coverage preview table for collected rows
     *
     * @param array $rows
     *
     * @return string
     */
    protected function renderCoveragePreviewTable($rows) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Street'));
        $cells .= wf_TableCell(__('Build'));
        $cells .= wf_TableCell(__('Users'));
        if ($this->buildPassportsFlag) {
            $cells .= wf_TableCell(__('Type'));
        }
        $cells .= wf_TableCell(__('Geo location'));
        $tableRows = wf_TableRow($cells, 'row1');

        if (!empty($rows)) {
            foreach ($rows as $io => $row) {
                $buildId = 0;
                if (isset($row['build_id'])) {
                    $buildId = $row['build_id'];
                }
                $usersCount = 0;
                if (isset($this->usersPerBuild[$buildId])) {
                    $usersCount = $this->usersPerBuild[$buildId];
                }
                $geoLabel = '';
                $lon = '';
                $lat = '';
                if (isset($row['lon'])) {
                    $lon = $row['lon'];
                }
                if (isset($row['lat'])) {
                    $lat = $row['lat'];
                }
                if (empty($lon) or empty($lat)) {
                    if (isset($this->builds[$buildId])) {
                        if (isset($this->builds[$buildId]['geo'])) {
                            $geoParts = $this->parseBuildGeo($this->builds[$buildId]['geo']);
                            $lat = $geoParts['lat'];
                            $lon = $geoParts['lon'];
                        }
                    }
                }
                if ((!empty($lat)) and (!empty($lon))) {
                    $geoLabel = $lat . ', ' . $lon;
                }

                $cells = wf_TableCell($buildId);
                $cells .= wf_TableCell($row['street']);
                $cells .= wf_TableCell($row['build']);
                $cells .= wf_TableCell($usersCount);
                if ($this->buildPassportsFlag) {
                    $typeLabel = __('Private house');
                    if ($this->isApartmentHouse($buildId)) {
                        $typeLabel = __('Apartment house');
                    }
                    $cells .= wf_TableCell($typeLabel);
                }
                $cells .= wf_TableCell($geoLabel);
                $tableRows .= wf_TableRow($cells, 'row5');
            }
        }

        $result = wf_TableBody($tableRows, '100%', '0', 'sortable');
        $result .= __('Total') . ': ' . sizeof($rows);
        return ($result);
    }

    /**
     * Normalizes coverage export options
     *
     * @param string $mode
     * @param int $withGeo
     * @param int $minUsers
     *
     * @return array
     */
    protected function normalizeExportOptions($mode, $withGeo, $minUsers) {
        $result = array(
            'mode' => self::MODE_BUILDS,
            'withGeo' => 0,
            'minUsers' => 0
        );
        if ($mode == self::MODE_PREMISES) {
            $result['mode'] = self::MODE_PREMISES;
        }
        if ($withGeo) {
            $result['withGeo'] = 1;
        }
        $result['minUsers'] = vf($minUsers, 3);
        return ($result);
    }

    /**
     * Returns city name by ID
     *
     * @param int $cityId
     *
     * @return string
     */
    protected function getCityName($cityId) {
        $result = '';
        if (isset($this->cities[$cityId])) {
            $result = $this->cities[$cityId];
        }
        return ($result);
    }

    /**
     * Builds safe coverage export file base name without extension
     *
     * @param int $agentId
     * @param int $cityId
     * @param string $cityName
     *
     * @return string
     */
    protected function getCoverageExportBaseName($agentId, $cityId, $cityName) {
        $cityFileName = zb_TranslitString($cityName);
        $cityFileName = str_replace(' ', '_', $cityFileName);
        $cityFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $cityFileName);
        if (empty($cityFileName)) {
            $cityFileName = $cityId;
        }
        $result = 'coverage_' . $agentId . '_' . $cityFileName;
        return ($result);
    }

    /**
     * Collects coverage export rows for one agent and city
     *
     * @param int $agentId
     * @param int $cityId
     * @param string $mode
     * @param int $withGeo
     * @param int $minUsers
     *
     * @return array
     */
    protected function collectCoverageRows($agentId, $cityId, $mode, $withGeo, $minUsers) {
        $result = array();
        $buildIds = array();
        $options = $this->normalizeExportOptions($mode, $withGeo, $minUsers);
        $mode = $options['mode'];
        $withGeo = $options['withGeo'];
        $minUsers = $options['minUsers'];
        $cityName = $this->getCityName($cityId);

        if (isset($this->reportData[$agentId])) {
            if (isset($this->reportData[$agentId][$cityId])) {
                if ($mode == self::MODE_PREMISES) {
                    $buildIds = $this->reportData[$agentId][$cityId]['premises'];
                } else {
                    $buildIds = $this->reportData[$agentId][$cityId]['builds'];
                }
            }
        }

        if (!empty($buildIds)) {
            foreach ($buildIds as $buildId => $flag) {
                $usersCount = 0;
                if (isset($this->usersPerBuild[$buildId])) {
                    $usersCount = $this->usersPerBuild[$buildId];
                }
                if ($minUsers > 0) {
                    if ($usersCount < $minUsers) {
                        continue;
                    }
                }

                if (!isset($this->builds[$buildId])) {
                    continue;
                }

                if ($this->buildPassportsFlag and $this->exportOnlyApartmentHouses) {
                    if (!$this->isApartmentHouse($buildId)) {
                        continue;
                    }
                }

                $buildData = $this->builds[$buildId];
                $streetName = '';
                $streetId = $buildData['streetid'];
                if (isset($this->streets[$streetId])) {
                    $streetName = $this->streets[$streetId]['streetname'];
                }

                $lon = '';
                $lat = '';
                $geoRaw = '';
                if (isset($buildData['geo'])) {
                    $geoRaw = $buildData['geo'];
                }
                $geoParts = $this->parseBuildGeo($geoRaw);
                if ($withGeo) {
                    if (empty($geoParts['lat']) or empty($geoParts['lon'])) {
                        continue;
                    }
                    $lat = $geoParts['lat'];
                    $lon = $geoParts['lon'];
                }

                $row = array(
                    'build_id' => $buildId,
                    'id' => '',
                    'oblast' => '',
                    'district' => '',
                    'settlement_type' => '',
                    'settlement' => $cityName,
                    'street_type' => '',
                    'street' => $streetName,
                    'build' => $buildData['buildnum'],
                    'corpus' => '',
                    'power_hours' => $this->powerAutonomyHours
                );
                if ($this->exportUseBuildId) {
                    $row['id'] = $buildId;
                }
                if ($withGeo) {
                    $row['lon'] = $lon;
                    $row['lat'] = $lat;
                }
                $result[] = $row;
            }
        }

        return ($result);
    }

    /**
     * Returns coverage export column headers
     * 
     * TODO: make this headers loadable from some config
     *
     * @param int $withGeo
     *
     * @return array
     */
    protected function getCoverageExportHeaders($withGeo) {
        $result = array(
            'ID (за наявністю)',
            'Область',
            'Район',
            'Тип населеного пункту',
            'Населений пункт*',
            'Тип вулиці',
            'Вулиця*',
            'Будинок',
            'Корпус',
            'Тривалість роботи без електроенергії'
        );
        if ($withGeo) {
            $result[] = 'Довгота';
            $result[] = 'Широта';
        }
        return ($result);
    }

    /**
     * Converts coverage row assoc into flat export cells
     *
     * @param array $row
     * @param int $withGeo
     *
     * @return array
     */
    protected function coverageRowToCells($row, $withGeo) {
        $result = array(
            $row['id'],
            $row['oblast'],
            $row['district'],
            $row['settlement_type'],
            $row['settlement'],
            $row['street_type'],
            $row['street'],
            $row['build'],
            $row['corpus'],
            $row['power_hours']
        );
        if ($withGeo) {
            $result[] = $row['lon'];
            $result[] = $row['lat'];
        }
        return ($result);
    }

    /**
     * Exports coverage CSV for one agent and city
     *
     * @param int $agentId
     * @param int $cityId
     * @param string $mode
     * @param int $withGeo
     * @param int $minUsers
     *
     * @return void
     */
    public function exportCoverageCSV($agentId, $cityId, $mode, $withGeo, $minUsers) {
        $options = $this->normalizeExportOptions($mode, $withGeo, $minUsers);
        $withGeo = $options['withGeo'];
        $rows = $this->collectCoverageRows($agentId, $cityId, $mode, $withGeo, $minUsers);
        $cityName = $this->getCityName($cityId);
        $result = '';

        $headers = $this->getCoverageExportHeaders($withGeo);
        $csvHeader = array();
        foreach ($headers as $io => $header) {
            $csvHeader[] = $this->escapeCsvField($header);
        }
        $result .= implode(';', $csvHeader) . "\n";

        if (!empty($rows)) {
            foreach ($rows as $io => $row) {
                $cells = $this->coverageRowToCells($row, $withGeo);
                $csvRow = array();
                foreach ($cells as $cellIndex => $cellValue) {
                    $csvRow[] = $this->escapeCsvField($cellValue);
                }
                $result .= implode(';', $csvRow) . "\n";
            }
        }

        $saveCsvName = self::EXPORT_PATH . $this->getCoverageExportBaseName($agentId, $cityId, $cityName) . '.csv';
        file_put_contents($saveCsvName, $result);
        log_register('DOWNLOAD FILE `' . basename($saveCsvName) . '`');
        zb_DownloadFile($saveCsvName, 'csv');
        die();
    }

    /**
     * Exports coverage XLSX for one agent and city
     *
     * @param int $agentId
     * @param int $cityId
     * @param string $mode
     * @param int $withGeo
     * @param int $minUsers
     *
     * @return void
     */
    public function exportCoverageXLSX($agentId, $cityId, $mode, $withGeo, $minUsers) {
        $options = $this->normalizeExportOptions($mode, $withGeo, $minUsers);
        $withGeo = $options['withGeo'];
        $rows = $this->collectCoverageRows($agentId, $cityId, $mode, $withGeo, $minUsers);
        $cityName = $this->getCityName($cityId);
        $messages = new UbillingMessageHelper();
        $saveXlsxName = self::EXPORT_PATH . $this->getCoverageExportBaseName($agentId, $cityId, $cityName) . '.xlsx';

        $xlsx = new XLSX();
        $xlsx->setSheetName('Coverage');
        $xlsx->setHeaders($this->getCoverageExportHeaders($withGeo));
        $numberColumns = array(0, 9);
        if ($withGeo) {
            $numberColumns[] = 10;
            $numberColumns[] = 11;
        }
        $xlsx->setNumberColumns($numberColumns);
        if (!empty($rows)) {
            foreach ($rows as $io => $row) {
                $xlsx->addRow($this->coverageRowToCells($row, $withGeo));
            }
        }

        if ($xlsx->save($saveXlsxName)) {
            log_register('DOWNLOAD FILE `' . basename($saveXlsxName) . '`');
            zb_DownloadFile($saveXlsxName, XLSX::MIME);
            die();
        } else {
            show_error($messages->getStyledMessage(__('Something went wrong'), 'error'));
        }
    }

}
