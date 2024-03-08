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
     * Placeholder for OnuElp class
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
     * Must be set to false with any of available actions/options.
     *
     * @var bool
     */
    public $noActionsEnabled = true;

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';


    /**
     * Base constructor for class
     * 
     * @return void
     */
    public function __construct($login) {
        $this->initMessages();
        $this->loadAlter();

        $tmpBaseONU = new OnuBase($login);
        $onuData = $tmpBaseONU->getDataONU();

        if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
            $this->describe = new OnuDescribe($login);
            $this->noActionsEnabled = false;
        }

        if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
            $this->reboot = new OnuReboot($login);
            $this->noActionsEnabled = false;
        }

        if ($this->altCfg['ONUAUTO_CONFIG_DLP']) {
            $this->dlp = new OnuDlp($login);
            $this->noActionsEnabled = false;
        }

        if ($this->altCfg['ONUAUTO_CONFIG_ELP']) {
            $this->elp = new OnuElp($login);
            $this->noActionsEnabled = false;
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) and $this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) {
            $this->deregister = new OnuDeregister($login);
            $this->noActionsEnabled = false;
        }

        if (isset($this->altCfg['ONUAUTO_CONFIG_DELETE']) and $this->altCfg['ONUAUTO_CONFIG_DELETE']) {
            $this->delete = new OnuDelete($login);
            $this->noActionsEnabled = false;
        }

        $this->userHasONU = (!empty($onuData));
    }


    /**
     * Initializes the messages object.
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }


    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }


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
            if ($this->noActionsEnabled) {
                $windowContents .= $this->messages->getStyledMessage(__('Seems no options for describe, reboot, deregister or delete actions are enabled. Check ONUAUTO_CONFIG_* options statuses in alter.ini.'), 'warning');
            }

            if ($this->userHasONU) {
                if ($this->altCfg['ONUAUTO_CONFIG_DESCRIBE']) {
                    $windowContents .= $this->describe->DescribeForm($login) . wf_delimiter(0);
                }

                if ($this->altCfg['ONUAUTO_CONFIG_DLP']) {
                    $windowContents .= $this->dlp->dlpForm() . wf_delimiter(0);
                }

                if ($this->altCfg['ONUAUTO_CONFIG_ELP']) {
                    $windowContents .= $this->elp->elpForm() . wf_delimiter(0);
                }

                if ($this->altCfg['ONUAUTO_CONFIG_REBOOT']) {
                    $windowContents .= $this->reboot->rebootForm() . wf_delimiter(0);
                }

                if (isset($this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) and $this->altCfg['ONUAUTO_CONFIG_DEREGISTER']) {
                    $windowContents .= $this->deregister->deregForm() . wf_delimiter(0);
                }

                if (isset($this->altCfg['ONUAUTO_CONFIG_DELETE']) and $this->altCfg['ONUAUTO_CONFIG_DELETE']) {
                    $windowContents .= $this->delete->delForm() . wf_delimiter(0);
                }
            } else {
                $windowContents .= $this->messages->getStyledMessage(__('User has no ONU assigned'), 'error');
            }

            show_window(__('ONU operations for login') . ':' . wf_nbsp(2) . $login, $windowContents);
            show_window('', web_UserControls($login));
        }
    }
}
