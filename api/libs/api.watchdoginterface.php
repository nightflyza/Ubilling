<?php

/**
 * Watchdog tasks management and other interfaces
 */
class WatchDogInterface {

    /**
     * Contains all watchdog tasks
     *
     * @var array
     */
    protected $allTasks = array();

    /**
     * Contains watchdog settings as key=>value
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Contains previous watchdog alerts parsed from log
     *
     * @var array
     */
    protected $previousAlerts = array();

    /**
     * Contains available checktypes
     *
     * @var array
     */
    protected $checktypes = array();

    /**
     * Contains available operators
     *
     * @var array
     */
    protected $operators = array();

    const TASKID_EX = 'NO_REQUIRED_TASK_ID';
    const TASKADD_EX = 'MISSING_REQUIRED_OPTION';

    /**
     * load all watchdog tasks intoo private prop allTasks
     * 
     * @return void
     */
    public function loadAllTasks() {
        $taskQuery = "SELECT * from `watchdog` ORDER BY `id` DESC;";
        $alltasks = simple_queryall($taskQuery);
        if (!empty($alltasks)) {
            foreach ($alltasks as $iz => $eachTask) {
                $this->allTasks[$eachTask['id']]['id'] = $eachTask['id'];
                $this->allTasks[$eachTask['id']]['active'] = $eachTask['active'];
                $this->allTasks[$eachTask['id']]['name'] = $eachTask['name'];
                $this->allTasks[$eachTask['id']]['checktype'] = $eachTask['checktype'];
                $this->allTasks[$eachTask['id']]['param'] = $eachTask['param'];
                $this->allTasks[$eachTask['id']]['operator'] = $eachTask['operator'];
                $this->allTasks[$eachTask['id']]['condition'] = $eachTask['condition'];
                $this->allTasks[$eachTask['id']]['action'] = $eachTask['action'];
                $this->allTasks[$eachTask['id']]['oldresult'] = $eachTask['oldresult'];
            }
        }
    }

