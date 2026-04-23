<?php

/**
 * Devices polling stats module
 */
class SwPollStats {
    /**
     * Ubilling config instance
     *
     * @var object
     */
    protected $ubillingConfig = '';

    /**
     * System messages instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Event icons
     *
     * @var array
     */
    protected $eventIcons=array();

    /**
     * Log path
     *
     * @var string
     */
    protected $logPath = 'exports/swpolldata.log';

    // Some predefined stuff here
    const URL_ME = '?module=swpollstats';
    const URL_SWITCH_PROFILE ='?module=switches&edit=';
    const URL_SNMP_QUERY='?module=switchpoller&switchid=';
    const ROUTE_HORDE = 'showhorde';

    public function __construct() {
        global $ubillingConfig;
        $this->ubillingConfig = $ubillingConfig;
        $this->messages = new UbillingMessageHelper();
        $this->setEventIcons();
    }

    /**
     * Sets event icons
     *
     * @return void
     */
    protected function setEventIcons() {
        $this->eventIcons = array(
            '[OK]' => '✅',
            '[SKIP]' => '⚠️',
            '[FAIL]' => '❌',
        );
    }

    /**
     * Runs module workflow
     *
     * @return void
     */
    public function render() {
        $controls = $this->renderControls();
        if (!empty($controls)) {
         show_window('', $controls);
        }

        if (ubRouting::checkGet(self::ROUTE_HORDE)) {
            show_window(__('Devices polling stats'), $this->renderHordeStatsTable());
        } else {
            show_window(__('Swpoll log'), $this->renderPollLogTable());
            zb_BillingStats(true);
        }
    }

