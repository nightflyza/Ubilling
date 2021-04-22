<?php

/**
 * Just meditative web-log viewer
 */
class ApacheZen {

    /**
     * Contains billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Default datasource file to read
     *
     * @var string
     */
    protected $logPath = '/var/log/httpd-access.log';

    /**
     * Default flow identifier to ignore self requests
     *
     * @var string
     */
    protected $flowId = 'apzjcb';

    /**
     * Count of lines to read from log
     * 
     * @var int
     */
    protected $linesRead = 500;

    /**
     * Count of lines to render in viewport
     *
     * @var int
     */
    protected $linesRender = 40;

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
     * owls are not what they seem
     */
    public function __construct() {
        $this->loadConfigs();
    }

    /**
     * Predefined routes etc..
     */
    const URL_ME = '?module=apachezen';

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
        $this->grep = $this->billCfg['GREP'];
        $this->tail = $this->billCfg['TAIL'];
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
     * Renders the few last lines from data source.
     * 
     * @return string
     */
    public function render() {
        $result = '';
        if ($this->dataSourceExists()) {
            $filters = $this->grep . ' -v ' . $this->flowId; //ignore itself
            $filters .= '| ' . $this->grep . ' -v fwtbt'; //ignore For Whom The Bell Tolls
            $command = $this->tail . ' -n ' . $this->linesRead . ' ' . $this->logPath . ' | ' . $filters . ' | ' . $this->tail . ' -n ' . $this->linesRender;
            $resultRaw = shell_exec($command);
            if (!empty($resultRaw)) {
                $rows = '';
                $resultRaw = explodeRows($resultRaw);
                $resultRaw = array_reverse($resultRaw);
                if (!empty($resultRaw)) {
                    foreach ($resultRaw as $io => $eachLine) {
                        $cells = wf_TableCell($eachLine);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '', 'style="font-family: monospace;"');
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('File not exist') . ': ' . $this->logPath, 'error');
        }
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
