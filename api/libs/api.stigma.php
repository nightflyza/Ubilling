<?php

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
     * Default icons file extension
     */
    const ICON_EXT = '.png';

    /**
     * Default state icons path
     */
    const ICON_PATH = 'skins/stigma/';

    /**
     * Stigma configuration files path
     */
    const CONFIG_PATH = 'config/stigma/';

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
     * Some URLS/routes etc
     */
    const ROUTE_SCOPE = 'stscope';
    const ROUTE_ITEMID = 'stitemid';
    const ROUTE_STATE = 'stchstate';
    const ROUTE_ICONSIZE = 'stis';

    /**
     * Creates new stigma on selected scope
     * 
     * @param string $scope
     */
    public function __construct($scope) {
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
        $this->loadStigmas();
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
     * Preloads current scope settings from config file
     * 
     * @throws Exception
     * 
     * @return void
     */
    protected function loadConfig() {
        $confName = strtolower($this->scope);
        $confFullPath = self::CONFIG_PATH . $confName . self::CONFIG_EXT;
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
     * @return void
     */
    protected function loadStigmas() {
        $this->stigmaDb->where('scope', '=', $this->scope);
        $this->allStigmas = $this->stigmaDb->getAll('itemid');
    }

    /**
     * Renders stigma current state and editing interface (prototype)
     * 
     * @param string $itemId
     * @param int $size
     * 
     * @return string
     */
    public function render($itemId, $size = '') {
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
        }

        $containerName = 'ajStigma' . $this->scope . '_' . $itemId;
        $result .= wf_AjaxLoader(true);
        $result .= wf_tag('div', false, '', 'id="' . $containerName . '"');
        foreach ($this->states as $stateId => $stateName) {
            $stateLabel = __($stateName);
            $controlClass = 'dashtask';
            if (isset($currentStates[$stateId])) {
                $controlClass .= ' ' . $this->activeClass;
            }

            $stateIcon = self::ICON_PATH . @$this->icons[$stateId] . self::ICON_EXT;

            if (!file_exists($stateIcon)) {
                $stateIcon = self::ICON_PATH . 'default' . self::ICON_EXT;
            }

            $controlUrl = $this->baseUrl . '&' . self::ROUTE_SCOPE . '=' . $this->scope . '&' . self::ROUTE_ITEMID . '=' . $itemId . '&' . self::ROUTE_STATE . '=' . $stateId;
            if ($size) {
                $controlUrl .= '&' . self::ROUTE_ICONSIZE . '=' . $size;
            }
            $controlLink = wf_AjaxLink($controlUrl, wf_img_sized($stateIcon, $stateLabel, $size), $containerName);
            $result .= wf_tag('div', false, $controlClass, '');
            $result .= $controlLink;
            $result .= wf_delimiter(0) . $stateLabel;
            $result .= wf_tag('div', true);
        }


        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();

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
            }
        }
        return($result);
    }

    /**
     * AJAX callbacks processing controller
     * 
     * @return void
     */
    public function stigmaController() {
        if (ubRouting::checkGet(array(self::ROUTE_SCOPE, self::ROUTE_ITEMID, self::ROUTE_STATE))) {
            //my scope?
            if ($this->scope == ubRouting::get(self::ROUTE_SCOPE)) {
                $stigmaCtrl = new Stigma(ubRouting::get(self::ROUTE_SCOPE));
                $stigmaCtrl->saveState(ubRouting::get(self::ROUTE_ITEMID), ubRouting::get(self::ROUTE_STATE));
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
                }

                //saving new item state to database
                $this->setState($itemId, $newStatesString);
            }
        } else {
            //new stigma
            $this->createState($itemId, $state);
        }

        //update internal structs
        $this->loadStigmas();
    }

}
