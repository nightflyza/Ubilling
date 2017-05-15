<?php

class UbillingUpdateManager {

    /**
     * Systema alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System mysql.ini config as key=>value
     *
     * @var array
     */
    protected $mySqlCfg = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Available mysql dumps for apply
     *
     * @var array
     */
    protected $allDumps = array();

    const DUMPS_PATH = 'content/updates/sql';

    /**
     * Creates new update manager instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->loadDumps();
    }

    /**
     * Loads all required config files into protected props for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->mySqlCfg = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
    }

    /**
     * Loads available mysql dumps filenames into protected prop
     * 
     * @return void
     */
    protected function loadDumps() {
        $this->allDumps = rcms_scandir(self::DUMPS_PATH, '*.sql');
        debarr($this->allDumps);
    }

}
?>