<?php

/**
 * This class allow cast abstract stigmatization of random objects placed in random scopes.
 */
class Stigma {

    /**
     * Contains current instance stigma-scope
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Contains current instance type: checklist or radiolist
     *
     * @var string
     */
    protected $type = 'radiolist';

    /**
     * Stigma controller renderer type: iconic, selector, textlink, etc...
     * 
     * @var string
     */
    protected $renderer = 'iconic';

    /**
     * Contains available stigma type icons as state=>iconname
     *
     * @var array
     */
    protected $icons = array();

    /**
     * Contains all of states available in current scope as state=>name
     *
     * @var array
     */
    protected $states = array();

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Database abstraction layer placeholder
     *
     * @var object
     */
    protected $stigmaDb = '';

    /**
     * Contains all of available stigmas from database as itemid=>data
     *
     * @var array
     */
    protected $allStigmas = '';

    /**
     * Contains controller callbacks URL
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Configurable active-state class
     *
     * @var string
     */
    protected $activeClass = 'todaysig';

    /**
     * Configurable base-state controls class
     * 
     * @var string
     */
    protected $baseClass = 'dashtask';

    /**
     * Stigma content update animation
     *
     * @var bool
     */
    protected $animated = true;

    /**
     * Taskman logging flag/parameter name. Disabled if empty.
     *
     * @var string
     */
    protected $taskmanLogging = '';

    /**
     * System weblogs logging flag/parameter name. Disabled if empty.
     *
     * @var string
     */
    protected $systemLogging = '';

    /**
     * System custom logging flag/table name. Disabled if empty.
     *
     * @var string
     */
    protected $customLogging = '';

    /**
     * Default icons file extension
     */
    const ICON_EXT = '.png';

    /**
     * Default state icons path
     */
    const ICON_PATH = 'skins/stigma/';

    /**
     * Default stigma configuration files path
     */
    const CONFIG_PATH = 'config/stigma/';

    /**
     * Custom stigma configs path. Have higher priority on loading.
     */
    const CUSTOM_CONFIG_PATH = 'content/documents/mystigma/confs/';

    /**
     * Custom stigma icons path. Search icons at start at CUSTOM_ICON_PATH then on ICON_PATH.
     */
    const CUSTOM_ICON_PATH = 'content/documents/mystigma/icons/';

    /**
     * per-scope configuration files extension
     */
    const CONFIG_EXT = '.ini';

    /**
     * Contains default datasource table name
     */
    const TABLE_DATASOURCE = 'stigma';

    /**
     * Contains defaul states delimiter for multiple states
     */
    const DELIMITER = '|';

    /**
     * Renderer methods names prefix
     */
    const RENDERER_PREFIX = 'renderer';

    /**
     * Some URLS/routes etc
     */
    const ROUTE_SCOPE = 'stscope';
    const ROUTE_ITEMID = 'stitemid';
    const ROUTE_STATE = 'stchstate';
    const ROUTE_ICONSIZE = 'stis';
    const ROUTE_REPORT_DATE_FROM = 'stdatefrom';
    const ROUTE_REPORT_DATE_TO = 'stdateto';
    const ROUTE_REPORT_STATE = 'strepstate';
    const ROUTE_REPORT_BUCKET = 'strepbucket';

    /**
     * Extended report link modes
     */
    const LINK_MODE_NONE = 'none';
    const LINK_MODE_USERS = 'users';
    const LINK_MODE_URL = 'url';

    /**
     * Creates new stigma on selected scope
     * 
     * @param string $scope scope string identifier of stigma instance
     * @param string $loadOnlyItem preload only some itemId data in selected scope
     */
    public function __construct($scope, $loadOnlyItem = '') {
//            ______              
//         .d$$$******$$$$c.        
//      .d$P"            "$$c      
//     $$$$$.           .$$$*$.    
//   .$$ 4$L*$$.     .$$Pd$  '$b   
//   $F   *$. "$$e.e$$" 4$F   ^$b  
//  d$     $$   z$$$e   $$     '$. 
//  $P     `$L$$P` `"$$d$"      $$ 
//  $$     e$$F       4$$b.     $$ 
//  $b  .$$" $$      .$$ "4$b.  $$ 
//  $$e$P"    $b     d$`    "$$c$F 
//  '$P$$$$$$$$$$$$$$$$$$$$$$$$$$  
//   "$c.      4$.  $$       .$$   
//    ^$$.      $$ d$"      d$P    
//      "$$c.   `$b$F    .d$P"     
//        `4$$$c.$$$..e$$P"        
//            `^^^^^^^`
        $this->setScope($scope);
        $this->setBaseUrl();
        $this->setAdminLogin();
        $this->initDatabase();
        $this->loadConfig();
        $this->loadStigmas($loadOnlyItem);
    }

    /**
     * Sets current instance scope
     * 
     * @param string $scope
     * @throws Exception
     * 
     * @return void
     */
    protected function setScope($scope) {
        if (!empty($scope)) {
            $this->scope = ubRouting::filters($scope, 'mres');
        } else {
            throw new Exception('EX_EMPTY_SCOPE');
        }
    }

    /**
     * Sets current instance base URL
     * 
     * @throws Exception
     * 
     * @return void
     */
    protected function setBaseUrl() {
        $url = '';
        $getVars = ubRouting::rawGet();

        $myRoutes = array(self::ROUTE_ICONSIZE, self::ROUTE_ITEMID, self::ROUTE_SCOPE, self::ROUTE_STATE);
        $myRoutes = array_flip($myRoutes);

        if (!empty($getVars)) {
            $url = '?';
            foreach ($getVars as $getVar => $getVal) {
                if (!isset($myRoutes[$getVar])) {
                    $url .= $getVar . '=' . $getVal . '&';
                }
            }
        }

        if (!empty($url)) {
            $this->baseUrl = $url;
        } else {
            throw new Exception('EX_EMPTY_BASEURL');
        }
    }

