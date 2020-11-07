<?php

/**
 * Just whiteboard. Helps to manage some non-urgent projets. Yep, without markers.
 */
class WhiteBoard {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available record categories as id=>name
     *
     * @var array
     */
    protected $categories = array();

    /**
     * Contains available whiteboard records as id=>recorddata
     *
     * @var array
     */
    protected $records = array();

    /**
     * Contains record priorities as id=>name
     *
     * @var array
     */
    protected $priorities = array();

    /**
     * Contains priority colors as priorityid=>color
     *
     * @var array
     */
    protected $prioColors = array();

    /**
     * Contains active employee as id=>employeename
     *
     * @var array
     */
    protected $activeEmployee = array();

    /**
     * Contains all employee as id=>employeename
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Additional comments object placeholder
     *
     * @var object
     */
    public $adcomments = '';

    /**
     * Additional comments scope
     */
    const SCOPE = 'WHITEBOARD';

    /**
     * Default control module URL
     */
    const URL_ME = '?module=whiteboard';

    /**
     * Table name to store whiteboard records
     */
    const REC_TABLE = 'whiteboard';

    /**
     * Creates new whiteboard instance
     * 
     * @return void
     */
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

    /**
     * Inits adcomments obj for further usage
     * 
     * @return void
     */
    protected function initAdcomments() {
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adcomments = new ADcomments(self::SCOPE);
        }
    }

    /**
     * Loads/Sets categories
     * 
     * @return void
     */
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

    /**
     * Sets available records priorities
     * 
     * @return void
     */
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

    /**
     * Inits system message helper obj for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all and active employee into protected props
     * 
     * @return void
     */
    protected function loadEmployeeData() {
        $this->activeEmployee[0] = '-';
        $this->allEmployee = ts_GetAllEmployee();
        $this->activeEmployee += ts_GetActiveEmployee();
    }

    /**
     * Loads whiteboard records data from database
     * 
     * @return void
     */
    protected function loadWhiteboardRecords() {
        $query = "SELECT * from `" . self::REC_TABLE . "` ORDER BY `priority` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->records[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
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

    /**
     * Renders record creation form
     * 
     * @return string
     */
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

    /**
     * Renders record editing form
     * 
     * @param int $recordId
     * 
     * @return string
     */
    protected function renderEditForm($recordId) {
        $recordId = vf($recordId, 3);
        $result = '';
        if ((!empty($this->categories)) AND ( $this->priorities)) {
            if ($this->records[$recordId]) {
                $recordData = $this->records[$recordId];
                $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
                $inputs = wf_HiddenInput('editrecord', $recordId);
                $inputs.= wf_Selector('editcategory', $this->categories, __('Category') . $sup, $recordData['categoryid'], true);
                $inputs.= wf_Selector('editpriority', $this->priorities, __('Priority') . $sup, $recordData['priority'], true);
                $inputs.= wf_TextInput('editname', __('Name') . $sup, $recordData['name'], true, 40);
                $inputs.=__('Text') . wf_tag('br');
                $inputs.= wf_TextArea('edittext', '', $recordData['text'], true, '60x20');
                $inputs.= wf_Selector('editemployeeid', $this->activeEmployee, __('Who should do'), $recordData['employeeid'], true);
                $doneFlag = ($recordData['donedate']) ? true : false;
                $inputs.= wf_CheckInput('editdone', __('This task is done'), true, $doneFlag);
                $inputs.= wf_delimiter();

                if (cfr('ROOT')) {
                    $inputs.=wf_tag('div', false, '', 'style="float:right;"');
                    $inputs.=wf_JSAlertStyled(self::URL_ME . '&deleterecord=' . $recordId, web_delete_icon() . ' ' . __('Remove this task - it is an mistake'), $this->messages->getDeleteAlert(), '');
                    $inputs.=wf_tag('div', true);
                }
                $inputs.=wf_Submit(__('Save'));
                $result.=wf_Form('', 'POST', $inputs, 'glamour');
                $result.=wf_delimiter();
            }
        }
        return ($result);
    }

    /**
     * Saves record if editing required 
     * 
     * @return void
     */
    public function saveRecord() {
        if (wf_CheckPost(array('editrecord', 'editcategory', 'editpriority', 'editname'))) {
            $recordId = vf($_POST['editrecord']);
            if ($this->isMyRecord($recordId)) {
                $where = "WHERE `id`='" . $recordId . "'";
                simple_update_field(self::REC_TABLE, 'categoryid', $_POST['editcategory'], $where);
                simple_update_field(self::REC_TABLE, 'priority', $_POST['editpriority'], $where);
                simple_update_field(self::REC_TABLE, 'name', $_POST['editname'], $where);
                simple_update_field(self::REC_TABLE, 'text', $_POST['edittext'], $where);
                simple_update_field(self::REC_TABLE, 'employeeid', $_POST['editemployeeid'], $where);
                if (wf_CheckPost(array('editdone'))) {
                    simple_update_field(self::REC_TABLE, 'donedate', curdatetime(), $where);
                } else {
                    simple_update_field(self::REC_TABLE, 'donedate', 'NULL', $where, true);
                }
                log_register('WHITEBOARD EDIT RECORD [' . $recordId . ']');
            }
        }
    }

    /**
     * Returns administrator realname or login
     * 
     * @param int $recordId
     * 
     * @return string
     */
    public function getCreator($recordId) {
        $result = '';
        if (isset($this->records[$recordId])) {
            $creatorLogin = $this->records[$recordId]['admin'];
            @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            $authorRealname = (isset($employeeLogins[$creatorLogin])) ? $employeeLogins[$creatorLogin] : $creatorLogin;
            $result = $authorRealname;
        }
        return ($result);
    }

    /**
     * Deletes record from database
     * 
     * @param int $recordId
     * 
     * @return void
     */
    public function delete($recordId) {
        $recordId = vf($recordId, 3);
        if (isset($this->records[$recordId])) {
            if (cfr('ROOT')) {
                $query = "DELETE FROM `whiteboard` WHERE `id`='" . $recordId . "';";
                nr_query($query);
                log_register('WHITEBOARD DELETE RECORD [' . $recordId . ']');
            }
        }
    }

    /**
     * Renders custom records styles by their priority colors
     * 
     * @return string
     */
    protected function getStyles() {
        $result = '';
        if (!empty($this->prioColors)) {
            $result.=wf_tag('style');
            foreach ($this->prioColors as $io => $each) {
                $result.='.wbpriority_' . $io . ' { background-color:#' . $each . '; padding: 10px; }';
            }
            $result.=wf_tag('style', true);

            $result.=wf_tag('script');
            $result.='$( function() { $( ".whiteboard" ).draggable({ scroll: false }); } );';
            $result.=wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Creates new record in database
     * 
     * @return void
     */
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

    /**
     * Renders available records as default whiteboard view
     * 
     * @return string
     */
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
            /**
             * Вареники, борщ,
             * Позаторішнє сало,
             * Півметра ковбаси -
             * Усе попропадало!
             * То що мені робити?
             * Не з'їм, то будуть тапки...
             * І хто мене врятує?..
             * Капітан Канапка-а-а!
             */
            if (!empty($tmpArr)) {
                $result.=wf_tag('div', false, 'whiteboardbg');
                foreach ($tmpArr as $categoryId => $records) {
                    $result.=wf_tag('div', false, 'whiteboard');
                    $result.=wf_tag('h2') . $this->categories[$categoryId] . wf_tag('h2', true);
                    if (!empty($records)) {
                        $rows = '';
                        foreach ($records as $io => $recordData) {
                            $commentsCount = $this->adcomments->getCommentsCount($recordData['id']);
                            $commentsLabel = ($commentsCount > 0) ? ' (' . $commentsCount . ')' : '';
                            $cells = wf_TableCell(wf_Link(self::URL_ME . '&showrecord=' . $recordData['id'], $recordData['name'] . $commentsLabel), '', 'wbpriority_' . $recordData['priority']);
                            $rows.=wf_TableRow($cells);
                        }
                        $result.=wf_TableBody($rows, '100%', 0, '');
                    }
                    $result.=wf_tag('div', true);
                }

                $result.=wf_CleanDiv();
                $result.=wf_tag('div', true);
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Checks is some record editable by current user
     * 
     * @param int $recordId
     * 
     * @return bool
     */
    protected function isMyRecord($recordId) {
        $result = false;
        if (isset($this->records[$recordId])) {
            $myLogin = whoami();
            if (($this->records[$recordId]['admin'] == $myLogin) OR ( cfr('ROOT'))) {
                $result = true;
            }
        }

        return ($result);
    }

    /**
     * Renders record view form with some edit controls if record is created by current user
     * 
     * @param int $recordId
     * 
     * @return string
     */
    public function renderRecord($recordId) {
        $result = '';
        if (isset($this->records[$recordId])) {
            $recordData = $this->records[$recordId];

            $taskCreateForm = ' ' . wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), ts_TaskCreateFormUnified($recordData['name'], '', ''), '', '450', '540');

            $cells = wf_TableCell(__('Category') . $taskCreateForm, '20%', 'row2');
            $cells.= wf_TableCell($this->categories[$recordData['categoryid']]);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Priority'), '', 'row2');
            $fc = wf_tag('font', false, '', 'color="#' . $this->prioColors[$recordData['priority']] . '"');
            $fe = wf_tag('font', true);
            $cells.= wf_TableCell($fc . $this->priorities[$recordData['priority']] . $fe);
            $rows.= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Name'), '', 'row2');
            $cells.= wf_TableCell($recordData['name']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Who should do'), '', 'row2');
            $cells.= wf_TableCell(@$this->allEmployee[$recordData['employeeid']]);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Creation date') . ' / ' . __('Finish date'), '', 'row2');
            $doneDate = ($recordData['donedate']) ? $recordData['donedate'] : __('Undone');
            $cells.= wf_TableCell($recordData['createdate'] . ' / ' . $doneDate);
            $rows.= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Text'), '', 'row2');
            $cells.= wf_TableCell(nl2br($recordData['text']));
            $rows.= wf_TableRow($cells, 'row3');

            $result.=wf_TableBody($rows, '100%', 0, '');
            if ($this->isMyRecord($recordId)) {
                $result.=wf_tag('br');
                $result.=wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit'), $this->renderEditForm($recordId), 'ubButton');
            }
        } else {
            $result.=$this->messages->getStyledMessage(__('Something went wrong') . ': EX_RECORD_ID_NOT_EXIST', 'error');
        }
        return ($result);
    }

}
