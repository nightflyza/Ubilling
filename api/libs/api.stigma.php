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
    const CONFIG_PATH = 'config/';

    /**
     * per-scope configuration files extension
     */
    const CONFIG_EXT = '.ini';

    /**
     * Contains default datasource table name
     */
    const TABLE_DATASOURCE = 'stigma';

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
            $this->scope = $scope;
        } else {
            throw new Exception('EX_EMPTY_SCOPE');
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

            if (isset($raw['stigmasettings'])) {
                $this->type = $raw['stigmasettings'];
                foreach ($raw as $io => $each) {
                    if ($io != 'stigmasettings') {
                        $this->states[$io] = $each['NAME'];
                        $this->icons[$io] = $each['ICON'];
                    }
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

}