    /**
     * Sets current administrator login property
     * 
     * @return void
     */
    protected function setAdminLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Creates protected database abstraction layer
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->stigmaDb = new NyanORM(self::TABLE_DATASOURCE);
    }

    /**
     * Taskman logging flag/name public setter
     * 
     * @param string $parameter
     * 
     * @return void
     */
    public function setTaskmanLogging($parameter = '') {
        $this->taskmanLogging = $parameter;
    }

    /**
     * System weblogs logging flag/name public setter
     * 
     * @param string $parameter
     * 
     * @return void
     */
    public function setSystemLogging($parameter = '') {
        $this->systemLogging = $parameter;
    }

    /**
     * System custom database table logging flag/name public setter
     * 
     * @param string $parameter
     * 
     * @return void
     */
    public function setCustomLogging($parameter = '') {
        $this->customLogging = $parameter;
    }

    /**
     * Preloads current scope settings from config file
     * 
     * @throws Exception
     * 
     * @return void
     */
    protected function loadConfig() {
        $confName = strtolower($this->scope);
        $confFullPath = self::CUSTOM_CONFIG_PATH . $confName . self::CONFIG_EXT;
        if (!file_exists($confFullPath)) {
//use default path
            $confFullPath = self::CONFIG_PATH . $confName . self::CONFIG_EXT;
        }

        if (file_exists($confFullPath)) {
            $raw = rcms_parse_ini_file($confFullPath, true);
            /**
             * One nation, one clan
             * As the sun unites our hands
             * Each colour, each tribe
             * Where the eagle cries with pride
             */
            if (isset($raw['stigmasettings'])) {
                if (isset($raw['stigmasettings']['TYPE'])) {
                    $this->type = $raw['stigmasettings']['TYPE'];

                    if (isset($raw['stigmasettings']['ACTIVECLASS'])) {
                        $this->activeClass = $raw['stigmasettings']['ACTIVECLASS'];
                    }

                    if (isset($raw['stigmasettings']['BASECLASS'])) {
                        $this->baseClass = $raw['stigmasettings']['BASECLASS'];
                    }

                    if (isset($raw['stigmasettings']['ANIMATION'])) {
                        $this->animated = ($raw['stigmasettings']['ANIMATION']) ? false : true;
                    }

                    if (isset($raw['stigmasettings']['RENDERER'])) {
                        $this->renderer = $raw['stigmasettings']['RENDERER'];
                    }

                    foreach ($raw as $io => $each) {
                        if ($io != 'stigmasettings') {
                            $this->states[$io] = $each['NAME'];
                            if (isset($each['ICON'])) {
                                $this->icons[$io] = $each['ICON'];
                            }
                        }
                    }
                } else {
                    throw new Exception('EX_STIGMATYPE_MISSED');
                }
            } else {
                throw new Exception('EX_STIGMASETTINGS_SECTION_MISSED');
            }
        } else {
            throw new Exception('EX_CONF_NOT_EXISTS');
        }
    }

    /**
     * Loads all states from database for current scope
     * 
     * @param string $loadOnlyItem preload only some itemId data in selected scope
     * 
     * @return void
     */
    protected function loadStigmas($loadOnlyItem = '') {
        $this->stigmaDb->where('scope', '=', $this->scope);
        if (!empty($loadOnlyItem)) {
            $itemFilter = ubRouting::filters($loadOnlyItem, 'mres');
            $this->stigmaDb->where('itemid', '=', $itemFilter);
        }
        $this->allStigmas = $this->stigmaDb->getAll('itemid');
    }

    /**
     * Returns some stateId icon if available or default icon if not.
     * 
     * @param string $stateId
     * 
     * @return string
     */
    public function getStateIcon($stateId) {
        $result = '';
        if (file_exists(self::CUSTOM_ICON_PATH . @$this->icons[$stateId] . self::ICON_EXT)) {
            $result = self::CUSTOM_ICON_PATH . @$this->icons[$stateId] . self::ICON_EXT;
        } else {
            if (file_exists(self::ICON_PATH . @$this->icons[$stateId] . self::ICON_EXT)) {
                $result = self::ICON_PATH . @$this->icons[$stateId] . self::ICON_EXT;
            }
        }

        if (empty($result)) {
            $result = self::ICON_PATH . 'default' . self::ICON_EXT;
        }
        return($result);
    }

    /**
     * Returns default iconic renderer controls
     * 
     * @param string $itemId
     * @param int $size
     * @param bool $readOnly
     * @param array $currentStates
     * @param string $containerName
     * 
     * @return string 
     */
    protected function rendererIconic($itemId, $size = '', $readOnly = false, $currentStates=array(), $containerName='') {
        $result = '';
        foreach ($this->states as $stateId => $stateName) {
            $stateLabel = __($stateName);
            $controlClass = $this->baseClass;
            if (isset($currentStates[$stateId])) {
                $controlClass .= ' ' . $this->activeClass;
            }

            $stateIcon = $this->getStateIcon($stateId);

            $controlUrl = $this->baseUrl . '&' . self::ROUTE_SCOPE . '=' . $this->scope . '&' . self::ROUTE_ITEMID . '=' . $itemId . '&' . self::ROUTE_STATE . '=' . $stateId;
            if ($size) {
                $controlUrl .= '&' . self::ROUTE_ICONSIZE . '=' . $size;
            }
            if (!$readOnly) {
                $controlLink = wf_AjaxLink($controlUrl, wf_img_sized($stateIcon, $stateLabel, $size), $containerName);
            } else {
                $controlLink = wf_img_sized($stateIcon, $stateLabel, $size);
            }

            $result .= wf_tag('div', false, $controlClass, '');
            $result .= $controlLink;
            $result .= wf_delimiter(0) . $stateLabel;
            $result .= wf_tag('div', true);
        }
        return($result);
    }

    /**
     * Returns selector renderer controls
     * 
     * @param string $itemId
     * @param int $size
     * @param bool $readOnly
     * @param array $currentStates
     * @param string $containerName
     * 
     * @return string 
     */
    protected function rendererSelector($itemId, $size = '', $readOnly = false, $currentStates=array(), $containerName='') {
        $result = '';
        $params = array();
        $disabled = '';
        $selected = '';
        foreach ($this->states as $stateId => $stateName) {
            $controlUrl = $this->baseUrl . '&' . self::ROUTE_SCOPE . '=' . $this->scope . '&' . self::ROUTE_ITEMID . '=' . $itemId . '&' . self::ROUTE_STATE . '=' . $stateId;
            if (isset($currentStates[$stateId])) {
                $selected = $controlUrl;
            }
            if ($readOnly) {
                $disabled = ' DISABLED';
            }

            $stateLabel = __($stateName);
            $params[$controlUrl] = $stateLabel;
        }
        $result .= wf_AjaxSelectorAC($containerName, $params, '', $selected, false, $disabled);
        return($result);
    }

    /**
     * Returns text links renderer controls
     * 
     * @param string $itemId
     * @param int $size
     * @param bool $readOnly
     * @param array $currentStates
     * @param string $containerName
     * 
     * @return string 
     */
    protected function rendererTextlink($itemId, $size = '', $readOnly = false, $currentStates=array(), $containerName='') {
        $result = '';
        foreach ($this->states as $stateId => $stateName) {
            $stateLabel = __($stateName);
            $controlClass = $this->baseClass;
            if (isset($currentStates[$stateId])) {
                $controlClass .= ' ' . $this->activeClass;
            }

            $controlUrl = $this->baseUrl . '&' . self::ROUTE_SCOPE . '=' . $this->scope . '&' . self::ROUTE_ITEMID . '=' . $itemId . '&' . self::ROUTE_STATE . '=' . $stateId;
            if (!$readOnly) {
                $controlLink = wf_AjaxLink($controlUrl, $stateLabel, $containerName, false, $controlClass);
            } else {
                $controlLink = wf_Link('#', $stateLabel, false, $controlClass);
            }
            $result .= $controlLink . ' ';
        }
        return($result);
    }

    /**
     * Returns text links and small images renderer controls
     * 
     * @param string $itemId
     * @param int $size
     * @param bool $readOnly
     * @param array $currentStates
     * @param string $containerName
     * 
     * @return string 
     */
    protected function rendererImagelink($itemId, $size = '', $readOnly = false, $currentStates=array(), $containerName='') {
        $result = '';
        foreach ($this->states as $stateId => $stateName) {
            $stateLabel = __($stateName);
            $controlClass = $this->baseClass;
            if (isset($currentStates[$stateId])) {
                $controlClass .= ' ' . $this->activeClass;
            }
            $stateIcon = $this->getStateIcon($stateId);

            $controlUrl = $this->baseUrl . '&' . self::ROUTE_SCOPE . '=' . $this->scope . '&' . self::ROUTE_ITEMID . '=' . $itemId . '&' . self::ROUTE_STATE . '=' . $stateId;
            if (!$readOnly) {
                $controlLink = wf_AjaxLink($controlUrl, wf_img_sized($stateIcon, $stateLabel, 16) . ' ' . $stateLabel, $containerName, false, $controlClass);
            } else {
                $controlLink = wf_Link('#', wf_img_sized($stateIcon, $stateLabel, 16) . ' ' . $stateLabel, false, $controlClass);
            }
            $result .= $controlLink . ' ';
        }
        return($result);
    }

    /**
     * Renders stigma current state and editing interface for some item
     * 
     * @param string $itemId item ID to render control panel
     * @param int $size optional size of state icons
     * @param bool $readOnly render panel as read-only state preview
     * 
     * @return string
     */
    public function render($itemId, $size = '', $readOnly = false) {
        $result = '';

        $itemId = ubRouting::filters($itemId, 'mres');
        $currentStates = array();

        if (ubRouting::checkGet(self::ROUTE_ICONSIZE)) {
            $size = ubRouting::get(self::ROUTE_ICONSIZE, 'int');
        }

        //this itemid already have an stigma record
        if (isset($this->allStigmas[$itemId])) {
            $rawStates = explode(self::DELIMITER, $this->allStigmas[$itemId]['state']);
            $currentStates = array_flip($rawStates);
            unset($currentStates['']);
        }

        $containerName = 'ajStigma' . $this->scope . '_' . $itemId;
        $result .= wf_AjaxLoader($this->animated);
        $result .= wf_tag('div', false, '', 'id="' . $containerName . '"');

        //selecting and calling controller renderer method
        $rendererMethodName = self::RENDERER_PREFIX . ucfirst($this->renderer);
        if (method_exists($this, $rendererMethodName)) {
            $result .= $this->$rendererMethodName($itemId, $size, $readOnly, $currentStates, $containerName);
        } else {
            throw new Exception('EX_RENDERER_METHOD_NOT_EXISTS:' . $rendererMethodName);
        }

        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();

        return($result);
    }

    /**
     * Renders stigma states list or void if not set for some item
     * 
     * @param string $itemId item ID to render control panel
     * @param int $size optional size of state icons
     * 
     * @return string/void
     */
    public function renderItemStates($itemId, $size = '') {
        $result = '';
        $currentStates = array();
        //have this item existing stigma record?
        if (isset($this->allStigmas[$itemId])) {
            $rawStates = explode(self::DELIMITER, $this->allStigmas[$itemId]['state']);
            $currentStates = array_flip($rawStates);
            unset($currentStates['']);
            if (!empty($currentStates)) {
                foreach ($currentStates as $eachStateId => $index) {
                    if (isset($this->states[$eachStateId])) {
                        $stateLabel = __($this->states[$eachStateId]);
                        $stateIcon = $this->getStateIcon($eachStateId);
                        $result .= wf_img_sized($stateIcon, $stateLabel, $size) . ' ';
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders stigma current states as text string
     * 
     * @param string $itemId item ID to render data
     * @param string $delimiter text delimiter between states
     * @param int $miniIcons icons size if required
     * 
     * @return string
     */
    public function textRender($itemId, $delimiter = '', $miniIcons = '') {
        $result = '';

        $itemId = ubRouting::filters($itemId, 'mres');
        $currentStates = array();

        if (ubRouting::checkGet(self::ROUTE_ICONSIZE)) {
            $size = ubRouting::get(self::ROUTE_ICONSIZE, 'int');
        }

//this itemid already have an stigma record
        if (isset($this->allStigmas[$itemId])) {
            $rawStates = explode(self::DELIMITER, $this->allStigmas[$itemId]['state']);
            $currentStates = array_flip($rawStates);
            unset($currentStates['']);
        }

        foreach ($currentStates as $stateId => $index) {
            if (!empty($stateId)) {
                $stateName = (isset($this->states[$stateId])) ? $this->states[$stateId] : $stateId;
                $stateLabel = __($stateName);
                $iconCode = '';
                if ($miniIcons) {
                    $stateIcon = $this->getStateIcon($stateId);
                    $iconCode = wf_img_sized($stateIcon, $stateLabel, $miniIcons) . ' ';
                }

                $result .= $iconCode . $stateLabel . $delimiter;
            }
        }


        return($result);
    }

    /**
     * Returns array of all item states as is (for isset checks)
     * 
     * @param string $itemId
     * 
     * @return array
     */
    public function getItemStates($itemId) {
        $result = array();
        if (isset($this->allStigmas[$itemId])) {
            if (!empty($this->allStigmas[$itemId]['state'])) {
                $itemStates = explode(self::DELIMITER, $this->allStigmas[$itemId]['state']);
                $result = array_flip($itemStates);
                unset($result['']);
            }
        }
        return($result);
    }

    /**
     * AJAX callbacks processing controller
     * 
     * @param string $logging SYSTEM:[paramname] or TASKMAN:[paramname]
     * 
     * @return void
     */
    public function stigmaController($logging = '') {
        if (ubRouting::checkGet(array(self::ROUTE_SCOPE, self::ROUTE_ITEMID, self::ROUTE_STATE))) {
//my scope?
            if ($this->scope == ubRouting::get(self::ROUTE_SCOPE)) {
                $stigmaCtrl = new Stigma(ubRouting::get(self::ROUTE_SCOPE), ubRouting::get(self::ROUTE_ITEMID));
//state modification callback?
                if (ubRouting::checkGet(self::ROUTE_STATE)) {
                    if (!empty($logging)) {
                        if (ispos($logging, 'TASKMAN:')) {
                            $logging = str_replace('TASKMAN:', '', $logging);
                            $stigmaCtrl->setTaskmanLogging($logging);
                        }

                        if (ispos($logging, 'SYSTEM:')) {
                            $logging = str_replace('SYSTEM:', '', $logging);
                            $stigmaCtrl->setSystemLogging($logging);
                        }

                        if (ispos($logging, 'CUSTOM:')) {
                            $logging = str_replace('CUSTOM:', '', $logging);
                            $stigmaCtrl->setCustomLogging($logging);
                        }
                    }

                    $stigmaCtrl->saveState(ubRouting::get(self::ROUTE_ITEMID), ubRouting::get(self::ROUTE_STATE));
                }
                die($stigmaCtrl->render(ubRouting::get(self::ROUTE_ITEMID)));
            }
        }
    }

    /**
     * Creates new stigma in database
     * 
     * @param string $itemId
     * @param string $state
     * 
     * @return void
     */
    protected function createState($itemId, $state) {
        $this->stigmaDb->data('scope', $this->scope);
        $this->stigmaDb->data('itemid', $itemId);
        $this->stigmaDb->data('state', $state);
        $this->stigmaDb->data('date', curdatetime());
        $this->stigmaDb->data('admin', $this->myLogin);
        $this->stigmaDb->create();
    }

    /**
     * Sets some state string to selected item in current scope
     * 
     * @param string $itemId
     * @param string $state
     * 
     * @return void
     */
    protected function setState($itemId, $state) {
        $this->stigmaDb->data('state', $state);
        $this->stigmaDb->data('date', curdatetime());
        $this->stigmaDb->data('admin', $this->myLogin);
        $this->stigmaDb->where('scope', '=', $this->scope);
        $this->stigmaDb->where('itemid', '=', $itemId);
        $this->stigmaDb->save(true, true);
    }

    /**
     * Saves some new stigma state into database
     * 
     * @param string $itemId
     * @param string $state
     * 
     * @return void
     */
    public function saveState($itemId, $state) {
        $itemId = ubRouting::filters($itemId, 'mres');
        $state = ubRouting::filters($state, 'mres');

//Item stigma already exists. Update it.
        if (isset($this->allStigmas[$itemId])) {
            $currentStates = $this->getItemStates($itemId);
            if ($this->type == 'radiolist') {
//state is changed?
                if (!isset($currentStates[$state])) {
                    $this->setState($itemId, $state);
                    $this->logStigmaChange($itemId, 'Changed', $state);
                }
            }

            if ($this->type == 'checklist') {
//uncheck already set state
                if (isset($currentStates[$state])) {
                    $newStates = $currentStates;
                    unset($newStates[$state]);
                    $newStatesString = '';
                    if (!empty($newStates)) {
                        foreach ($newStates as $io => $each) {
                            $newStatesString .= $io . self::DELIMITER;
                        }
                    }
                    $this->logStigmaChange($itemId, 'Deleted', $state);
                } else {
//update state with new one
                    $newStates = $currentStates;
                    $newStates[$state] = $state;
                    $newStatesString = '';
                    if (!empty($newStates)) {
                        foreach ($newStates as $io => $each) {
                            $newStatesString .= $io . self::DELIMITER;
                        }
                    }
                    $this->logStigmaChange($itemId, 'Append', $state);
                }

//saving new item state to database
                $this->setState($itemId, $newStatesString);
            }
        } else {
//new stigma
            $this->createState($itemId, $state);
            $this->logStigmaChange($itemId, 'Created', $state);
        }

//update internal structs
        $this->loadStigmas();
    }

    /**
     * Put logs data into database if required
     * 
     * @param string $itemId
     * @param string $oldState
     * @param string $newState
     * 
     * @return void
     */
    protected function logStigmaChange($itemId, $oldState, $newState) {
        if ($this->taskmanLogging) {
            $oldState = (isset($this->states[$oldState])) ? $this->states[$oldState] : $oldState;
            $newState = (isset($this->states[$newState])) ? $this->states[$newState] : $newState;
            ts_logTaskChange($itemId, $this->taskmanLogging, $oldState, $newState, false);
        }

        if ($this->systemLogging) {
            log_register('STIGMA ' . $this->scope . ' CHANGE [' . $itemId . '] `' . $this->systemLogging . '` ON  `' . $newState . '`');
        }

        if ($this->customLogging) {
            $customLogDb = new NyanORM($this->customLogging);
            $customLogDb->data('date', curdatetime());
            $customLogDb->data('admin', $this->myLogin);
            $customLogDb->data('scope', $this->scope);
            $customLogDb->data('itemid', $itemId);
            $customLogDb->data('action', $oldState);
            $customLogDb->data('state', $newState);
            $customLogDb->create();
        }
    }

    /**
     * Checks for available states for some itemId in scope
     * 
     * @param string $itemId
     * 
     * @return bool
     */
    public function haveState($itemId) {
        $result = false;
        if (isset($this->allStigmas[$itemId])) {
            if (!empty($this->allStigmas[$itemId]['state'])) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Returns array of all available states text labels as stateId=>textLabel
     * 
     * @return array
     */
    public function getAllStates() {
        return($this->states);
    }

    /**
     * Returns all scopes for which stigmas available in database
     * 
     * @return array
     */
    public function getAllScopes() {
        $result = array();
        $raw = $this->stigmaDb->getAll('scope', true, true);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $result[$io] = $io;
            }
        }
        return($result);
    }

    /**
     * Returns report data by states in selected time range
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array
     */
    public function getReportData($dateFrom = '', $dateTo = '') {
        $result = array();
        $dateFilters = false;
        if (!empty($dateFrom) AND !empty($dateTo)) {
            $dateFilters = true;
        }

        if (!empty($this->allStigmas)) {
            foreach ($this->allStigmas as $eachItemId => $eachStigmaData) {
                $addToResult = true;
                if ($dateFilters) {
                    $stigmaDate = $eachStigmaData['date'];
                    if (zb_isDateBetween($dateFrom, $dateTo, $stigmaDate)) {
                        $addToResult = true;
                    } else {
                        $addToResult = false;
                    }
                }

                if ($addToResult) {
                    $itemStates = $this->getItemStates($eachItemId);
                    if (!empty($itemStates)) {
                        foreach ($itemStates as $eachState => $eachIndex) {
                            if (isset($result[$eachState])) {
                                $result[$eachState]['count']++;
                            } else {
                                $result[$eachState]['count'] = 1;
                            }

                            if (isset($result[$eachState]['admins'][$eachStigmaData['admin']])) {
                                $result[$eachState]['admins'][$eachStigmaData['admin']]++;
                            } else {
                                $result[$eachState]['admins'][$eachStigmaData['admin']] = 1;
                            }
                            $result[$eachState]['itemids'][$eachItemId] = $eachStigmaData['admin'];
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders basic report on states applied on current scope
     * 
     * @return string
     */
    public function renderBasicReport() {
        $result = '';
        $availStates = $this->getAllStates();
        $messages = new UbillingMessageHelper();
        //default date intervals setting 
        $dateCurrentDay = curdate();
        $dateMonthBegin = curmonth() . '-01';
        $dateMonthEnd = curmonth() . '-' . date("t");
        $dateWeekBegin = date("Y-m-d", strtotime('monday this week'));
        $dateWeekEnd = date("Y-m-d", strtotime('sunday this week'));
        $dateYearBegin = curyear() . '-01-01';
        $dateYearEnd = curyear() . '-12-31';

        //getting report data
        $dataDay = $this->getReportData($dateCurrentDay, $dateCurrentDay);
        $dataWeek = $this->getReportData($dateWeekBegin, $dateWeekEnd);
        $dataMonth = $this->getReportData($dateMonthBegin, $dateMonthEnd);
        $dataYear = $this->getReportData($dateYearBegin, $dateYearEnd);
        $dataAllTime = $this->getReportData();

        if (!empty($availStates)) {
            $cells = wf_TableCell(__('Job'), '30%');
            $cells .= wf_TableCell(__('Day'));
            $cells .= wf_TableCell(__('Week'));
            $cells .= wf_TableCell(__('Month'));
            $cells .= wf_TableCell(__('Year'));
            $cells .= wf_TableCell(__('All time'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($availStates as $eachStateId => $eachStateDesc) {
                $stateLabel = __($eachStateDesc);
                $stateIcon = $this->getStateIcon($eachStateId);

                $dayCount = isset($dataDay[$eachStateId]['count']) ? $dataDay[$eachStateId]['count'] : 0;
                $weekCount = isset($dataWeek[$eachStateId]['count']) ? $dataWeek[$eachStateId]['count'] : 0;
                $monthCount = isset($dataMonth[$eachStateId]['count']) ? $dataMonth[$eachStateId]['count'] : 0;
                $yearCount = isset($dataYear[$eachStateId]['count']) ? $dataYear[$eachStateId]['count'] : 0;
                $allTimeCount = isset($dataAllTime[$eachStateId]['count']) ? $dataAllTime[$eachStateId]['count'] : 0;

                $cells = wf_TableCell(wf_img_sized($stateIcon, $stateLabel, '10') . ' ' . $stateLabel);
                $cells .= wf_TableCell($dayCount);
                $cells .= wf_TableCell($weekCount);
                $cells .= wf_TableCell($monthCount);
                $cells .= wf_TableCell($yearCount);
                $cells .= wf_TableCell($allTimeCount);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders extended report with optional date range and clickable counts.
     *
     * Supported $options keys:
     *   dateFrom      string  Y-m-d custom range start (optional)
     *   dateTo        string  Y-m-d custom range end (optional)
     *   showDateForm  bool    render two datepickers (default true)
     *   showAdmins    bool    show admin column in drill-down (default false)
     *   linkMode      string  none|users|url (default none)
     *   linkUrl       string  URL template with {ITEMID} and {STATE} placeholders (for linkMode=url)
     *   baseUrl       string  report page URL for form/drill-down links
     *
     * @param array $options
     *
     * @return string
     */
    public function renderExtendedReport($options = array()) {
        $result = '';
        if (!is_array($options)) {
            $options = array();
        }

        $showDateForm = true;
        if (isset($options['showDateForm'])) {
            $showDateForm = ($options['showDateForm']) ? true : false;
        }

        $showAdmins = false;
        if (isset($options['showAdmins'])) {
            $showAdmins = ($options['showAdmins']) ? true : false;
        }
        $options['showAdmins'] = $showAdmins;

        $linkMode = self::LINK_MODE_NONE;
        if (isset($options['linkMode']) and !empty($options['linkMode'])) {
            $linkMode = $options['linkMode'];
        }
        $options['linkMode'] = $linkMode;

        $baseUrl = $this->getExtendedReportBaseUrl($options);
        $options['baseUrl'] = $baseUrl;

        $dateFrom = '';
        $dateTo = '';
        if (isset($options['dateFrom']) and isset($options['dateTo'])) {
            if (!empty($options['dateFrom']) and !empty($options['dateTo'])) {
                $dateFrom = $options['dateFrom'];
                $dateTo = $options['dateTo'];
            }
        }

        if (ubRouting::checkGet(array(self::ROUTE_REPORT_DATE_FROM, self::ROUTE_REPORT_DATE_TO))) {
            $dateFrom = ubRouting::get(self::ROUTE_REPORT_DATE_FROM, 'mres');
            $dateTo = ubRouting::get(self::ROUTE_REPORT_DATE_TO, 'mres');
        } else {
            if (ubRouting::checkPost(array(self::ROUTE_REPORT_DATE_FROM, self::ROUTE_REPORT_DATE_TO))) {
                $dateFrom = ubRouting::post(self::ROUTE_REPORT_DATE_FROM, 'mres');
                $dateTo = ubRouting::post(self::ROUTE_REPORT_DATE_TO, 'mres');
            }
        }

        if (!empty($dateFrom) and !empty($dateTo)) {
            if (!zb_checkDate($dateFrom) or !zb_checkDate($dateTo)) {
                $dateFrom = '';
                $dateTo = '';
            }
        }

        $rangeMode = false;
        if (!empty($dateFrom) and !empty($dateTo)) {
            $rangeMode = true;
        }

        if ($showDateForm) {
            $result .= $this->renderExtendedReportDateForm($baseUrl, $dateFrom, $dateTo);
        }

        if (ubRouting::checkGet(array(self::ROUTE_REPORT_STATE, self::ROUTE_REPORT_BUCKET))) {
            $result .= $this->renderExtendedReportDrillDown($options, $dateFrom, $dateTo);
        } else {
            $result .= $this->renderExtendedReportTable($options, $dateFrom, $dateTo, $rangeMode);
        }

        return($result);
    }

    /**
     * Builds base URL for extended report links (without report-specific routes).
     *
     * @param array $options
     *
     * @return string
     */
    protected function getExtendedReportBaseUrl($options = array()) {
        $result = '';
        if (isset($options['baseUrl']) and !empty($options['baseUrl'])) {
            $result = $options['baseUrl'];
        } else {
            $reportRoutes = array(
                self::ROUTE_REPORT_DATE_FROM => true,
                self::ROUTE_REPORT_DATE_TO => true,
                self::ROUTE_REPORT_STATE => true,
                self::ROUTE_REPORT_BUCKET => true,
                self::ROUTE_ICONSIZE => true,
                self::ROUTE_ITEMID => true,
                self::ROUTE_SCOPE => true,
                self::ROUTE_STATE => true
            );
            $getVars = ubRouting::rawGet();
            if (!empty($getVars)) {
                $url = '?';
                foreach ($getVars as $getVar => $getVal) {
                    if (!isset($reportRoutes[$getVar])) {
                        $url .= $getVar . '=' . $getVal . '&';
                    }
                }
                $result = rtrim($url, '&');
            } else {
                $result = $this->baseUrl;
            }
        }
        return($result);
    }

    /**
     * Renders date range form for extended report.
     *
     * @param string $baseUrl
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return string
     */
    protected function renderExtendedReportDateForm($baseUrl, $dateFrom = '', $dateTo = '') {
        $result = '';
        $inputs = '';
        $query = '';
        $urlParts = parse_url($baseUrl);
        if (isset($urlParts['query']) and !empty($urlParts['query'])) {
            $query = $urlParts['query'];
        }

        if (!empty($query)) {
            $params = array();
            parse_str($query, $params);
            if (!empty($params)) {
                foreach ($params as $key => $val) {
                    if ($key != self::ROUTE_REPORT_DATE_FROM and $key != self::ROUTE_REPORT_DATE_TO and $key != self::ROUTE_REPORT_STATE and $key != self::ROUTE_REPORT_BUCKET) {
                        $inputs .= wf_HiddenInput($key, $val);
                    }
                }
            }
        }

        $presetFrom = (!empty($dateFrom)) ? $dateFrom : curdate();
        $presetTo = (!empty($dateTo)) ? $dateTo : curdate();
        $inputs .= __('Date from') . ' ' . wf_DatePickerPreset(self::ROUTE_REPORT_DATE_FROM, $presetFrom, true) . ' ';
        $inputs .= __('Date to') . ' ' . wf_DatePickerPreset(self::ROUTE_REPORT_DATE_TO, $presetTo, true) . ' ';
        $inputs .= wf_SubmitClassed(__('Show'), 'ubButton','', wf_img('skins/icon_search.png').' '.__('Show'));

        if (!empty($dateFrom) and !empty($dateTo)) {
            $inputs .= ' '.wf_Link($baseUrl, wf_img('skins/icon_cleanup.png').' '.__('All time'), false, 'ubButton');
        }
        $result .= wf_Form('?', 'GET', $inputs, 'glamour');

        $result .= wf_CleanDiv();
        $result .= wf_tag('br');
        return($result);
    }

    /**
     * Returns from/to dates for selected report bucket.
     *
     * @param string $bucket day|week|month|year|all|custom
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    protected function getExtendedReportBucketDates($bucket, $dateFrom = '', $dateTo = '') {
        $result = array('from' => '', 'to' => '');
        switch ($bucket) {
            case 'custom':
                $result['from'] = $dateFrom;
                $result['to'] = $dateTo;
                break;
            case 'day':
                $result['from'] = curdate();
                $result['to'] = curdate();
                break;
            case 'week':
                $result['from'] = date("Y-m-d", strtotime('monday this week'));
                $result['to'] = date("Y-m-d", strtotime('sunday this week'));
                break;
            case 'month':
                $result['from'] = curmonth() . '-01';
                $result['to'] = curmonth() . '-' . date("t");
                break;
            case 'year':
                $result['from'] = curyear() . '-01-01';
                $result['to'] = curyear() . '-12-31';
                break;
            case 'all':
            default:
                $result['from'] = '';
                $result['to'] = '';
                break;
        }
        return($result);
    }

    /**
     * Formats count cell as plain value or drill-down link.
     *
     * @param int    $count
     * @param string $stateId
     * @param string $bucket
     * @param array  $options
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return string
     */
    protected function formatExtendedReportCountCell($count, $stateId, $bucket, $options, $dateFrom = '', $dateTo = '') {
        $result = '0';
        $count = ($count) ? $count : 0;
        $linkMode = isset($options['linkMode']) ? $options['linkMode'] : self::LINK_MODE_NONE;
        $baseUrl = isset($options['baseUrl']) ? $options['baseUrl'] : '';

        if ($count > 0) {
            if ($linkMode == self::LINK_MODE_NONE or empty($baseUrl)) {
                $result = $count;
            } else {
                $url = $baseUrl . '&' . self::ROUTE_REPORT_STATE . '=' . $stateId . '&' . self::ROUTE_REPORT_BUCKET . '=' . $bucket;
                if ($bucket == 'custom' and !empty($dateFrom) and !empty($dateTo)) {
                    $url .= '&' . self::ROUTE_REPORT_DATE_FROM . '=' . $dateFrom . '&' . self::ROUTE_REPORT_DATE_TO . '=' . $dateTo;
                }
                $result = wf_Link($url, $count);
            }
        }
        return($result);
    }

    /**
     * Renders extended report summary table (buckets or custom range).
     *
     * @param array  $options
     * @param string $dateFrom
     * @param string $dateTo
     * @param bool   $rangeMode
     *
     * @return string
     */
    protected function renderExtendedReportTable($options, $dateFrom = '', $dateTo = '', $rangeMode = false) {
        $result = '';
        $messages = new UbillingMessageHelper();
        $availStates = $this->getAllStates();

        if (!empty($availStates)) {
            if ($rangeMode) {
                $dataRange = $this->getReportData($dateFrom, $dateTo);
                $periodLabel = $dateFrom . ' - ' . $dateTo;

                $cells = wf_TableCell(__('Job'), '40%');
                $cells .= wf_TableCell($periodLabel);
                $rows = wf_TableRow($cells, 'row1');

                foreach ($availStates as $eachStateId => $eachStateDesc) {
                    $stateLabel = __($eachStateDesc);
                    $stateIcon = $this->getStateIcon($eachStateId);
                    $rangeCount = isset($dataRange[$eachStateId]['count']) ? $dataRange[$eachStateId]['count'] : 0;

                    $cells = wf_TableCell(wf_img_sized($stateIcon, $stateLabel, '10') . ' ' . $stateLabel);
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($rangeCount, $eachStateId, 'custom', $options, $dateFrom, $dateTo));
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $dateCurrentDay = curdate();
                $dateMonthBegin = curmonth() . '-01';
                $dateMonthEnd = curmonth() . '-' . date("t");
                $dateWeekBegin = date("Y-m-d", strtotime('monday this week'));
                $dateWeekEnd = date("Y-m-d", strtotime('sunday this week'));
                $dateYearBegin = curyear() . '-01-01';
                $dateYearEnd = curyear() . '-12-31';

                $dataDay = $this->getReportData($dateCurrentDay, $dateCurrentDay);
                $dataWeek = $this->getReportData($dateWeekBegin, $dateWeekEnd);
                $dataMonth = $this->getReportData($dateMonthBegin, $dateMonthEnd);
                $dataYear = $this->getReportData($dateYearBegin, $dateYearEnd);
                $dataAllTime = $this->getReportData();

                $cells = wf_TableCell(__('Job'), '30%');
                $cells .= wf_TableCell(__('Day'));
                $cells .= wf_TableCell(__('Week'));
                $cells .= wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Year'));
                $cells .= wf_TableCell(__('All time'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($availStates as $eachStateId => $eachStateDesc) {
                    $stateLabel = __($eachStateDesc);
                    $stateIcon = $this->getStateIcon($eachStateId);

                    $dayCount = isset($dataDay[$eachStateId]['count']) ? $dataDay[$eachStateId]['count'] : 0;
                    $weekCount = isset($dataWeek[$eachStateId]['count']) ? $dataWeek[$eachStateId]['count'] : 0;
                    $monthCount = isset($dataMonth[$eachStateId]['count']) ? $dataMonth[$eachStateId]['count'] : 0;
                    $yearCount = isset($dataYear[$eachStateId]['count']) ? $dataYear[$eachStateId]['count'] : 0;
                    $allTimeCount = isset($dataAllTime[$eachStateId]['count']) ? $dataAllTime[$eachStateId]['count'] : 0;

                    $cells = wf_TableCell(wf_img_sized($stateIcon, $stateLabel, '10') . ' ' . $stateLabel);
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($dayCount, $eachStateId, 'day', $options));
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($weekCount, $eachStateId, 'week', $options));
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($monthCount, $eachStateId, 'month', $options));
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($yearCount, $eachStateId, 'year', $options));
                    $cells .= wf_TableCell($this->formatExtendedReportCountCell($allTimeCount, $eachStateId, 'all', $options));
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders drill-down details for a clicked report count.
     *
     * @param array  $options
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return string
     */
    protected function renderExtendedReportDrillDown($options, $dateFrom = '', $dateTo = '') {
        $result = '';
        $messages = new UbillingMessageHelper();
        $baseUrl = isset($options['baseUrl']) ? $options['baseUrl'] : '';
        $linkMode = isset($options['linkMode']) ? $options['linkMode'] : self::LINK_MODE_NONE;
        $linkUrl = isset($options['linkUrl']) ? $options['linkUrl'] : '';
        $stateId = ubRouting::get(self::ROUTE_REPORT_STATE, 'mres');
        $bucket = ubRouting::get(self::ROUTE_REPORT_BUCKET, 'mres');

        $backUrl = $baseUrl;
        if ($bucket == 'custom' and !empty($dateFrom) and !empty($dateTo)) {
            $backUrl .= '&' . self::ROUTE_REPORT_DATE_FROM . '=' . $dateFrom . '&' . self::ROUTE_REPORT_DATE_TO . '=' . $dateTo;
        }
        $result .= wf_BackLink($backUrl) . wf_delimiter();

        $availStates = $this->getAllStates();
        if (!isset($availStates[$stateId])) {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        } else {
            $bucketDates = $this->getExtendedReportBucketDates($bucket, $dateFrom, $dateTo);
            $reportData = $this->getReportData($bucketDates['from'], $bucketDates['to']);
            $itemIds = array();
            if (isset($reportData[$stateId]['itemids']) and !empty($reportData[$stateId]['itemids'])) {
                $itemIds = $reportData[$stateId]['itemids'];
            }

            if (empty($itemIds)) {
                $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
            } else {
                $stateLabel = __($availStates[$stateId]);
                $stateIcon = $this->getStateIcon($stateId);
                $result .= wf_tag('b', false) . wf_img_sized($stateIcon, $stateLabel, '16') . ' ' . $stateLabel . wf_tag('b', true);
                $result .= wf_delimiter();

                $showAdmins = false;
                if (isset($options['showAdmins']) and $options['showAdmins']) {
                    $showAdmins = true;
                }

                if ($linkMode == self::LINK_MODE_USERS) {
                    if (cfr('USERPROFILE')) {
                        $loginsArr = array();
                        $adminColumn = array();
                        $dateColumn = array();
                        foreach ($itemIds as $itemId => $adminLogin) {
                            $loginsArr[] = $itemId;
                            if ($showAdmins) {
                                $adminColumn[$itemId] = $adminLogin;
                            }
                            $dateColumn[$itemId] = isset($this->allStigmas[$itemId]['date']) ? $this->allStigmas[$itemId]['date'] : '-';
                        }
                        $extraColumns = array();
                        if ($showAdmins) {
                            $extraColumns[__('Admin')] = $adminColumn;
                        }
                        $extraColumns[__('Date')] = $dateColumn;
                        $result .= web_UserArrayShower($loginsArr, $extraColumns);
                    } else {
                        $result .= $messages->getStyledMessage(__('Access denied'), 'error');
                    }
                } else {
                    if ($linkMode == self::LINK_MODE_URL) {
                        $cells = wf_TableCell(__('Object'));
                        if ($showAdmins) {
                            $cells .= wf_TableCell(__('Admin'));
                        }
                        $cells .= wf_TableCell(__('Date'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($itemIds as $itemId => $adminLogin) {
                            $objectLabel = $itemId;
                            if (!empty($linkUrl)) {
                                $objectHref = str_replace(array('{ITEMID}', '{STATE}'), array(urlencode($itemId), urlencode($stateId)), $linkUrl);
                                $objectLabel = wf_Link($objectHref, $itemId);
                            }
                            $itemDate = isset($this->allStigmas[$itemId]['date']) ? $this->allStigmas[$itemId]['date'] : '-';
                            $cells = wf_TableCell($objectLabel);
                            if ($showAdmins) {
                                $cells .= wf_TableCell($adminLogin);
                            }
                            $cells .= wf_TableCell($itemDate);
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    } else {
                        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
                    }
                }
            }
        }
        return($result);
    }
}
