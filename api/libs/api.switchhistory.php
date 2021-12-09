<?php

/**
 * Renders events which happens with some switch devices
 */
class SwitchHistory {

    /**
     * Contains existing switch ID
     *
     * @var int
     */
    protected $switchId = '';

    /**
     * Weblogs data model placeholder
     *
     * @var object
     */
    protected $weblogs = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains current instance render type
     *
     * @var bool
     */
    protected $ajaxFlag = false;

    /**
     * Contains current instance report scope
     *
     * @var bool
     */
    protected $timeMachineFlag = false;

    /**
     * Switch profile back URL
     */
    const URL_SWPROFILE = '?module=switches&edit=';

    /**
     * Default controller module URL
     */
    const URL_ME = '?module=switchhistory';

    /**
     * Creates new report instance
     * 
     * @param int $swithId
     * 
     * @return void
     */
    public function __construct($switchId = '') {
        $this->setSwitchId($switchId);
        $this->setRenderType();
        $this->initMessages();
        $this->initWeblogs();
    }

    /**
     * Sets current instance render type
     * 
     * @return void
     */
    protected function setRenderType() {
        if (ubRouting::checkGet('ajax')) {
            $this->ajaxFlag = true;
        }

        if (ubRouting::checkGet('timemachine')) {
            $this->timeMachineFlag = true;
        }
    }

    /**
     * Inits system messages object instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits weblogs database model
     * 
     * @return void
     */
    protected function initWeblogs() {
        $this->weblogs = new NyanORM('weblogs');
    }

    /**
     * Sets required switchId to protected prop
     * 
     * @param int $switchId
     * 
     * @return void
     */
    protected function setSwitchId($switchId) {
        $switchId = ubRouting::filters($switchId, 'int');
        if (!empty($switchId)) {
            $this->switchId = $switchId;
        }
    }

    /**
     * Renders switch timemachine data
     * 
     * @return string
     */
    protected function renderTimeMachineData() {
        $result = '';
        if (!empty($this->switchId)) {
            $switchData = zb_SwitchGetData($this->switchId);
            if (!empty($switchData)) {
                $switchIp = $switchData['ip'];
                $tmData = ub_SwitchesTimeMachineGetByIp($switchIp);
                if (!empty($tmData)) {
                    $cells = wf_TableCell(__('Switch dead'));
                    $cells .= wf_TableCell(__('Location'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($tmData as $date => $location) {
                        $cells = wf_TableCell($date);
                        $cells .= wf_TableCell($location);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'success');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_SWITCH_DATA', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_SWITCHID', 'error');
        }


        return($result);
    }

    /**
     * Renders report about all events which happens with some switch
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        if ($this->ajaxFlag) {
            $result .= wf_tag('div', false, '', 'style="height:200px; overflow:auto;"');
            $result .= wf_tag('strong') . __('History of switch life') . wf_tag('strong', true) . ' ';
            $result .= wf_Link(self::URL_ME . '&switchid=' . $this->switchId, wf_img_sized('skins/log_icon_small.png', __('View full'), 12)) . ' ';
            $result .= wf_Link(self::URL_ME . '&timemachine=true&switchid=' . $this->switchId, wf_img_sized('skins/skull.png', __('Time machine'),12));
        }

        if (!$this->timeMachineFlag) {
            if (!empty($this->switchId)) {
                $eventMask = '%SWITCH%';
                $switchMask = '%[' . $this->switchId . ']%';

                $this->weblogs->where('event', 'LIKE', $eventMask);
                $this->weblogs->where('event', 'LIKE', $switchMask);
                $this->weblogs->orderBy('id', 'desc');
                $allEvents = $this->weblogs->getAll();

                if (!empty($allEvents)) {
                    $cells = wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Admin'));
                    $cells .= wf_TableCell(__('IP'));
                    $cells .= wf_TableCell(__('Event'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($allEvents as $io => $each) {
                        $cells = wf_TableCell($each['id']);
                        $cells .= wf_TableCell($each['date']);
                        $cells .= wf_TableCell($each['admin']);
                        $cells .= wf_TableCell($each['ip']);
                        $cells .= wf_TableCell($each['event']);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_SWITCHID', 'error');
            }
        } else {
            $result .= $this->renderTimeMachineData();
        }

//no additional formatting required
        if ($this->ajaxFlag) {
            $result .= wf_tag('div', true);
            die($result);
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_SWPROFILE . $this->switchId) . ' ';

        if (!$this->timeMachineFlag) {
            $timeMachineUrl = self::URL_ME . '&timemachine=true&switchid=' . $this->switchId;
            $result .= wf_Link($timeMachineUrl, wf_img('skins/skull.png') . ' ' . __('Time machine'), false, 'ubButton') . '';
        } else {
            $switchHistUrl = self::URL_ME . '&switchid=' . $this->switchId;
            $result .= wf_Link($switchHistUrl, wf_img('skins/log_icon_small.png') . ' ' . __('History'), false, 'ubButton') . '';
        }


        return($result);
    }

}
