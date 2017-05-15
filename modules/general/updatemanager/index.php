<?php

if (cfr('ROOT')) {

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
         * Creates new update manager instance
         * 
         * @return void
         */
        public function __construct() {
            $this->loadConfigs();
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

    }
    
    $updateManager=new UbillingUpdateManager();

} else {
    show_error(__('Access denied'));
}
?>


