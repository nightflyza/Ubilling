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
     * some predefined stuff like routes, etc here
     */
    const URL_ME = '?module=processmon';
    const ROUTE_ALL = 'showall';
    const ROUTE_ACTIVE = 'onlyactive';
    const ROUTE_FINISHED = 'finished';
    const ROUTE_ZENMODE = 'processzen';
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
        $result .= wf_BackLink('?module=report_sysload');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ALL . '=true', wf_img('skins/icon_thread.png') . ' ' . __('All'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ACTIVE . '=true', wf_img('skins/icon_active.gif') . ' ' . __('Active'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FINISHED . '=true', wf_img('skins/icon_inactive.gif') . ' ' . __('Finished'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZENMODE . '=true', wf_img('skins/zen.png') . ' ' . __('Zen'), false, 'ubButton') . ' ';
        return($result);
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
        $counter = 0;
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
        }

        if (!empty($this->allProcess)) {
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
                    $cells = wf_TableCell($processData['pid']);
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
        return($result);
    }

}
