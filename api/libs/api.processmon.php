<?php

/**
 * StarDust-managed process monitor implementation
 */
class ProcessMon {

    /**
     * Stardust process menager instance placeholder
     *
     * @var object
     */
    protected $stardust = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all process stats as processName=>data
     *
     * @var array
     */
    protected $allProcess = array();

    /**
     * Contains default system ps path
     *
     * @var string
     */
    protected $psPath = '/bin/ps';

    /**
     * some predefined stuff like routes, etc here
     */
    const URL_ME = '?module=processmon';
    const ROUTE_ALL = 'showall';
    const ROUTE_ACTIVE = 'onlyactive';
    const ROUTE_FINISHED = 'finished';
    const ROUTE_ZENMODE = 'processzen';
    const ROUTE_STOP = 'shutdownprocess';
    const ROUTE_BRUTAL = 'brutality';
    const ZEN_TIMEOUT = 1000;

    public function __construct() {
        $this->initMessages();
        $this->initStarDust();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits process manager for further usage
     * 
     * @return void
     */
    protected function initStarDust() {
        $this->stardust = new StarDust();
    }

    /**
     * Loads all process states into protected property
     * 
     * @return void
     */
    protected function loadAllProcessStates() {
        $this->allProcess = $this->stardust->getAllStates();
    }

    /**
     * Renders default module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $backUrl = '?module=report_sysload';
        if (ubRouting::get('back') == 'tb') {
            $backUrl = '?module=taskbar';
        }
        $result .= wf_BackLink($backUrl);
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ALL . '=true', wf_img('skins/icon_thread.png') . ' ' . __('All'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ACTIVE . '=true', wf_img('skins/icon_active.gif') . ' ' . __('Active'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FINISHED . '=true', wf_img('skins/icon_inactive.gif') . ' ' . __('Finished'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZENMODE . '=true', wf_img('skins/zen.png') . ' ' . __('Zen'), false, 'ubButton') . ' ';
        return ($result);
    }

    /**
     * Returns all running PID-s array as pid=>processString
     * 
     * @return array
     */
    protected function getRunningPids() {
        $result = array();
        $rawResult = shell_exec($this->psPath . ' ax');
        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            foreach ($rawResult as $io => $eachLine) {
                $eachLine = trim($eachLine);
                $rawLine = $eachLine;
                $eachLine = explode(' ', $eachLine);
                if (isset($eachLine[0])) {
                    $eachPid = $eachLine[0];
                    if (is_numeric($eachPid)) {
                        $result[$eachPid] = $rawLine;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Stops running process by its name
     * 
     * @global object $ubillingConfig
     * @param string $processName
     * @param bool $brutal
     * 
     * @return void/string
     */
    public function stopProcess($processName, $brutal = false) {
        global $ubillingConfig;
        $result = '';
        $this->loadAllProcessStates();
        if (isset($this->allProcess[$processName])) {
            $processData = $this->allProcess[$processName];
            $processPid = $processData['pid'];
            $runningPids = $this->getRunningPids();
            if (isset($runningPids[$processPid])) {
                $billCfg = $ubillingConfig->getBilling();
                $sudoPath = $billCfg['SUDO'];
                $killPath = $billCfg['KILL'];
                $stopModifier = ($brutal) ? '-9' : '';
                $stopLabel = ($brutal) ? 'BRUTAL' : '';
                $command = $sudoPath . ' ' . $killPath . ' ' . $stopModifier . ' ' . $processPid;
                $shutdownResult = shell_exec($command);
                if (!empty($shutdownResult)) {
                    $result .= $shutdownResult;
                }

                log_register('PROCESSMON PROCESS `' . $processName . '` KILLED ' . $stopLabel . ' WITH PID [' . $processPid . ']');
            } else {
                $result .= __('No matching process PID was found') . ': ' . $processName . ' PID ' . $processPid;
                log_register('PROCESSMON PROCESS `' . $processName . '` KILL FAILED PID [' . $processPid . '] NOT FOUND');
            }
        } else {
            $result .= __('No matching process was found') . ': ' . $processName;
            log_register('PROCESSMON PROCESS `' . $processName . '` KILL FAILED PROCESS NOT FOUND');
        }
        return ($result);
    }

    /**
     * Renders process shutdown form
     * 
     * @param string $processName
     * 
     * @return string
     */
    protected function renderShutdownForm($processName) {
        $result = '';
        $shutdownUrl = self::URL_ME . '&' . self::ROUTE_STOP . '=' . $processName;
        $brutalityUrl = $shutdownUrl . '&' . self::ROUTE_BRUTAL . '=true';
        $stopAlert = __('Are you serious') . ' ' . __('Stop the process') . ' ' . $processName . '?';
        $result .= wf_JSAlert($shutdownUrl, wf_img('skins/stop.png') . ' ' . __('Stop the process') . ' ' . $processName, $stopAlert, '', 'ubButton');
        $result .= wf_delimiter();
        $result .= wf_JSAlert($brutalityUrl, wf_img('skins/skull.png') . ' ' . __('Stop the process with extreme cruelty'), $stopAlert, '', 'ubButton');

        return ($result);
    }

    /**
     * Renders and filters process list
     * 
     * @return string
     */
    public function renderProcessList() {
        $result = '';
        $this->loadAllProcessStates();

        //some filters here
        $renderActive = true;
        $renderFinished = true;
        $zenFlag = false;
        $counter = 0;
        $runningPids = array();
        if (ubRouting::checkGet(self::ROUTE_ACTIVE)) {
            $renderFinished = false;
        }

        if (ubRouting::checkGet(self::ROUTE_FINISHED)) {
            $renderActive = false;
        }

        if (ubRouting::checkGet(self::ROUTE_ALL)) {
            $renderActive = true;
            $renderFinished = true;
        }

        if (ubRouting::checkGet(self::ROUTE_ZENMODE)) {
            $renderFinished = false;
            $zenFlag = true;
        }

        if (!empty($this->allProcess)) {
            if (!$zenFlag) {
                $runningPids = $this->getRunningPids();
            }
            $cells = wf_TableCell(__('PID'), '10%');
            $cells .= wf_TableCell(__('Name'), '22%');
            $cells .= wf_TableCell('⛏️ ' . __('Active'), '8%');
            $cells .= wf_TableCell('⏳ ' . __('from'), '15%');
            $cells .= wf_TableCell('⏳ ' . __('to'), '15%');
            $cells .= wf_TableCell('⏱️ ' . __('time'), '30%');
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allProcess as $processName => $processData) {
                $rowRender = true;
                $activityLed = ($processData['finished']) ? wf_img('skins/icon_inactive.gif') : wf_img('skins/icon_active.gif');
                $activityFlag = ($processData['finished']) ? 0 : 1;
                $startTime = ($processData['start']) ? date("Y-m-d H:i:s", $processData['start']) : '';
                $endTime = ($processData['end']) ? date("Y-m-d H:i:s", $processData['end']) : '';
                $execTime = 0;
                $runningLabel = ($processData['finished']) ? '' : ' 🏁 ';

                if ($processData['finished']) {
                    $runTime = ($processData['end'] - $processData['start']);
                    if (!$renderFinished) {
                        $rowRender = false;
                    }
                } else {
                    $runTime = $processData['realtime'];
                    if (!$renderActive) {
                        $rowRender = false;
                    }
                }

                if ($runTime > 3) {
                    $execTime = zb_formatTime($runTime);
                } else {
                    $execTime = $processData['realtime'] . ' ' . __('sec.');
                }

                if ($rowRender) {
                    $pidLabel = $processData['pid'];
                    //process management form if its running
                    if (isset($runningPids[$processData['pid']]) and $activityFlag) {
                        $processString = $runningPids[$processData['pid']];
                        $pidModal = wf_modalAuto($processData['pid'], __('Manage') . ' ' . $processName, $this->renderShutdownForm($processName));
                        $pidLabel = $pidModal;
                    }
                    //highligthing dead processes with no PIDs
                    if (!$zenFlag) {
                        if (!isset($runningPids[$processData['pid']]) and $activityFlag) {
                            $pidLabel = $pidLabel . ' ' . wf_img_sized('skins/skull.png', __('Dead'), '12');
                        }
                    }
                    $cells = wf_TableCell($pidLabel);
                    $cells .= wf_TableCell($processName);
                    $cells .= wf_TableCell($activityLed, '', '', 'sorttable_customkey="' . $activityFlag . '"');
                    $cells .= wf_TableCell($startTime);
                    $cells .= wf_TableCell($endTime);
                    $cells .= wf_TableCell($runningLabel . $execTime, '', '', 'sorttable_customkey="' . $processData['realtime'] . '"');
                    $rows .= wf_TableRow($cells, 'row5');
                    $counter++;
                }
            }
            if ($counter > 0) {
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                $result .= __('Total') . ' ' . __('processes') . ': ' . $counter;
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }
}
