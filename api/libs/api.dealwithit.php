<?php

class DealWithIt {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available tasks as id=>taskdata
     *
     * @var array
     */
    protected $allTasks = array();

    /**
     * Contains available actions array as action=>name
     *
     * @var array
     */
    protected $actionNames = array();

    /**
     * Contains available actions icons as action=>icon URL
     *
     * @var array
     */
    protected $actionIcons = array();

    /**
     * Contains available actions array as  callback url=>name
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Base module URL
     */
    const URL_ME = '?module=pl_dealwithit';

    public function __construct() {
        $this->loadAlter();
        $this->setActionNames();
        $this->setActionIcons();
        $this->setActionsURL();
        $this->loadTasks();
    }

    /**
     * Loads system alter.ini config for further usage
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
     * Loads existing tasks for further usage
     * 
     * @return void
     */
    protected function loadTasks() {
        $query = "SELECT * from `dealwithit`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTasks[$each['id']] = $each;
            }
        }
    }

    /**
     * Fills available actions array with names
     * 
     * @rerutn void
     */
    protected function setActionNames() {
        $this->actionNames = array(
            'noaction' => '-',
            'addcash' => __('Add cash'),
            'corrcash' => __('Correct saldo'),
            'setcash' => __('Set cash'),
            'credit' => __('Change') . ' ' . __('credit'),
            'creditexpire' => __('Change') . ' ' . __('credit expire date'),
            'tariffchange' => __('Change') . ' ' . __('tariff'),
            'tagadd' => __('Add tag'),
            'tagdel' => __('Delete tag'),
            'freeze' => __('Freeze user'),
            'unfreeze' => __('Unfreeze user'),
            'reset' => __('User reset'),
            'setspeed' => __('Change speed override'),
            'down' => __('Set user down'),
            'undown' => __('Enable user'),
            'ao' => __('Enable AlwaysOnline'),
            'unao' => __('Disable AlwaysOnline')
        );
    }

    /**
     * Sets available actions array with icons
     * 
     * @return void
     */
    protected function setActionIcons() {
        $this->actionIcons = array(
            'addcash' => 'skins/icon_dollar.gif',
            'corrcash' => 'skins/icon_dollar.gif',
            'setcash' => 'skins/icon_dollar.gif',
            'credit' => 'skins/icon_credit.gif',
            'creditexpire' => 'skins/icon_calendar.gif',
            'tariffchange' => 'skins/icon_tariff.gif',
            'tagadd' => 'skins/tagiconsmall.png',
            'tagdel' => 'skins/tagiconsmall.png',
            'freeze' => 'skins/icon_passive.gif',
            'unfreeze' => 'skins/icon_passive.gif',
            'reset' => 'skins/refresh.gif',
            'setspeed' => 'skins/icon_speed.gif',
            'down' => 'skins/icon_down.gif',
            'undown' => 'skins/icon_down.gif',
            'ao' => 'skins/icon_online.gif',
            'unao' => 'skins/icon_online.gif'
        );
    }

    /**
     * Fills available actions array with selector URL-s
     * 
     * @rerutn void
     */
    protected function setActionsURL() {
        $this->actions = array(
            self::URL_ME . '&ajinput=noaction' => $this->actionNames['noaction'],
            self::URL_ME . '&ajinput=addcash' => $this->actionNames['addcash'],
            self::URL_ME . '&ajinput=corrcash' => $this->actionNames['corrcash'],
            self::URL_ME . '&ajinput=setcash' => $this->actionNames['setcash'],
            self::URL_ME . '&ajinput=credit' => $this->actionNames['credit'],
            self::URL_ME . '&ajinput=creditexpire' => $this->actionNames['creditexpire'],
            self::URL_ME . '&ajinput=tariffchange' => $this->actionNames['tariffchange'],
            self::URL_ME . '&ajinput=tagadd' => $this->actionNames['tagadd'],
            self::URL_ME . '&ajinput=tagdel' => $this->actionNames['tagdel'],
            self::URL_ME . '&ajinput=freeze' => $this->actionNames['freeze'],
            self::URL_ME . '&ajinput=unfreeze' => $this->actionNames['unfreeze'],
            self::URL_ME . '&ajinput=reset' => $this->actionNames['reset'],
            self::URL_ME . '&ajinput=setspeed' => $this->actionNames['setspeed'],
            self::URL_ME . '&ajinput=down' => $this->actionNames['down'],
            self::URL_ME . '&ajinput=undown' => $this->actionNames['undown'],
            self::URL_ME . '&ajinput=ao' => $this->actionNames['ao'],
            self::URL_ME . '&ajinput=unao' => $this->actionNames['unao']
        );
    }

    /**
     * Logs tasks creation/execution to database
     * 
     * @param string $id
     * @param string $date
     * @param string $login
     * @param string $action
     * @param string $param
     * @param string $note
     * @param bool $done
     * 
     * @return void
     */
    protected function logTask($id, $date, $login, $action, $param, $note, $done) {
        $id = vf($id, 3);
        $admin = whoami();
        $mtime = curdatetime();
        $doneFlag = ($done) ? 1 : 0;
        $query = "INSERT INTO `dealwithithist` (`id`,`originalid`,`mtime`,`date`,`login`,`action`,`param`,`note`,`admin`,`done`) VALUES";
        $query.="(NULL,'" . $id . "','" . $mtime . "','" . $date . "','" . $login . "','" . $action . "','" . $param . "','" . $note . "','" . $admin . "','" . $doneFlag . "');";
        nr_query($query);
    }

    /**
     * Creates scheduler task in database
     * 
     * @param string $date
     * @param string $login
     * @param string $action
     * @param string $param
     * @param string $note
     * 
     * @return void
     */
    public function createTask($date, $login, $action, $param, $note) {
        $dateF = mysql_real_escape_string($date);
        $loginF = mysql_real_escape_string($login);
        $actionF = mysql_real_escape_string($action);
        $paramF = mysql_real_escape_string($param);
        $noteF = mysql_real_escape_string($note);
        $query = "INSERT INTO `dealwithit` (`id`,`date`,`login`,`action`,`param`,`note`) VALUES";
        $query.="(NULL,'" . $dateF . "','" . $loginF . "','" . $actionF . "','" . $paramF . "','" . $noteF . "');";
        nr_query($query);
        $newId = simple_get_lastid('dealwithit');
        $this->logTask($newId, $dateF, $loginF, $actionF, $paramF, $noteF, false);
        log_register('SCHEDULER CREATE ID [' . $newId . '] (' . $login . ')  DATE `' . $date . ' `ACTION `' . $action . '` NOTE `' . $note . '`');
    }

    /**
     * Deletes existing task from database
     * 
     * @param int $taskId
     * 
     * @return void
     */
    public function deleteTask($taskId) {
        $taskId = vf($taskId, 3);
        if (isset($this->allTasks[$taskId])) {
            $taskData = $this->allTasks[$taskId];
            $query = "DELETE from `dealwithit` WHERE `id`='" . $taskId . "'";
            nr_query($query);
            log_register('SCHEDULER DELETE ID [' . $taskId . '] (' . $taskData['login'] . ')  DATE `' . $taskData['date'] . ' `ACTION `' . $taskData['action'] . '`');
        }
    }

    /**
     * Renders task creation form
     * 
     * @return string
     */
    public function renderCreateForm($login) {
        $result = '';
        $result.=wf_AjaxLoader();
        $inputs = wf_HiddenInput('newschedlogin', $login);
        $inputs.= wf_DatePickerPreset('newscheddate', curdate(), true) . ' ' . __('Target date') . wf_tag('br');
        $inputs.= wf_AjaxSelectorAC('ajparamcontainer', $this->actions, __('Task'), '', true);
        $inputs.= wf_AjaxContainer('ajparamcontainer');

        $result.= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns ajax inputs of required type
     * 
     * @return string
     */
    public function catchAjRequest() {
        $result = '';
        if (wf_CheckGet(array('ajinput'))) {
            $request = vf($_GET['ajinput']);
            switch ($request) {
                case 'addcash':
                    $result.= wf_HiddenInput('newschedaction', 'addcash');
                    $result.= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    break;
                case 'corrcash':
                    $result.= wf_HiddenInput('newschedaction', 'corrcash');
                    $result.= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    break;
                case 'setcash':
                    $result.= wf_HiddenInput('newschedaction', 'setcash');
                    $result.= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    break;
                case 'credit':
                    $result.= wf_HiddenInput('newschedaction', 'credit');
                    $result.= wf_TextInput('newschedparam', __('New credit'), '', true, 5);
                    break;
                case 'creditexpire':
                    $result.= wf_HiddenInput('newschedaction', 'creditexpire');
                    $result.= wf_DatePickerPreset('newschedparam', curdate()) . ' ' . __('New credit expire') . wf_tag('br');
                    break;
                case 'tariffchange':
                    $result.= wf_HiddenInput('newschedaction', 'tariffchange');
                    $result.= web_tariffselector('newschedparam') . ' ' . __('Tariff name') . wf_tag('br');
                    break;
                case 'tagadd':
                    $result.= wf_HiddenInput('newschedaction', 'tagadd');
                    $allTags = array();
                    $allTagsRaw = simple_queryall("SELECT * from `tagtypes`");
                    if (!empty($allTagsRaw)) {
                        foreach ($allTagsRaw as $io => $each) {
                            $allTags[$each['id']] = $each['tagname'];
                        }
                    }
                    $result.= wf_Selector('newschedparam', $allTags, __('Tag'), '', true);
                    break;
                case 'tagdel':
                    $result.= wf_HiddenInput('newschedaction', 'tagdel');
                    $allTags = array();
                    $allTagsRaw = simple_queryall("SELECT * from `tagtypes`");
                    if (!empty($allTagsRaw)) {
                        foreach ($allTagsRaw as $io => $each) {
                            $allTags[$each['id']] = $each['tagname'];
                        }
                    }
                    $result.= wf_Selector('newschedparam', $allTags, __('Tag'), '', true);
                    break;
                case 'freeze':
                    $result.= wf_HiddenInput('newschedaction', 'freeze');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'unfreeze':
                    $result.= wf_HiddenInput('newschedaction', 'unfreeze');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'reset':
                    $result.= wf_HiddenInput('newschedaction', 'reset');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'setspeed':
                    $result.= wf_HiddenInput('newschedaction', 'setspeed');
                    $result.= wf_TextInput('newschedparam', __('New speed override'), '', true, 5);
                    break;
                case 'down':
                    $result.= wf_HiddenInput('newschedaction', 'down');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'undown':
                    $result.= wf_HiddenInput('newschedaction', 'undown');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'ao':
                    $result.= wf_HiddenInput('newschedaction', 'ao');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
                case 'unao':
                    $result.= wf_HiddenInput('newschedaction', 'unao');
                    $result.= wf_HiddenInput('newschedparam', '');
                    break;
            }

            $result.= wf_TextInput('newschednote', __('Notes'), '', true, 30);
            $result.=wf_Submit(__('Create'));


            if ($request == 'noaction') {
                $result = __('Please select action');
            }
        }
        die($result);
    }

    /**
     * 
     * @return void/error notice
     */
    public function catchCreateRequest() {
        $result = '';
        if (wf_CheckPost(array('newschedlogin', 'newschedaction', 'newscheddate'))) {
            $date = $_POST['newscheddate'];
            $action = $_POST['newschedaction'];
            $param = $_POST['newschedparam'];
            $note = $_POST['newschednote'];
            $login = $_POST['newschedlogin'];
            if (zb_checkDate($date)) {
                switch ($action) {
                    //this action types requires non empty parameter
                    case 'addcash':
                        if ($param) {
                            if (zb_checkMoney($param)) {
                                $this->createTask($date, $login, $action, $param, $note);
                            } else {
                                $result = __('Wrong format of a sum of money to pay');
                            }
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'corrcash':
                        if ($param) {
                            if (zb_checkMoney($param)) {
                                $this->createTask($date, $login, $action, $param, $note);
                            } else {
                                $result = __('Wrong format of a sum of money to pay');
                            }
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'setcash':
                        if ($param) {
                            if (zb_checkMoney($param)) {
                                $this->createTask($date, $login, $action, $param, $note);
                            } else {
                                $result = __('Wrong format of a sum of money to pay');
                            }
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'credit':
                        if ($param >= 0) {
                            if (zb_checkMoney($param)) {
                                $this->createTask($date, $login, $action, $param, $note);
                            } else {
                                $result = __('Wrong format of a sum of money to pay');
                            }
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'creditexpire':
                        if ($param) {
                            if (zb_checkDate($param)) {
                                $this->createTask($date, $login, $action, $param, $note);
                            } else {
                                $result = __('Wrong date format');
                            }
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'tariffchange':
                        if ($param) {
                            $this->createTask($date, $login, $action, $param, $note);
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'tagadd':
                        if ($param) {
                            $this->createTask($date, $login, $action, $param, $note);
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    case 'tagdel':
                        if ($param) {
                            $this->createTask($date, $login, $action, $param, $note);
                        } else {
                            $result = __('No all of required fields is filled');
                        }
                        break;
                    //for this task types parameter may be empty
                    case 'freeze':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'unfreeze':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'reset':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'setspeed':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'down':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'undown':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'ao':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                    case 'unao':
                        $this->createTask($date, $login, $action, $param, $note);
                        break;
                }
            } else {
                $result = __('Wrong date format');
            }
        } else {
            $result = __('Something went wrong');
        }
        return ($result);
    }

    /**
     * Renders available tasks list with controls
     * 
     * @param sring $login
     * 
     * @return string
     */
    public function renderTasksList($login = '') {
        $result = '';
        $messages = new UbillingMessageHelper();
        $tmpArr = array();
        $allRealNames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();

        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                if (empty($login)) {
                    $tmpArr[$io] = $each;
                } else {
                    if ($login == $each['login']) {
                        $tmpArr[$io] = $each;
                    }
                }
            }
        }

        if (!empty($tmpArr)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Task'));
            $cells.= wf_TableCell(__('Parameter'));
            $cells.= wf_TableCell(__('Notes'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($tmpArr as $io => $each) {
                $actionIcon = (isset($this->actionIcons[$each['action']])) ? wf_img_sized($this->actionIcons[$each['action']], $this->actionNames[$each['action']], '12', '12') . ' ' : '';
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, ''));
                $cells.= wf_TableCell(@$allAddress[$each['login']]);
                $cells.= wf_TableCell(@$allRealNames[$each['login']]);
                $cells.= wf_TableCell($actionIcon . $this->actionNames[$each['action']]);
                $cells.= wf_TableCell($each['param']);
                $cells.= wf_TableCell($each['note']);
                $taskControls = wf_JSAlert(self::URL_ME . '&username=' . $each['login'] . '&deletetaskid=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
                $cells.= wf_TableCell($taskControls);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $messages->getStyledMessage(__('Nothing found'), 'info');
        }

        return ($result);
    }

    /**
     * Renders available tasks list with controls
     * 
     * 
     * @return string
     */
    public function renderTasksHistory() {
        $result = '';
        $messages = new UbillingMessageHelper();
        $tmpArr = array();
        $allRealNames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $query="SELECT * from `dealwithithist` ORDER by `id` DESC";
        $allTasksHistory=  simple_queryall($query);
        
        if (!empty($allTasksHistory)) {
            foreach ($allTasksHistory as $io => $each) {
                $tmpArr[$io] = $each;
            }
        }

        if (!empty($tmpArr)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Changed'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Task'));
            $cells.= wf_TableCell(__('Parameter'));
            $cells.= wf_TableCell(__('Notes'));
            $cells.= wf_TableCell(__('Done'));
            $cells.= wf_TableCell(__('Admin'));

            $rows = wf_TableRow($cells, 'row1');

            foreach ($tmpArr as $io => $each) {
                $actionIcon = (isset($this->actionIcons[$each['action']])) ? wf_img_sized($this->actionIcons[$each['action']], $this->actionNames[$each['action']], '12', '12') . ' ' : '';
                $cells = wf_TableCell($each['originalid']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['mtime']);
                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, ''));
                $cells.= wf_TableCell(@$allAddress[$each['login']]);
                $cells.= wf_TableCell(@$allRealNames[$each['login']]);
                $cells.= wf_TableCell($actionIcon . $this->actionNames[$each['action']]);
                $cells.= wf_TableCell($each['param']);
                $cells.= wf_TableCell($each['note']);
                $cells.= wf_TableCell(web_bool_led($each['done']));
                $cells.= wf_TableCell($each['admin']);

                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $messages->getStyledMessage(__('Nothing found'), 'info');
        }

        return ($result);
    }

    /**
     * Sets task as done / deletes it from database
     * 
     * @param int $taskId
     * 
     * @return void
     */
    protected function setTaskIsDone($taskId) {
        $taskId = vf($taskId, 3);
        if (isset($this->allTasks[$taskId])) {
            $taskData = $this->allTasks[$taskId];
            $this->logTask($taskId, $taskData['date'], $taskData['login'], $taskData['action'], $taskData['param'], $taskData['note'], true);
            $query = "DELETE from `dealwithit` WHERE `id`='" . $taskId . "'";
            nr_query($query);
            log_register('SCHEDULER DONE ID [' . $taskId . '] (' . $taskData['login'] . ')');
        }
    }

    /**
     * Performs available tasks processing
     * 
     * @return void
     */
    public function tasksProcessing() {
        global $billing;
        $curdate = curdate();
        $rawUsers = zb_UserGetAllStargazerData();
        $allUsers = array();
        if (!empty($rawUsers)) {
            foreach ($rawUsers as $io => $each) {
                $allUsers[$each['login']] = $each;
            }
        }

        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                if ($each['date'] == $curdate) {
                    if (isset($allUsers[$each['login']])) {
                        $login = $each['login'];
                        $param = $each['param'];

                        switch ($each['action']) {
                            case 'addcash':
                                zb_CashAdd($login, $param, 'add', 1, 'SCHEDULED');
                                break;
                            case 'corrcash':
                                zb_CashAdd($login, $param, 'correct', 1, 'SCHEDULED');
                                break;
                            case 'setcash':
                                zb_CashAdd($login, $param, 'set', 1, 'SCHEDULED');
                                break;
                            case 'credit':
                                $billing->setcredit($login, $param);
                                log_register('CHANGE Credit (' . $login . ') ON ' . $param);
                                break;
                            case 'creditexpire':
                                $billing->setcreditexpire($login, $param);
                                log_register('CHANGE CreditExpire (' . $login . ') ON ' . $param);
                                break;
                            case 'tariffchange':
                                $billing->settariff($login, $param);
                                log_register('CHANGE Tariff (' . $login . ') ON `' . $param . '`');
                                //optional user reset
                                if ($this->altCfg['TARIFFCHGRESET']) {
                                    $billing->resetuser($login);
                                    log_register('RESET User (' . $login . ')');
                                }
                                break;
                            case 'tagadd':
                                stg_add_user_tag($login, $param);
                                break;
                            case 'tagdel':
                                stg_del_user_tagid($login, $param);
                                break;
                            case 'freeze':
                                $billing->setpassive($login, 1);
                                log_register('CHANGE Passive (' . $login . ') ON 1');
                                break;
                            case 'unfreeze':
                                $billing->setpassive($login, 0);
                                log_register('CHANGE Passive (' . $login . ') ON 0');
                                break;
                            case 'reset':
                                $billing->resetuser($login);
                                log_register('RESET User (' . $login . ')');
                                break;
                            case 'setspeed':
                                zb_UserDeleteSpeedOverride($login);
                                zb_UserCreateSpeedOverride($login, $param);
                                $billing->resetuser($login);
                                log_register("RESET User (" . $login . ")");
                                break;
                            case 'down':
                                $billing->setdown($login, 1);
                                log_register('CHANGE Down (' . $login . ') ON 1');
                                break;
                            case 'undown':
                                $billing->setdown($login, 0);
                                log_register('CHANGE Down (' . $login . ') ON 0');
                                break;
                            case 'ao':
                                $billing->setao($login, 1);
                                log_register('CHANGE AlwaysOnline (' . $login . ') ON 1');
                                break;
                            case 'unao':
                                $billing->setao($login, 0);
                                log_register('CHANGE AlwaysOnline (' . $login . ') ON 0');
                                break;
                        }

                        //flush task from database
                        $this->setTaskIsDone($each['id']);
                    } else {
                        log_register('SCHEDULER FAIL ID [' . $taskId . '] USER (' . $each['login'] . ')  NON EXISTS');
                        $this->deleteTask($taskId);
                    }
                }
            }
        }
    }

}

?>