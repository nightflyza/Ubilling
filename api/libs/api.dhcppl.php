<?php

/**
 * User DHCP log viewer
 */
class DHCPPL {

    /**
     * Contains billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Contains alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Default datasource file to read
     *
     * @var string
     */
    protected $logPath = '/var/log/messages';

    /**
     * Default flow identifier to ignore self requests
     *
     * @var string
     */
    protected $flowId = 'pldhzjcb';

    /**
     * Count of lines to render in viewport
     *
     * @var int
     */
    protected $linesRender = 30;

    /**
     * Default container refresh timeout in ms.
     *
     * @var int
     */
    protected $timeout = 1000;

    /**
     * Contains system grep path
     *
     * @var string
     */
    protected $grep = '';

    /**
     * Contains system tail path
     *
     * @var string
     */
    protected $tail = '';

    /**
     * Contains system cat path
     *
     * @var string
     */
    protected $cat = '';

    /**
     * Contains sudo command path
     *
     * @var string
     */
    protected $sudo = '';

    /**
     * DHCP option82 enabled flag
     * 
     * @var bool
     */
    protected $opt82Flag = false;

    /**
     * Contains current instance user login
     * 
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains current instance user IP
     * 
     * @var string
     */
    protected $userIp = '';

    /**
     * Contains current instance user MAC
     * 
     * @var string
     */
    protected $userMac = '';

    /**
     * Dynamic view-port default style
     * 
     * @var string
     */
    protected $renderStyle = 'font-family: monospace;';

    /**
     * System messages helper instance
     * 
     * @var object
     */
    protected $messages = '';

    /**
     * Black wings will grow when you`re dead
     * 
     * @param string $userLogin
     * @param string $userIp
     * @param string $userMac
     * 
     */
    public function __construct($userLogin = '', $userIp = '', $userMac = '') {
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions($userLogin, $userIp, $userMac);
    }

    /**
     * Predefined routes etc..
     */
    const URL_ME = '?module=pl_dhcp';

    /**
     * Preloads required configs for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billCfg = $ubillingConfig->getBilling();
        $this->altCfg = $ubillingConfig->getAlter();
        $this->sudo = $this->billCfg['SUDO'];
        $this->grep = $this->billCfg['GREP'];
        $this->tail = $this->billCfg['TAIL'];
        $this->cat = $this->billCfg['CAT'];
        $this->opt82Flag = $this->altCfg['OPT82_ENABLED'];
        $this->logPath = $ubillingConfig->getAlterParam('NMLEASES');
    }

    /**
     * Sets instance properties
     * 
     * @param string $userLogin
     * @param string $userIp
     * @param string $userMac
     * 
     * @return void
     */
    protected function setOptions($userLogin = '', $userIp = '', $userMac = '') {
        $this->userLogin = $userLogin;
        $this->userIp = $userIp;
        $this->userMac = $userMac;
    }

    /**
     * Inits system message helper instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Checks is datasource file exists
     * 
     * @return bool
     */
    protected function dataSourceExists() {
        $result = false;
        if (file_exists($this->logPath)) {
            $result = true;
        }
        return($result);
    }

    /**
     * Returns parsed data source records as string
     * 
     * @return string
     */
    protected function getLogData() {
        $result = '';
        if ($this->userIp AND $this->userMac) {
            $macParse = $this->userMac;
            $grepPath = $this->grep;
            if ($this->opt82Flag) {
                $grepPath = $this->grep . ' -E';
                $macParse = '"( ' . $this->userIp . '(:)? )|(' . $this->userMac . ')"';
            }
            $command = $this->sudo . ' ' . $this->cat . ' ' . $this->logPath . ' | ' . $grepPath . ' ' . $macParse . ' | ' . $this->tail . '  -n ' . $this->linesRender;
            $result = shell_exec($command);
        }
        return($result);
    }

    /**
     * Returns user mac label
     * 
     * @return string
     */
    public function getMacLabel() {
        $result = '';
        $result = $this->messages->getStyledMessage(wf_tag('h2') . __('Current MAC') . ': ' . $this->userMac . wf_tag('h2', true), 'info');
        return($result);
    }

    /**
     * Renders the parsed log data from data source.
     * 
     * @return string
     */
    public function render() {
        /**
         * Commence, Destroy, Exploit, Enjoy, A world to spoil, The pistols recoil
         */
        $result = '';
        if ($this->dataSourceExists()) {
            $resultRaw = $this->getLogData();
            if (!empty($resultRaw)) {
                $rows = '';
                $resultRaw = explodeRows($resultRaw);
                $resultRaw= array_reverse($resultRaw);
                if (!empty($resultRaw)) {
                    foreach ($resultRaw as $io => $eachLine) {
                        if (!empty($eachLine)) {
                            $cells = wf_TableCell(htmlentities(strip_tags($eachLine)));
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '', 'style="' . $this->renderStyle . '"');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('File not exist') . ': ' . $this->logPath, 'error');
        }
        /**
         * Command, Ignite, Commence, The Fight, A strike of spite, The rush of the fright
         */
        return($result);
    }

    /**
     * Returns current container flowID
     * 
     * @return string
     */
    public function getFlowId() {
        return($this->flowId);
    }

    /**
     * Returns current instance refresh timeout
     * 
     * @return int
     */
    public function getTimeout() {
        return($this->timeout);
    }
}
