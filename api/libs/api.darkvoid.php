<?php

/**
 * Notification area aka DarkVoid class
 */
class DarkVoid {

    /**
     * 
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains alerts cache
     *
     * @var string
     */
    protected $alerts = '';

    /**
     * Contains non-cachable alerts & notifications
     *
     * @var string
     */
    protected $dynamicArea = '';

    /**
     * Contains default cache timeout in minutes
     *
     * @var int
     */
    protected $cacheTime = 10;

    /**
     * UbillingConfig object placeholder
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Array of modules that must be skipped on alert updates
     *
     * @var array
     */
    protected $skipOnModules = array();

    /**
     * Contains current module
     *
     * @var string
     */
    protected $currentModule = '';

    /**
     * Cache storage path
     */
    const CACHE_PATH = 'exports/';

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'darkvoid.';

    /**
     * External alerts providers path
     */
    const PROVIDERS_PATH = 'modules/darkvoid/';

    public function __construct() {
        if (LOGGED_IN) {
            $this->setCurrentModule();
            $this->setModSkip();
            $this->setMyLogin();
            $this->loadAlter();
            $this->loadAlerts();
            $this->loadDynamicArea();
        }
    }

    /**
     * Sets current instance current route module name
     *
     * @return void
     */
    protected function setCurrentModule() {
        if (ubRouting::checkGet('module')) {
            $this->currentModule = ubRouting::get('module', 'vf');
        }
    }

    /**
     * Sets modules array to be skipped on alert updates to prevent DB ops
     * 
     * @return void
     */
    protected function setModSkip() {
        $this->skipOnModules = array('turbosms', 'senddog', 'remoteapi', 'updatemanager','sysconf');
        $this->skipOnModules = array_flip($this->skipOnModules);
    }

    /**
     * Loads alerts from per-user cache or from database if needed
     * 
     * @return void
     */
    protected function loadAlerts() {
        $cacheName = self::CACHE_PATH . self::CACHE_PREFIX . $this->myLogin;
        $cacheTime = time() - ($this->cacheTime * 60); //in minutes

        $updateCache = false;
        if (file_exists($cacheName)) {
            $updateCache = false;
            if ((filemtime($cacheName) > $cacheTime)) {
                $updateCache = false;
            } else {
                $updateCache = true;
            }
        } else {
            $updateCache = true;
        }

        if ($updateCache) {
            //ugly hack to prevent alerts update on tsms and senddog modules
            if (!empty($this->currentModule)) {
                if (!isset($this->skipOnModules[$this->currentModule])) {
                    //renew cache
                    $this->updateAlerts();
                }
            } else {
                //renew cache
                $this->updateAlerts();
            }
        } else {
            //read from cache
            @$this->alerts = file_get_contents($cacheName);
        }
    }

    /**
     * Loads dynamic, non-cachable dark-void content
     *
     * @return void
     */
    protected function loadDynamicArea() {
        //Taskbar quick search
        if (isset($this->altCfg['TB_QUICKSEARCH_ENABLED'])) {
            if ($this->altCfg['TB_QUICKSEARCH_ENABLED']) {
                if (@$this->altCfg['TB_QUICKSEARCH_INLINE'] == 1) {
                    if ($this->currentModule == 'taskbar' or empty($this->currentModule)) {
                        $this->dynamicArea .= web_TaskBarQuickSearchForm();
                        //overriding default style
                        $this->dynamicArea .= wf_tag('style');
                        $this->dynamicArea .= '
                        .tbqsearchform {
                                float: right;
                                margin-right: 0px;
                                margin-left: 5px;
                                position: relative;
                                display: flex;
                                align-items: center;
                        }
                        ';
                        $this->dynamicArea .= wf_tag('style', true);
                    }
                }
            }
        }
    }

    /**
     * Sets private login property
     * 
     * @return
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads global alter.ini config into protected property
     * 
     * @global type $ubillingConfig
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['DARKVOID_CACHETIME'])) {
            if ($this->altCfg['DARKVOID_CACHETIME']) {
                $this->cacheTime = $this->altCfg['DARKVOID_CACHETIME'];
            }
        }
    }

    /**
     * Renders available and enabled alerts into  DarkVoid notification area
     * 
     * @return void
     */
    protected function updateAlerts() {
        //darkvoid is not disabled globally?
        if (empty($this->altCfg['DARKVOID_DISABLED'])) {
            //external darkvoid providers
            $this->appendExternalAlerts();

            //appending some debug string to validate cache expire
            $this->alerts .= '<!-- DarkVoid saved: ' . curdatetime() . ' -->';

            //saving per-admin cache data
            file_put_contents(self::CACHE_PATH . self::CACHE_PREFIX . $this->myLogin, $this->alerts);
        }
    }

    /**
     * Appends external alerts providers output
     *
     * @return void
     */
    protected function appendExternalAlerts() {
        $providers = array();
        if (is_dir(self::PROVIDERS_PATH)) {
            $providers = rcms_scandir(self::PROVIDERS_PATH, '*.php', 'file');
        }

        if (!empty($providers)) {
            foreach ($providers as $io => $providerFile) {
                $providerPath = self::PROVIDERS_PATH . $providerFile;
                if (file_exists($providerPath)) {
                    $darkVoidContext = array(
                        'altCfg' => $this->altCfg,
                        'ubConfig' => $this->ubConfig,
                        'myLogin' => $this->myLogin,
                        'currentModule' => $this->currentModule
                    );

                    $providerResult = include($providerPath);
                    if (is_string($providerResult)) {
                        if (!empty($providerResult)) {
                            $this->alerts .= $providerResult;
                        }
                    } else {
                        $failureMsg= __('DarkVoid module failed').': ' . $providerFile .' '. __('returned').' ' . gettype($providerResult) . '';
                        $this->alerts .= wf_img('skins/dvfail32.png', $failureMsg);
                    }
                }
            }
        }
    }

    /**
     * Returns raw alerts data
     * 
     * @return string
     */
    public function render() {
        $result = '';
        //darkvoid is not disabled globally?
        if (empty($this->altCfg['DARKVOID_DISABLED'])) {
            $result = $this->alerts;
            $result .= $this->dynamicArea;
        }
        return ($result);
    }

    /**
     * Flushes all or specified user alert cachesysconf
     * 
     * @param string $login Optional existing user login
     * 
     * @return void
     */
    public function flushCache($login = '') {
        if (empty($login)) {
            $allCache = rcms_scandir(self::CACHE_PATH, self::CACHE_PREFIX . '*', 'file');
            if (!empty($allCache)) {
                foreach ($allCache as $io => $each) {
                    @unlink(self::CACHE_PATH . $each);
                }
            }
        } else {
            if (file_exists(self::CACHE_PATH . self::CACHE_PREFIX . $login)) {
                @unlink(self::CACHE_PATH . self::CACHE_PREFIX . $login);
            }
        }
    }
}
