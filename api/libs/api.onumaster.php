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
     * Placeholder for OnuDlp class
     *
     * @var object
     */
    public $dlp = '';

    /**
     * Placeholder for OnuDlp class
     *
     * @var object
     */
    public $elp = '';
    
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
     * Flag to determine if at least one of the ONU actions is enabled
     *
     * @var bool
     */
    public $noActionsEnabled = false;

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
        $tmpBaseONU = new OnuBase($login);
        $onuData = $tmpBaseONU->getDataONU();

        if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
            $this->describe = new OnuDescribe($login);
        }

        if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
            $this->reboot = new OnuReboot($login);
        }
        
        if ($this->altCfg['ONUAUTO_CONFIG_DLP']) {
            $this->dlp = new OnuDlp($login);
        }

        if ($this->altCfg['ONUAUTO_CONFIG_ELP']) {
            $this->elp = new OnuElp($login);
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) and $this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) {
            $this->deregister = new OnuDeregister($login);
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DELETE']) and $this->altCfg['ONUAUTO_CONFIG_DELETE']) {
            $this->delete = new OnuDelete($login);
        }

        $this->userHasONU = (!empty($onuData));
        $this->noActionsEnabled = (empty($this->describe) and empty($this->reboot) and empty($this->deregister) and empty($this->delete));
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

                if ($this->altCfg['ONUAUTO_CONFIG_DLP']) {

                    $windowContents.= $this->dlp->dlpForm() . wf_delimiter(0);
                }

                if ($this->altCfg['ONUAUTO_CONFIG_ELP']) {

                    $windowContents.= $this->elp->elpForm() . wf_delimiter(0);
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

            if ($this->noActionsEnabled) {
                $windowContents.= show_warning(__('Seems no options for describe, reboot, deregister or delete actions are enabled. Check ONUAUTO_CONFIG_* options statuses in alter.ini.'));
            }

            show_window(__('ONU operations for login') . ':' . wf_nbsp(2) . $login, $windowContents);
            show_window('', web_UserControls($login));
        }
    }

}
