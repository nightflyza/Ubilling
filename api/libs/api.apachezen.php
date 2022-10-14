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
     * Default error log path
     *
     * @var string
     */
    protected $errorLogPath = '/var/log/httpd-error.log';

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
     * Current zen source
     *
     * @var string
     */
    protected $currentSource = '';

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
     * Dynamic view-port default style
     * 
     * @var string
     */
    protected $renderStyle = 'font-family: monospace;';

    /**
     * Render access log or errorlog flag
     *
     * @var bool
     */
    protected $errorLogFlag = false;

    /**
     * owls are not what they seem
     */
    public function __construct($errorLogFlag = false) {
        $this->setLogType($errorLogFlag);
        $this->loadConfigs();
        $this->setDataSource();
    }

    /**
     * Predefined routes etc..
     */
    const URL_ME = '?module=apachezen';
    const URL_BACK = '?module=report_sysload';
    const URL_CODE = 'https://github.com/nightflyza/Ubilling/';
    const ROUTE_ERRORS = 'errorlog';
    const ROUTE_PHPERR = 'phperrors';
    const ERROR_FILTER = 'line';

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
        $this->cat = $this->billCfg['CAT'];
    }

    /**
     * Sets current instance log type
     * 
     * @param bool $errorLogFlag
     * 
     * return void
     */
    protected function setLogType($errorLogFlag = false) {
        $this->errorLogFlag = $errorLogFlag;
    }

    /**
     * Sets alternative datasource path
     * 
     * @return void
     */
    protected function setDataSource() {
        //access logs
        if (!file_exists($this->logPath)) {
            $alternatePath = '/var/log/apache2/access.log';
            if (file_exists($alternatePath)) {
                //Debian Linux? 
                $this->logPath = $alternatePath;
            }
        }
        //errors log
        if (!file_exists($this->errorLogPath)) {
            $alternateErrorsPath = '/var/log/apache2/error.log';
            if (file_exists($alternateErrorsPath)) {
                $this->errorLogPath = $alternateErrorsPath;
            }
        }
    }

    /**
     * Checks is datasource file exists
     * 
     * @param bool $errorLog
     * 
     * @return bool
     */
    protected function dataSourceExists($errorLog = false) {
        $result = false;
        if ($errorLog) {
            if (file_exists($this->errorLogPath)) {
                $result = true;
            }
        } else {
            if (file_exists($this->logPath)) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Renders the few last lines from data source.
     * 
     * @param bool $errorLog
     * 
     * @return string
     */
    public function render() {
        $result = '';
        $readSource = ($this->errorLogFlag) ? $this->errorLogPath : $this->logPath;
        if ($this->dataSourceExists($this->errorLogFlag)) {
            $this->currentSource = $readSource;
            $filters = $this->grep . ' -v ' . $this->flowId; //ignore itself
            $filters .= '| ' . $this->grep . ' -v fwtbt'; //ignore For Whom The Bell Tolls
            $command = $this->tail . ' -n ' . $this->linesRead . ' ' . $readSource . ' | ' . $filters . ' | ' . $this->tail . ' -n ' . $this->linesRender;
            $resultRaw = shell_exec($command);
            if (!empty($resultRaw)) {
                $rows = '';
                $resultRaw = explodeRows($resultRaw);
                $resultRaw = array_reverse($resultRaw);
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
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('File not exist') . ': ' . $readSource, 'error');
        }
        return($result);
    }

    /**
     * Renders latest PHP errors in scripts
     * 
     * @return string
     */
    public function renderPHPErrors() {
        $result = '';
        $readSource = ($this->errorLogFlag) ? $this->errorLogPath : $this->logPath;
        if ($this->dataSourceExists(true)) {
            $this->currentSource = $readSource;
            $filters = ' ' . $this->grep . ' ' . self::ERROR_FILTER;
            $command = $this->tail . ' -n ' . ($this->linesRead * 10) . ' ' . $readSource . ' | ' . $filters . ' | ' . $this->tail . ' -n ' . $this->linesRender;
            $resultRaw = shell_exec($command);
            $stripPaths = array(
                '/usr/local/www/apache22',
                '/usr/local/www/apache24',
                '/var/www/html/',
                '/data/',
                'dev/ubilling/',
                'billing/'
            );
            if (!empty($resultRaw)) {
                $rows = '';
                $date = '';
                $type = '';
                $client = '';
                $message = '';
                $resultRaw = explodeRows($resultRaw);
                $resultRaw = array_reverse($resultRaw);
                if (!empty($resultRaw)) {
                    foreach ($resultRaw as $io => $eachLine) {
                        if (!empty($eachLine)) {
                            preg_match('~^\[(.*?)\]~', $eachLine, $date);
                            preg_match('~\] \[([a-z]*?)\] \[~', $eachLine, $type);
                            preg_match('~\] \[client ([0-9\.]*)\]~', $eachLine, $client);
                            preg_match('~\] (.*)$~', $eachLine, $message);
                            $cleanMessage = $message[1];
                            $cleanMessage = str_replace('[' . $type[1] . ']', '', $cleanMessage);
                            $cleanMessage = str_replace('[client ' . $client[1] . ']', '', $cleanMessage);
                            foreach ($stripPaths as $ia => $eachStripPath) {
                                $cleanMessage = str_replace($eachStripPath, '', $cleanMessage);
                            }

                            //tryin to detect billing code line
                            if (ispos($cleanMessage, 'in') AND ispos($cleanMessage, 'on line')) {
                                preg_match('!in (.*?) on!si', $cleanMessage, $sourceFiles);
                                preg_match('!on line (.*?),!si', $cleanMessage, $codeLines);
                                $lineOfCode = '';
                                if (isset($codeLines[1])) {
                                    $lineOfCode = ubRouting::filters($codeLines[1], 'int');
                                }
                                if (isset($sourceFiles[1])) {
                                    if (file_exists($sourceFiles[1])) {
                                        $sourceUrl = self::URL_CODE . 'blob/master/' . $sourceFiles[1];
                                        if (!empty($lineOfCode)) {
                                            $sourceUrl .= '#L' . $lineOfCode;
                                        }
                                        $sourceLink = wf_Link($sourceUrl, $sourceFiles[1]);
                                        $cleanMessage = str_replace($sourceFiles[1], $sourceLink, $cleanMessage);
                                    }
                                }
                            }

                            $cells = '';
                            if (!empty($date)) {
                                $timeStamp = strtotime($date[1]);
                                $cleanDate = date("Y-m-d H:i:s", $timeStamp);
                                $cells .= wf_TableCell($cleanDate);
                                $cells .= wf_TableCell(htmlentities(strip_tags($client[1])));
                            }

                            $cells .= wf_TableCell($cleanMessage);
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                    }
                }
                $result .= wf_TableBody($rows, '100%', 0, '');
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('File not exist') . ': ' . $readSource, 'error');
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

    /**
     * Just returns default module controls
     * 
     * @return string
     */
    public function controls() {
        $result = '';
        $result .= wf_BackLink(self::URL_BACK) . ' ';
        $result .= wf_Link(self::URL_ME, wf_img('skins/zen.png') . ' Access ' . __('Zen'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ERRORS . '=true', wf_img('skins/zen.png') . ' Error ' . __('Zen'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_PHPERR . '=true', wf_img('skins/icon_php.png') . ' ' . __('PHP errors'), false, 'ubButton');
        return($result);
    }

    /**
     * Returns current data source
     * 
     * @return string
     */
    public function getCurrentSource() {
        return($this->currentSource);
    }

}
