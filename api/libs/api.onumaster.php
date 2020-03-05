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
     * Placeholder for OnuDeregister class
     *
     * @var object
     */
    public $deregister = '';

    /**
     * Placeholder for OnuDelete class
     *
     * @var object
     */
    public $delete = '';

    /**
     * Flag to determine if a particular user has an attached ONU actually
     *
     * @var bool
     */
    public $userHasONU = false;

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
        $onuData = array();

        if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
            $this->describe = new OnuDescribe($login);
            $onuData = $this->describe->getDataONU();
        }

        if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
            $this->reboot = new OnuReboot($login);
            $onuData = $this->reboot->getDataONU();
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) and $this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) {
            $this->deregister = new OnuDeregister($login);
            $onuData = $this->deregister->getDataONU();
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DELETE']) and $this->altCfg['ONUAUTO_CONFIG_DELETE']) {
            $this->delete = new OnuDelete($login);
            $onuData = $this->delete->getDataONU();
        }

        $this->userHasONU = (!empty($onuData));
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
     * @param $login
     *
     * @throws Exception
     */
    public function renderMain($login) {
        $windowContents = '';

        if (!empty($login)) {
            if ($this->userHasONU) {
                if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
                    $windowContents.= $this->describe->DescribeForm($login) . wf_delimiter(0);
                }

                if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
                    $windowContents.= $this->reboot->rebootForm() . wf_delimiter(0);
                }

                if (isset($this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) and $this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) {
                    $windowContents.= $this->deregister->deregForm() . wf_delimiter(0);
                }

                if (isset($this->altCfg['ONUAUTO_CONFIG_DELETE']) and $this->altCfg['ONUAUTO_CONFIG_DELETE']) {
                    $windowContents.= $this->delete->delForm() . wf_delimiter(0);
                }
            } else {
                $windowContents = show_error(__('User has no ONU assigned'));
            }

            show_window(__('ONU operations for login') . ':' . wf_nbsp(2) . $login, $windowContents);
            show_window('', web_UserControls($login));
        }
    }

}