    /**
     * load all watchdog previous alerts into private data prop
     * 
     * @return void
     */
    public function loadAllPreviousAlerts() {
        //select year to load
        if (wf_CheckPost(array('alertsyearsel'))) {
            $curYear = vf($_POST['alertsyearsel'], 3);
        } else {
            $curYear = curyear();
        }

        $query = "SELECT `id`,`date`,`event` from `weblogs` WHERE `event` LIKE 'WATCHDOG NOTIFY THAT%' AND `date` LIKE '" . $curYear . "-%';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->previousAlerts[$each['id']]['id'] = $each['id'];
                $this->previousAlerts[$each['id']]['date'] = $each['date'];
                $event = str_replace('WATCHDOG NOTIFY THAT', '', $each['event']);
                $event = str_replace('`', '', $event);
                $this->previousAlerts[$each['id']]['event'] = $event;
            }
        }
    }

    /**
     * protected property allTasks getter
     * 
     * @return array
     */
    public function getAllTasks() {
        $result = $this->allTasks;
        return ($result);
    }

    /**
     * Gets watchdog settings from database and load it into settings property
     * Also it sets default values into the database
     * 
     * @return void
     */
    public function loadSettings() {
        $alert = zb_StorageGet('WATCHDOG_ALERT');
        if (empty($alert)) {
            $alert = __('Watchdog notifies that');
            zb_StorageSet('WATCHDOG_ALERT', $alert);
        }
        $phones = zb_StorageGet('WATCHDOG_PHONES');
        if (empty($phones)) {
            zb_StorageSet('WATCHDOG_PHONES', '');
        }
        $emails = zb_StorageGet('WATCHDOG_EMAILS');
        if (empty($emails)) {
            zb_StorageSet('WATCHDOG_EMAILS', '');
        }
        $telegramchats = zb_StorageGet('WATCHDOG_TELEGRAM');
        $maintenanceMode = zb_StorageGet('WATCHDOG_MAINTENANCE');



        $this->settings['WATCHDOG_ALERT'] = $alert;
        $this->settings['WATCHDOG_PHONES'] = $phones;
        $this->settings['WATCHDOG_EMAILS'] = $emails;
        $this->settings['WATCHDOG_TELEGRAM'] = $telegramchats;
        $this->settings['WATCHDOG_MAINTENANCE'] = $maintenanceMode;

        $this->checktypes = array(
            'icmpping' => 'icmpping',
            'tcpping' => 'tcpping',
            'udpping' => 'udpping',
            'hopeping' => 'hopeping',
            'script' => 'script',
            'httpget' => 'httpget',
            'getusertraff' => 'getusertraff',
            'fileexists' => 'fileexists',
            'opentickets' => 'opentickets',
            'onepunch' => 'onepunch',
            'snmpwalk'=>'snmpwalk',
            'freediskspace'=>'freediskspace'
        );

        $this->operators = array(
            '=true' => '=true',
            '=false' => '=false',
            '==' => '==',
            '!=' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            'empty' => 'empty',
            'notempty' => 'notempty',
            'changed' => 'changed',
            'notchanged' => 'notchanged',
            'like' => 'like',
            'notlike' => 'notlike',
            'rised' => 'rised',
            'decreased' => 'decreased'
        );
    }

    /**
     * Returns current watchdog settings
     * 
     * @return array
     */
    public function getSettings() {
        $result = $this->settings;
        return ($result);
    }

    /**
     * shows all available tasks list
     * 
     * @return string
     */
    public function listAllTasks() {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Check type'));
        $cells .= wf_TableCell(__('Parameter'));
        $cells .= wf_TableCell(__('Operator'));
        $cells .= wf_TableCell(__('Condition'));
        $cells .= wf_TableCell(__('Actions'));
        $cells .= wf_TableCell(__('Manage'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $eachtask) {
                $details = wf_tag('pre') . print_r($eachtask, true) . wf_tag('pre', true);
                $detailLink = wf_modal($eachtask['id'], $eachtask['name'], $details, '', '600', '400');
                $cells = wf_TableCell($detailLink, '', '', 'sorttable_customkey="' . $eachtask['id'] . '"');
                $cells .= wf_TableCell(web_bool_led($eachtask['active']), '', '', 'sorttable_customkey="' . $eachtask['active'] . '"');
                $cells .= wf_TableCell($eachtask['name']);
                $cells .= wf_TableCell($eachtask['checktype']);
                $cells .= wf_TableCell($eachtask['param']);
                $cells .= wf_TableCell($eachtask['operator']);
                $cells .= wf_TableCell($eachtask['condition']);
                $cells .= wf_TableCell($eachtask['action']);

                $controls = wf_JSAlert('?module=watchdog&delete=' . $eachtask['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $controls .= wf_JSAlert('?module=watchdog&edit=' . $eachtask['id'], web_edit_icon(), __('Are you serious'));

                $cells .= wf_TableCell($controls);
                $rows .= wf_tag('tr', false, 'row5');
                $rows .= $cells;
                $rows .= wf_tag('tr', true);
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * shows new task creation form
     * 
     * @return string
     */
    public function newTaskForm() {
        $inputs = wf_TextInput('newname', __('Name'), '', true);
        $inputs .= wf_Selector('newchecktype', $this->checktypes, __('Check type'), '', true);
        $inputs .= wf_TextInput('newparam', __('Parameter'), '', true);
        $inputs .= wf_Selector('newoperator', $this->operators, __('Operator'), '', true);
        $inputs .= wf_TextInput('newcondition', __('Condition'), '', true);
        $inputs .= wf_TextInput('newaction', __('Actions'), '', true);
        $inputs .= wf_CheckInput('newactive', __('Active'), true, true);
        $inputs .= wf_Submit(__('Create'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        return ($form);
    }

    /**
     * shows modify form for some existing task
     *
     * @param int $taskID existing task id
     * 
     * @return string
     */
    public function editTaskForm($taskID) {

        $taskID = vf($taskID, 3);
        if (empty($taskID)) {
            throw new Exception(self::TASKID_EX);
        }

        $inputs = wf_TextInput('editname', __('Name'), $this->allTasks[$taskID]['name'], true);
        $inputs .= wf_Selector('editchecktype', $this->checktypes, __('Check type'), $this->allTasks[$taskID]['checktype'], true);
        $inputs .= wf_TextInput('editparam', __('Parameter'), $this->allTasks[$taskID]['param'], true);
        $inputs .= wf_Selector('editoperator', $this->operators, __('Operator'), $this->allTasks[$taskID]['operator'], true);
        $inputs .= wf_TextInput('editcondition', __('Condition'), $this->allTasks[$taskID]['condition'], true);
        $inputs .= wf_TextInput('editaction', __('Actions'), $this->allTasks[$taskID]['action'], true);
        $inputs .= wf_CheckInput('editactive', __('Active'), true, $this->allTasks[$taskID]['active']);
        $inputs .= wf_Submit(__('Save'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        $form .= wf_BackLink("?module=watchdog");
        return ($form);
    }

    /**
     * saves changes in the watchdog task as selected in editTaskForm
     * 
     * @return void
     */
    public function changeTask() {
        $taskID = vf($_GET['edit'], 3);
        if (wf_CheckPost(array('editname', 'editaction', 'editparam'))) {
            if (!empty($taskID)) {
                if (isset($_POST['editactive'])) {
                    $actFlag = 1;
                } else {
                    $actFlag = 0;
                }
                simple_update_field('watchdog', 'name', $_POST['editname'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'checktype', $_POST['editchecktype'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'param', $_POST['editparam'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'operator', $_POST['editoperator'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'condition', $_POST['editcondition'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'action', $_POST['editaction'], "WHERE `id`='" . $taskID . "'");
                simple_update_field('watchdog', 'active', $actFlag, "WHERE `id`='" . $taskID . "'");

                log_register("WATCHDOG CHANGE TASK [" . $taskID . "] `" . $_POST['editname'] . "`");
            } else {
                throw new Exception(self::TASKID_EX);
            }
        } else {
            throw new Exception(self::TASKADD_EX);
        }
    }

    /**
     * delete some existing watchdog task
     * 
     * @param int $taskID - existing task id
     * 
     * @return void
     */
    public function deleteTask($taskID) {
        $taskID = vf($taskID, 3);
        if (empty($taskID)) {
            throw new Exception(self::TASKID_EX);
        }
        $query = "DELETE from `watchdog` WHERE `id`='" . $taskID . "'";
        nr_query($query);
        log_register("WATCHDOG DELETE TASK [" . $taskID . "]");
    }

    /**
     * creates new watchdog task
     * 
     * @param string $name - task name
     * @param string $checktype - task check type
     * @param string $param - parameter
     * @param string $operator - operator
     * @param string $condition - condition for action
     * @param string $action - actions list
     * @param int $active - activity tinyint flag
     * 
     * @return void
     */
    public function createTask($name, $checktype, $param, $operator, $condition, $action, $active = 0) {
        $active = mysql_real_escape_string($active);
        $name = mysql_real_escape_string($name);
        $checktype = mysql_real_escape_string($checktype);
        $param = mysql_real_escape_string($param);
        $operator = mysql_real_escape_string($operator);
        $condition = mysql_real_escape_string($condition);
        $action = mysql_real_escape_string($action);

        if ((empty($name)) OR ( empty($param)) OR ( empty($action))) {
            throw new Exception(self::TASKADD_EX);
        }

        $query = "INSERT INTO `watchdog` (`id` , `active` , `name` , `checktype` , `param` ,`operator` ,  `condition` ,`action` ,`oldresult`)
                VALUES (NULL , '" . $active . "', '" . $name . "', '" . $checktype . "', '" . $param . "', '" . $operator . "', '" . $condition . "', '" . $action . "', NULL);";
        nr_query($query);
        log_register("WATCHDOG CREATE TASK `" . $name . "`");
    }

    /**
     * Shows watchdog control panel
     * 
     * @return string
     */
    public function panel() {
        $createWindow = $this->newTaskForm();
        $settingsWindow = $this->settingsForm();
        $result = '';

        $result .= wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create new task'), __('Create new task'), $createWindow, 'ubButton');
        $result .= wf_Link("?module=watchdog", wf_img('skins/icon_search_small.gif') . ' ' . __('Show all tasks'), false, 'ubButton');
        $result .= wf_Link("?module=watchdog&manual=true", wf_img('skins/refresh.gif') . ' ' . __('Manual run'), false, 'ubButton');
        $result .= wf_Link("?module=watchdog&previousalerts=true", wf_img('skins/time_machine.png') . ' ' . __('Previous alerts'), false, 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/settings.png') . ' ' . __('Settings'), __('Settings'), $settingsWindow, 'ubButton');


        return ($result);
    }

    /**
     * Sets maincente mode state
     * 
     * @param string $action
     * 
     * @return void
     */
    public function setMaintenance($action) {
        if ($action == 'enable') {
            zb_StorageSet('WATCHDOG_MAINTENANCE', 'enabled');
            log_register('WATCHDOG MAINTENANCE ENABLED');
        }

        if ($action == 'disable') {
            zb_StorageDelete('WATCHDOG_MAINTENANCE');
            log_register('WATCHDOG MAINTENANCE DISABLED');
        }

        //update notification area
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();
    }

    /**
     * returns watchdog settings edit form
     * 
     * @return string
     */
    public function settingsForm() {
        $result = '';
        $inputs = wf_TextInput('changealert', __('Watchdog alert text'), $this->settings['WATCHDOG_ALERT'], true, '30');
        $inputs .= wf_TextInput('changephones', __('Phone numbers to send alerts'), $this->settings['WATCHDOG_PHONES'], true, '30');
        $inputs .= wf_TextInput('changeemails', __('Emails to send alerts'), $this->settings['WATCHDOG_EMAILS'], true, '30');
        $inputs .= wf_TextInput('changetelegram', __('Telegram chat ids to send alerts'), $this->settings['WATCHDOG_TELEGRAM'], true, '30');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');

        if (cfr('ROOT')) {
            $result .= wf_tag('br');
            if (!$this->settings['WATCHDOG_MAINTENANCE']) {
                $result .= wf_Link('?module=watchdog&maintenance=enable', wf_img('skins/icon_ok.gif') . ' ' . __('Watchdog') . ': ' . __('Enabled'), false, 'ubButton');
            } else {
                $result .= wf_Link('?module=watchdog&maintenance=disable', wf_img('skins/icon_minus.png') . ' ' . __('Watchdog') . ': ' . __('Disabled'), false, 'ubButton');
            }
        }
        return ($result);
    }

    /**
     * save the current settings of watchdog as it posted in settingsForm
     * 
     * @return void
     */
    public function saveSettings() {
        if (wf_CheckPost(array('changealert'))) {
            zb_StorageSet('WATCHDOG_ALERT', $_POST['changealert']);
            zb_StorageSet('WATCHDOG_PHONES', $_POST['changephones']);
            zb_StorageSet('WATCHDOG_EMAILS', $_POST['changeemails']);
            zb_StorageSet('WATCHDOG_TELEGRAM', $_POST['changetelegram']);
            log_register("WATCHDOG SETTINGS CHANGED");
        }
    }

    /**
     * returns year selector to load alerts
     * 
     * @return string
     */
    public function yearSelectorAlerts() {
        $inputs = wf_YearSelector('alertsyearsel', __('Year') . ' ', false);
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result .= wf_tag('br');
        return ($result);
    }

    /**
     * preprocess and return full calendar data for alerts report
     * 
     * @retun string
     */
    public function renderAlertsCalendar() {
        $result = '';
        $controls = wf_TableCell($this->yearSelectorAlerts());
        $controls .= wf_TableCell($this->alertsSearchForm());
        $controls = wf_TableRow($controls);
        $result = wf_TableBody($controls, '60%', 0, '');

        if (!empty($this->previousAlerts)) {
            $calendarData = '';
            foreach ($this->previousAlerts as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);
                $calendarData .= "
                      {
                        title: '" . $rawTime . ' ' . $each['event'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                        className : 'undone'
		      },
                    ";
            }
            $result .= wf_FullCalendar($calendarData);
        } else {
            $result .= __('Nothing found');
        }


        return ($result);
    }

    /**
     * Returns previous alerts search form
     * 
     * @return string
     */
    public function alertsSearchForm() {
        $result = '';
        $availTaskNames = array();
        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                $availTaskNames[$each['name']] = $each['name'];
            }
        }

        $inputs = wf_Selector('previousalertsearch', $availTaskNames, __('Name'), '', false);
        $inputs .= wf_Submit(__('Search'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Returns previousa alerts search results
     * 
     * @param string $request
     * @return string
     */
    public function alertSearchResults($request) {
        $result = $this->alertsSearchForm();
        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Event'));
        $rows = wf_TableRow($cells, 'row1');
        $counter = 0;
        if (!empty($this->previousAlerts)) {
            foreach ($this->previousAlerts as $io => $each) {
                if (ispos($each['event'], $request)) {
                    $cells = wf_TableCell($each['date']);
                    $cells .= wf_TableCell($each['event']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $counter++;
                }
            }
        }
        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= __('Total') . ': ' . $counter;

        return ($result);
    }

}