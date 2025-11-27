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
     * Contains sudo command path
     *
     * @var string
     */
    protected $sudo = '';

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
    const URL_TASKBAR='?module=taskbar';
    const URL_CODE = 'https://github.com/nightflyza/Ubilling/';
    const ROUTE_ERRORS = 'errorlog';
    const ROUTE_PHPERR = 'phperrors';
    const ERROR_FILTER = 'on line';

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
        $this->sudo = $this->billCfg['SUDO'];
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
            $command = $this->sudo.' '.$this->tail . ' -n ' . $this->linesRead . ' ' . $readSource . ' | ' . $filters . ' | ' . $this->tail . ' -n ' . $this->linesRender;
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
            $filters = ' ' . $this->grep . ' "' . self::ERROR_FILTER . '"';
            $command = $this->sudo.' '.$this->tail . ' -n ' . ($this->linesRead * 10) . ' ' . $readSource . ' | ' . $filters . ' | ' . $this->tail . ' -n ' . $this->linesRender;
            $resultRaw = shell_exec($command);
            $stripPaths = array(
                '/usr/local/www/apache22',
                '/usr/local/www/apache24',
                '/var/www/html/',
                '/data/',
                'dev/ubilling/',
                'billing/'
            );

            $hlights = array(
                'PHP Notice' => 'de6666',
                'PHP Warning' => 'd04545',
                'PHP Parse error' => 'ae0000',
                'PHP Fatal error' => 'e00808'
            );

            if (!empty($resultRaw)) {
                $rows = '';
                $resultRaw = explodeRows($resultRaw);
                $resultRaw = array_reverse($resultRaw);
                if (!empty($resultRaw)) {
                    foreach ($resultRaw as $io => $eachLine) {
                        if (!empty($eachLine)) {
                            $cleanMessage = strip_tags($eachLine);
                            foreach ($stripPaths as $ia => $eachStripPath) {
                                $cleanMessage = str_replace($eachStripPath, '', $cleanMessage);
                            }

                            //tryin to detect billing code line
                            if (ispos($cleanMessage, 'in') AND ispos($cleanMessage, 'on line')) {
                                preg_match('!in (.*?) on!si', $cleanMessage, $sourceFiles);
                                preg_match('!on line (.*?),!si', $cleanMessage, $codeLines);
                                $lineOfCode = '';
                                if (isset($codeLines[1])) {
                                    $codeLines = explode(' ', $codeLines[1]);
                                    $codeLines = $codeLines[0];
                                    $lineOfCode = ubRouting::filters($codeLines, 'int');
                                }

                                if (isset($sourceFiles[1])) {
                                    if (file_exists($sourceFiles[1])) {
                                        $sourceUrl = self::URL_CODE . 'blob/master/' . $sourceFiles[1];
                                        $sourceLink = wf_Link($sourceUrl, $sourceFiles[1], false, '', 'target="_BLANK"');
                                        $cleanMessage = str_replace($sourceFiles[1], $sourceLink, $cleanMessage);

                                        if (!empty($lineOfCode)) {
                                            $lineUrl = $sourceUrl . '#L' . $lineOfCode;
                                            $lineMark = 'on line ' . $lineOfCode . ',';
                                            $lineLink = wf_Link($lineUrl, wf_tag('u') . $lineMark . wf_tag('u', true), false, '', 'target="_BLANK"');
                                            $cleanMessage = str_replace($lineMark, $lineLink, $cleanMessage);
                                        }
                                    }
                                }
                            }

                            //coloring results
                            foreach ($hlights as $eachString => $eachColor) {
                                $cleanMessage = $this->colorize($cleanMessage, $eachString, $eachColor);
                            }
                            $cells = wf_TableCell($cleanMessage);
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
     * Paints some subStr into some color if its appears in text
     * 
     * @param string $text
     * @param string $subStr
     * @param string $color
     * 
     * @return string
     */
    protected function colorize($text, $subStr, $color) {
        $result = '';
        if (!empty($text) AND ! empty($subStr) AND ! empty($color)) {
            $colorizedSubstr = wf_tag('font', false, '', 'style="color:#' . $color . ';"');
            $colorizedSubstr .= $subStr;
            $colorizedSubstr .= wf_tag('font', true);
            $result = str_replace($subStr, $colorizedSubstr, $text);
        } else {
            $result = $text;
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
        $backUrl = self::URL_BACK;
        if (ubRouting::get('back') == 'tb') {
            $backUrl = self::URL_TASKBAR;
        }
        $result .= wf_BackLink($backUrl) . ' ';
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