    /**
     * Renders top module controls
     *
     * @return string
     */
    protected function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/log_icon_small.png') . ' ' . __('View polling log'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_HORDE . '=true', wf_img('skins/orc_small.png') . ' ' . __('View horde stats'), false, 'ubButton');
        return ($result);
    }

    /**
     * Renders polling log as table
     *
     * @return string
     */
    protected function renderPollLogTable() {
        $result = '';
        $columns = array(
            __('Date'),
            __('IP'),
            __('Location'),
            __('Time') . ' (' . __('seconds') . ')',
            __('Event'),
            __('Actions')
        );
        $tableData = array();
        $allSwitchesByIp = $this->getAllSwitchesByIp();

        if (file_exists($this->logPath)) {
            $rawResult = file_get_contents($this->logPath);

            if (!empty($rawResult)) {
                $prevTime = '';
                $logData = explodeRows($rawResult);

                foreach ($logData as $io => $each) {
                    if (!empty($each)) {
                        if (!ispos($each, 'SWPOLLSTART') and !ispos($each, 'SWPOLLFINISHED')) {
                            $eachEntry = explode(' ', $each);
                            $curTime = strtotime($eachEntry[0] . ' ' . $eachEntry[1]);
                            $diffTime = 0;

                            if (!empty($prevTime)) {
                                $diffTime = $curTime - $prevTime;
                            }

                            $prevTime = $curTime;
                            $devIp = @$eachEntry[2];
                            $switchLocation = '';
                            $actions = '';
                            if (!empty($devIp) and isset($allSwitchesByIp[$devIp])) {
                                $switchId = $allSwitchesByIp[$devIp]['id'];
                                $switchLocation = $allSwitchesByIp[$devIp]['location'];
                                $actions = wf_Link(self::URL_SWITCH_PROFILE . $switchId, web_edit_icon(__('Switch profile'))).' ';
                                $actions .= wf_Link(self::URL_SNMP_QUERY . $switchId, wf_img('skins/snmp.png', __('SNMP query')));
                            }
                            $eventData = trim($eachEntry[3] . ' ' . @$eachEntry[4] . ' ' . @$eachEntry[5]);
                            $eventData = $this->replaceEventTypeWithIcon($eventData);
                            $tableData[] = array(
                                $eachEntry[0] . ' ' . $eachEntry[1],
                                $devIp,
                                $switchLocation,
                                $diffTime,
                                $eventData,
                                $actions
                            );
                        } else {
                            $eachEntry = explode(' ', $each);
                            $prevTime = strtotime($eachEntry[0] . ' ' . $eachEntry[1]);
                        }
                    }
                }

                if (!empty($tableData)) {
                    $opts = '"order": [[0, "desc"]]';
                    $result .= wf_JqDtEmbed($columns, $tableData, false, __('events'), 100, $opts);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Replaces event type with icon in event text
     *
     * @param string $eventData
     *
     * @return string
     */
    protected function replaceEventTypeWithIcon($eventData) {
        $result = $eventData;
        if (!empty($eventData) and !empty($this->eventIcons)) {
            foreach ($this->eventIcons as $eventType => $eventIcon) {
                $result = str_replace($eventType, $eventIcon, $result);
            }
        }
        return ($result);
    }

    /**
     * Renders horde polling stats table
     *
     * @return string
     */
    protected function renderHordeStatsTable() {
        $result = '';
        $columns = array(__('from'),  __('to'), __('IP'), __('Location'), __('time'), __('Actions'));
        $tableData = array();
        $allSwitchesByIp = $this->getAllSwitchesByIp();
        $hordePath = 'exports/';
        $allHordeStats = rcms_scandir($hordePath, '*HORDE_*');

        if (!empty($allHordeStats)) {
            $totalCount = 0;
            $totalTime = 0;

            foreach ($allHordeStats as $io => $eachStat) {
                $devIp = zb_ExtractIpAddress($eachStat);
                $statData = file_get_contents($hordePath . $eachStat);
                if (!empty($statData)) {
                    $statData = unserialize($statData);
                    $pollTime = $statData['end'] - $statData['start'];
                    $switchLocation = '';
                    $actions = '';
                    if (!empty($devIp) and isset($allSwitchesByIp[$devIp])) {
                        $switchId = $allSwitchesByIp[$devIp]['id'];
                        $switchLocation = $allSwitchesByIp[$devIp]['location'];
                        $actions = wf_Link(self::URL_SWITCH_PROFILE . $switchId, web_edit_icon(__('Switch profile'))).' ';
                        $actions .= wf_Link(self::URL_SNMP_QUERY . $switchId, wf_img('skins/snmp.png', __('SNMP query')));
                    }

                    $tableData[] = array(
                        date("Y-m-d H:i:s", $statData['start']),
                        date("Y-m-d H:i:s", $statData['end']),
                        $devIp,
                        $switchLocation,

                        zb_formatTime($pollTime),
                        $actions
                    );
                    $totalCount++;
                    $totalTime += $pollTime;
                }
            }

            if (!empty($tableData)) {
                $opts = '"order": [[0, "desc"]]';
                $result .= wf_JqDtEmbed($columns, $tableData, false, __('devices'), 100, $opts);
                $result .= wf_delimiter(0);
                $result .= wf_tag('b') . __('Total') . ' ' . __('time') . ': ' . wf_tag('b', true) . zb_formatTime($totalTime);
                $result .= wf_delimiter(0);
                $result .= wf_tag('b') . __('Devices') . ': ' . wf_tag('b', true) . $totalCount;
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns switches data map by IP address
     *
     * @return array
     */
    protected function getAllSwitchesByIp() {
        $result = array();
        $allSwitches = zb_SwitchesGetAll();
        if (!empty($allSwitches)) {
            foreach ($allSwitches as $eachSwitch) {
                if (!empty($eachSwitch['ip']) and !empty($eachSwitch['id'])) {
                    $result[$eachSwitch['ip']]['id'] = $eachSwitch['id'];
                    $result[$eachSwitch['ip']]['location'] = $eachSwitch['location'];
                }
            }
        }
        return ($result);
    }
}