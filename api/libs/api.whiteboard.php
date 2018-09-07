<?php

class WhiteBoard {

    protected $altCfg = array();
    protected $categories = array();
    protected $records = array();
    protected $priorities = array();
    protected $prioColors = array();
    protected $activeEmployee = array();
    protected $allEmployee = array();
    protected $messages = '';
    protected $adcomments = '';

    const SCOPE = 'WHITEBOARD';
    const URL_ME = '?module=whiteboard';
    const REC_TABLE = 'whiteboard';

    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->initAdcomments();
        $this->loadCategories();
        $this->setPriorities();
        $this->loadEmployeeData();
        $this->loadWhiteboardRecords();
    }

    /**
     * Loads system alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    protected function initAdcomments() {
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adcomments = new ADcomments(self::SCOPE);
        }
    }

    protected function loadCategories() {
        $this->categories = array(
            1 => __('Signups'),
            2 => __('Network'),
            3 => __('Marketing'),
            4 => __('Finance'),
            5 => __('Shopping'),
            6 => __('Miscellaneous'),
        );
    }

    protected function setPriorities() {
        $this->priorities = array(
            1 => __('Indifferently'),
            2 => __('Sometime later'),
            3 => __('You need to do'),
            4 => __('The faster the better'),
            5 => __('Urgently') . '!',
            6 => __('Matter of life and death'),
        );

        $this->prioColors = array(
            1 => '820091',
            2 => '202fa4',
            3 => '005a20',
            4 => '8a8500',
            5 => 'f48a00',
            6 => 'ea0000'
        );
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function loadEmployeeData() {
        $this->activeEmployee[''] = '-';
        $this->allEmployee = ts_GetAllEmployee();
        $this->activeEmployee += ts_GetActiveEmployee();
    }

    protected function loadWhiteboardRecords() {
        $query = "SELECT * from `" . self::REC_TABLE . "` ORDER BY `priority` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->records[$each['id']] = $each;
            }
        }
    }

    public function renderControls() {
        $result = '';
        if (wf_CheckGet(array('showrecord'))) {
            $result.=wf_BackLink(self::URL_ME);
        }
        $result.=wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderCreateForm(), 'ubButton');
        if ((!wf_CheckGet(array('showrecord')))) {
            if (!wf_CheckGet(array('onlydone'))) {
                $result.=wf_Link(self::URL_ME . '&onlydone=true', wf_img('skins/done_icon.png') . ' ' . __('Done'), false, 'ubButton');
            } else {
                $result.=wf_Link(self::URL_ME, wf_img('skins/undone_icon.png') . ' ' . __('Undone'), false, 'ubButton');
            }
        }
        return ($result);
    }

    protected function renderCreateForm() {
        $result = '';
        if ((!empty($this->categories)) AND ( $this->priorities)) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('createnewrecord', 'true');
            $inputs.= wf_Selector('newcategory', $this->categories, __('Category') . $sup, '', true);
            $inputs.= wf_Selector('newpriority', $this->priorities, __('Priority') . $sup, '', true);
            $inputs.= wf_TextInput('newname', __('Name') . $sup, '', true, 40);
            $inputs.=__('Text') . wf_tag('br');
            $inputs.= wf_TextArea('newtext', '', '', true, '60x20');
            $inputs.= wf_Selector('newemployeeid', $this->activeEmployee, __('Who should do'), '', true);
            $inputs.= wf_delimiter();
            $inputs.=wf_Submit(__('Create'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    protected function getStyles() {
        $result = '';
        if (!empty($this->prioColors)) {
            $result.=wf_tag('style');
            foreach ($this->prioColors as $io => $each) {
                $result.='.wbpriority_' . $io . ' { background-color:#' . $each . '; padding: 10px; }';
            }
            $result.=wf_tag('style', true);
        }
        return ($result);
    }

    public function createRecord() {
        if (wf_CheckPost(array('createnewrecord', 'newcategory', 'newpriority', 'newname'))) {
            $category = vf($_POST['newcategory'], 3);
            $priority = vf($_POST['newpriority'], 3);
            $employeeid = vf($_POST['newemployeeid'], 3);
            $name = mysql_real_escape_string($_POST['newname']);
            $text = mysql_real_escape_string($_POST['newtext']);
            $createdate = curdatetime();
            $admin = whoami();
            $query = "INSERT INTO `" . self::REC_TABLE . "` (`id`,`categoryid`,`admin`,`employeeid`,`createdate`,`donedate`,`priority`,`name`,`text`) VALUES ";
            $query.= "(NULL, '" . $category . "','" . $admin . "','" . $employeeid . "','" . $createdate . "',NULL,'" . $priority . "','" . $name . "','" . $text . "');";
            nr_query($query);
            $newId = simple_get_lastid(self::REC_TABLE);
            log_register('WHITEBOARD CREATE RECORD [' . $newId . ']');
        }
    }

    public function renderRecordsList() {
        $result = '';
        $result.=$this->getStyles();
        $tmpArr = array();
        $doneFlag = (wf_CheckGet(array('onlydone'))) ? true : false;

        if (!empty($this->records)) {
            foreach ($this->records as $io => $each) {
                if ($doneFlag) {
                    if ($each['donedate']) {
                        $tmpArr[$each['categoryid']][] = $each;
                    }
                } else {
                    if (!$each['donedate']) {
                        $tmpArr[$each['categoryid']][] = $each;
                    }
                }
            }

            if (!empty($tmpArr)) {
                foreach ($tmpArr as $categoryId => $records) {
                    $result.=wf_tag('div', false, 'whiteboard');
                    $result.=wf_tag('h2') . $this->categories[$categoryId] . wf_tag('h2', true);
                    if (!empty($records)) {
                        $rows = '';
                        foreach ($records as $io => $recordData) {
                            $cells = wf_TableCell(wf_Link(self::URL_ME . '&showrecord=' . $recordData['id'], $recordData['name']), '', 'wbpriority_' . $recordData['priority']);
                            $rows.=wf_TableRow($cells);
                        }
                        $result.=wf_TableBody($rows, '100%', 0, '');
                    }
                    $result.=wf_tag('div', true);
                }
                $result.=wf_CleanDiv();
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    public function renderRecord($id) {
        $result = '';
        if (isset($this->records[$id])) {
            $recordData = $this->records[$id];
            $cells = wf_TableCell(__('Category'), '20%', 'row2');
            $cells.= wf_TableCell($this->categories[$recordData['categoryid']]);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Priority'), '', 'row2');
            $cells.= wf_TableCell($this->priorities[$recordData['priority']]);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Name'), '', 'row2');
            $cells.= wf_TableCell($recordData['name']);
            $rows.= wf_TableRow($cells, 'row3');

            $result.=wf_TableBody($rows, '100%', 0, '');
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': EX_RECORD_ID_NOT_EXIST', 'error');
        }
        return ($result);
    }

}
