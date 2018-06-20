<?php

/**
 * Class for managing ONU/ONT. 
 * Change/Add/Delete description. Only for BDCOM. 
 * Reboot ONU. Only for BDCOM. 
 * Registering ONU/ONT GePON + GPON. Only for ZTE.
 */
class OnuMaster {

    /**
     * Placeholder for OnuDescribe class
     * 
     * @var object
     */
    public $describe = '';

    /**
     * Placeholder for OnuReboot class
     * 
     * @var object
     */
    public $reboot = '';

    /**
     * Contains system alter config
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Base constructor for class
     * 
     * @return void
     */
    public function __construct($login) {
        $this->loadAlter();
        if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
            $this->describe = new OnuDescribe($login);
        }
        if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
            $this->reboot = new OnuReboot($login);
        }
    }

    //data loader function

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    //view function
    /**
     * Renders main window for managing ONU.
     * 
     * @param string $login
     * 
     * @return void
     */
    public function renderMain($login) {
        if (!empty($login)) {
            if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
                show_window('', $this->describe->DescribeForm($login));
            }
            if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
                show_window('', $this->reboot->RebootForm());
            }
        }
    }

}
