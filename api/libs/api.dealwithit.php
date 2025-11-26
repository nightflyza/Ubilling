<?php

/**
 * Per-user task scheduler
 */
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
     * Contains admns Name as admin_login => admin_name
     *
     * @var array
     */
    protected $adminsName = array();

    /**
     * Discounts enable flag
     *
     * @var bool
     */
    protected $discountsFlag = false;

    /**
     * Discounts instance placeholder
     *
     * @var object
     */
    protected $discounts = '';

    /**
    * Contains current user login
    *
    * @var string
    */
    protected $userLogin = '';

    /**
     * Base module URL
     */
    const URL_ME = '?module=pl_dealwithit';

    public function __construct() {
        $this->loadAlter();
        $this->initDiscounts();
        $this->setActionNames();
        $this->setActionIcons();
        $this->setActionsURL();
        $this->setLogin();
        $this->loadTasks();
        $this->loadAdminsName();
    }

    /**
    * Sets user login to filter
    *
    * @param string $login
    *
    * @return void
    */
    protected function setLogin() {
        if (ubRouting::checkGet('username')) {
            $this->userLogin = ubRouting::get('username', 'mres');
        }
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
     * Inits discounts instance if enabled
     *
     * @return void
     */
    protected function initDiscounts() {
        if (@$this->altCfg[Discounts::OPTION_ENABLE]) {
            $this->discountsFlag = true;
            $this->discounts = new Discounts();
        }
    }

    /**
     * Loads existing tasks for further usage
     * 
     * @return void
     */
    protected function loadTasks() {
        $query = "SELECT * from `dealwithit`";
        $query.= ($this->userLogin) ? ' WHERE `login` = "' . $this->userLogin . '"' : '';
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

        if ($this->discountsFlag) {
            $this->actionNames['setdiscount'] = __('Change discount');
        }
    }

    /**
     * Returns all available dealwithit tasks
     * 
     * @return array
     */
    public function getAvailableTasks() {
        return ($this->allTasks);
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

        if ($this->discountsFlag) {
            $this->actionIcons['setdiscount'] = 'skins/icon_discount_16.png';
        }
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

        if ($this->discountsFlag) {
            $this->actions[self::URL_ME . '&ajinput=setdiscount'] = $this->actionNames['setdiscount'];
        }
    }

    /**
     * Loads admis Name
     * 
     * @return void
     */
    protected function loadAdminsName() {
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        if (!empty($employeeLogins)) {
            foreach ($employeeLogins as $login => $name) {
                $this->adminsName[$login] = $name;
            }
        }
    }

    /**
     * Init admin Name
     * 
     * @param string $admin
     * @return void
     */
    protected function initAdminName($admin) {
        $result = '';
        if (!empty($admin)) {
            $result = (isset($this->adminsName[$admin])) ? $this->adminsName[$admin] : $admin;
        }
        return ($result);
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
        if ($done) {
            $query = "UPDATE `dealwithithist` SET `done` = '1', `datetimedone` = '" . $mtime . "' WHERE `dealwithithist`.`originalid` = '" . $id . "'";
        } else {
            $query = "INSERT INTO `dealwithithist` (`id`,`originalid`,`mtime`,`date`,`login`,`action`,`param`,`note`,`admin`,`done`) VALUES";
            $query .= "(NULL,'" . $id . "','" . $mtime . "','" . $date . "', '" . $login . "','" . $action . "','" . $param . "','" . $note . "','" . $admin . "','0')";
        }
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
     * @return int
     */
    public function createTask($date, $login, $action, $param, $note) {
        $dateF = mysql_real_escape_string($date);
        $loginF = mysql_real_escape_string($login);
        $actionF = mysql_real_escape_string($action);
        $paramF = mysql_real_escape_string($param);
        $noteF = mysql_real_escape_string($note);
        $query = "INSERT INTO `dealwithit` (`id`,`date`,`login`,`action`,`param`,`note`) VALUES";
        $query .= "(NULL,'" . $dateF . "','" . $loginF . "','" . $actionF . "','" . $paramF . "','" . $noteF . "');";
        nr_query($query);
        $newId = simple_get_lastid('dealwithit');
        $this->logTask($newId, $dateF, $loginF, $actionF, $paramF, $noteF, false);
        log_register('SCHEDULER CREATE ID [' . $newId . '] (' . $login . ')  DATE `' . $date . ' `ACTION `' . $action . '` NOTE `' . $note . '`');
        return ($newId);
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
    public function renderCreateForm() {
        $result = '';
        $result .= wf_AjaxLoader();
        $inputs = wf_HiddenInput('newschedlogin', $this->userLogin);
        $inputs .= wf_DatePickerPreset('newscheddate', date('Y-m-d', strtotime('+1 day')), true) . ' ' . __('Target date') . wf_tag('br');
        $inputs .= wf_AjaxSelectorAC('ajparamcontainer', $this->actions, __('Task'), '', true);
        $inputs .= wf_AjaxContainer('ajparamcontainer');

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
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
                    $result .= wf_HiddenInput('newschedaction', 'addcash');
                    $result .= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    $allCashTypes = zb_CashGetTypesNamed();
                    $result .= wf_Selector('newschedcashtype', $allCashTypes, __('Cash type'), '', true);
                    break;
                case 'corrcash':
                    $result .= wf_HiddenInput('newschedaction', 'corrcash');
                    $result .= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    break;
                case 'setcash':
                    $result .= wf_HiddenInput('newschedaction', 'setcash');
                    $result .= wf_TextInput('newschedparam', __('Sum'), '', true, 5);
                    break;
                case 'credit':
                    $result .= wf_HiddenInput('newschedaction', 'credit');
                    $result .= wf_TextInput('newschedparam', __('New credit'), '', true, 5);
                    break;
                case 'creditexpire':
                    $result .= wf_HiddenInput('newschedaction', 'creditexpire');
                    $result .= wf_DatePickerPreset('newschedparam', curdate()) . ' ' . __('New credit expire') . wf_tag('br');
                    break;
                case 'tariffchange':
                    $result .= wf_HiddenInput('newschedaction', 'tariffchange');
                    $result .= web_tariffselector('newschedparam') . ' ' . __('Tariff name') . wf_tag('br');
                    break;
                case 'tagadd':
                    $result .= wf_HiddenInput('newschedaction', 'tagadd');
                    $allTags = array();
                    $allTagsRaw = simple_queryall("SELECT * from `tagtypes`");
                    if (!empty($allTagsRaw)) {
                        foreach ($allTagsRaw as $io => $each) {
                            $allTags[$each['id']] = $each['tagname'];
                        }
                    }
                    $result .= wf_Selector('newschedparam', $allTags, __('Tag'), '', true);
                    break;
                case 'tagdel':
                    $result .= wf_HiddenInput('newschedaction', 'tagdel');
                    $allTags = array();
                    $allTagsRaw = simple_queryall("SELECT * from `tagtypes`");
                    if (!empty($allTagsRaw)) {
                        foreach ($allTagsRaw as $io => $each) {
                            $allTags[$each['id']] = $each['tagname'];
                        }
                    }
                    $result .= wf_Selector('newschedparam', $allTags, __('Tag'), '', true);
                    break;
                case 'freeze':
                    $result .= wf_HiddenInput('newschedaction', 'freeze');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'unfreeze':
                    $result .= wf_HiddenInput('newschedaction', 'unfreeze');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'reset':
                    $result .= wf_HiddenInput('newschedaction', 'reset');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'setspeed':
                    $result .= wf_HiddenInput('newschedaction', 'setspeed');
                    $result .= wf_TextInput('newschedparam', __('New speed override'), '', true, 5);
                    break;
                case 'down':
                    $result .= wf_HiddenInput('newschedaction', 'down');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'undown':
                    $result .= wf_HiddenInput('newschedaction', 'undown');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'ao':
                    $result .= wf_HiddenInput('newschedaction', 'ao');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'unao':
                    $result .= wf_HiddenInput('newschedaction', 'unao');
                    $result .= wf_HiddenInput('newschedparam', '');
                    break;
                case 'setdiscount':
                    $result .= wf_HiddenInput('newschedaction', 'setdiscount');
                    $result .= wf_TextInput('newschedparam', __('New discount'), '', true, 5, 'digits');
                    break;
            }

            $result .= wf_TextInput('newschednote', __('Notes'), '', true, 30);
            $result .= wf_Submit(__('Create'));

            if ($request == 'noaction') {
                $result = __('Please select action');
            }
        }
        die($result);
    }

    /**
     * Creates new schedule task
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
                                $cashType = (wf_CheckPost(array('newschedcashtype'))) ? vf($_POST['newschedcashtype']) : 1;
                                if ($cashType != 1) {
                                    $param .= '|' . $cashType;
                                }
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
                    case 'setdiscount':
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
     * 
     * @return void/error notice
     */
    public function catchCreateMassRequest() {
        $result = '';
        if (wf_CheckPost(array('newschedloginsarr', 'newschedaction', 'newscheddate'))) {
            if (!empty($_POST['_logins'])) {
                $date = $_POST['newscheddate'];
                $action = $_POST['newschedaction'];
                $param = $_POST['newschedparam'];
                $note = $_POST['newschednote'];
                $logins = array_keys($_POST['_logins']);
                if (zb_checkDate($date)) {
                    switch ($action) {
                            //this action types requires non empty parameter
                        case 'addcash':
                            if ($param) {
                                if (zb_checkMoney($param)) {
                                    $cashType = (wf_CheckPost(array('newschedcashtype'))) ? vf($_POST['newschedcashtype']) : 1;
                                    if ($cashType != 1) {
                                        $param .= '|' . $cashType;
                                    }
                                    foreach ($logins as $login) {
                                        $this->createTask($date, $login, $action, $param, $note);
                                    }
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
                                    foreach ($logins as $login) {
                                        $this->createTask($date, $login, $action, $param, $note);
                                    }
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
                                    foreach ($logins as $login) {
                                        $this->createTask($date, $login, $action, $param, $note);
                                    }
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
                                    foreach ($logins as $login) {
                                        $this->createTask($date, $login, $action, $param, $note);
                                    }
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
                                    foreach ($logins as $login) {
                                        $this->createTask($date, $login, $action, $param, $note);
                                    }
                                } else {
                                    $result = __('Wrong date format');
                                }
                            } else {
                                $result = __('No all of required fields is filled');
                            }
                            break;
                        case 'tariffchange':
                            if ($param) {
                                foreach ($logins as $login) {
                                    $this->createTask($date, $login, $action, $param, $note);
                                }
                            } else {
                                $result = __('No all of required fields is filled');
                            }
                            break;
                        case 'tagadd':
                            if ($param) {
                                foreach ($logins as $login) {
                                    $this->createTask($date, $login, $action, $param, $note);
                                }
                            } else {
                                $result = __('No all of required fields is filled');
                            }
                            break;
                        case 'tagdel':
                            if ($param) {
                                foreach ($logins as $login) {
                                    $this->createTask($date, $login, $action, $param, $note);
                                }
                            } else {
                                $result = __('No all of required fields is filled');
                            }
                            break;
                            //for this task types parameter may be empty
                        case 'freeze':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'unfreeze':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'reset':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'setspeed':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'down':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'undown':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'ao':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'unao':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                        case 'setdiscount':
                            foreach ($logins as $login) {
                                $this->createTask($date, $login, $action, $param, $note);
                            }
                            break;
                    }
                } else {
                    $result = __('Wrong date format');
                }
            } else {
                $result = __('You did not select any user');
            }
        } else {
            $result = __('Something went wrong');
        }

        return ($result);
    }

    /**
     * Renders available tasks data list
     * 
     * @param sring $login
     * 
     * @return string
     */
    public function AjaxDataTasksList() {
        $messages = new UbillingMessageHelper();
        $tmpArr = array();
        $allRealNames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $json = new wf_JqDtHelper();

        if (!empty($this->allTasks)) {
            foreach ($this->allTasks as $io => $each) {
                if (empty($this->userLogin)) {
                    $tmpArr[$io] = $each;
                } else {
                    if ($this->userLogin == $each['login']) {
                        $tmpArr[$io] = $each;
                    }
                }
            }
        }

        if (!empty($tmpArr)) {
            $curDate = curdate();
            foreach ($tmpArr as $io => $each) {
                $actionIcon = (isset($this->actionIcons[$each['action']])) ? wf_img_sized($this->actionIcons[$each['action']], $this->actionNames[$each['action']], '12', '12') . ' ' : '';
                $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                $taskControls = wf_JSAlert(self::URL_ME . '&username=' . $each['login'] . '&deletetaskid=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
                if (ispos($each['param'], '|')) {
                    $paramFiltered = strstr($each['param'], '|', true);
                } else {
                    $paramFiltered = $each['param'];
                }
                $data[] = $each['id'];
                $data[] = $this->colorizeData($each['date'], $curDate, $each['date']);
                $data[] = $profileLink;
                $data[] = @$allAddress[$each['login']];
                $data[] = @$allRealNames[$each['login']];
                $data[] = $actionIcon . $this->colorizeData($each['date'], $curDate, $this->actionNames[$each['action']]);
                $data[] = $paramFiltered;
                $data[] = $each['note'];
                $data[] = $taskControls;
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns colorized string field based on planning date
     * 
     * @param string $date
     * @param string $curDate
     * @param string $string
     * 
     * @return string
     */
    protected function colorizeData($date, $curDate, $string) {
        $result = '';
        if ($date > $curDate) {
            $result = wf_tag('font', false, '', '') . $string . wf_tag('font', true);
        }
        if ($date == $curDate) {
            $result = wf_tag('font', false, '', 'color="#d45f00"') . $string . wf_tag('font', true);
        }

        if ($date < $curDate) {
            $result = wf_tag('font', false, '', 'color="#b71e00"') . $string . wf_tag('font', true);
        }
        return ($result);
    }

    /**
     * Returns container of tasks list
     *
     * @return string
     */
    public function renderTasksListAjax() {
        $result = '';
        $columns = array('ID', 'Target date', 'Login', 'Address', 'Real name', 'Task', 'Parameter', 'Notes', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $module_link = (empty($this->userLogin)) ? '?module=report_dealwithit&ajax=true' : '?module=pl_dealwithit&ajax=true&username=' . $this->userLogin;
        $result = wf_JqDtLoader($columns, $module_link, false, 'Tasks', 100, $opts);
        return ($result);
    }

    /**
     * Renders available tasks data
     *
     * @return string
     */
    public function AjaxDataTasksHistory() {
        $tmpArr = array();
        $allRealNames = zb_UserGetAllRealnames();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $query = "SELECT * from `dealwithithist` ORDER by `id` DESC";
        $allTasksHistory = simple_queryall($query);
        $json = new wf_JqDtHelper();

        if (!empty($allTasksHistory)) {
            foreach ($allTasksHistory as $io => $each) {
                $tmpArr[$io] = $each;
            }
        }

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $actionIcon = (isset($this->actionIcons[$each['action']])) ? wf_img_sized($this->actionIcons[$each['action']], $this->actionNames[$each['action']], '12', '12') . ' ' : '';
                $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                if (ispos($each['param'], '|')) {
                    $paramFiltered = strstr($each['param'], '|', true);
                } else {
                    $paramFiltered = $each['param'];
                }
                $data[] = $each['originalid'];
                $data[] = $each['date'];
                $data[] = $each['mtime'];
                $data[] = $each['datetimedone'];
                $data[] = $profileLink;
                $data[] = @$allAddress[$each['login']];
                $data[] = @$allRealNames[$each['login']];
                $data[] = $actionIcon . @$this->actionNames[$each['action']];
                $data[] = $paramFiltered;
                $data[] = $each['note'];
                $data[] = web_bool_led($each['done']);
                $data[] = $this->initAdminName($each['admin']);
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns container of tasks
     *
     * @return string
     */
    public function renderTasksHistoryAjax() {
        $result = '';
        $columns = array('ID', 'Target date', 'Create date', 'Changed', 'Login', 'Address', 'Real name', 'Task', 'Parameter', 'Notes', 'Done', 'Admin');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, '?module=report_dealwithit&history=true&ajax=true', false, 'Tasks', 100, $opts);
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
                if (strtotime($each['date']) <= strtotime($curdate)) {
                    if (isset($allUsers[$each['login']])) {
                        $login = $each['login'];
                        $param = $each['param'];

                        switch ($each['action']) {
                            case 'addcash':
                                if (ispos($param, '|')) {
                                    $dataRaw = explode('|', $param);
                                    $summ = $dataRaw[0];
                                    $cashType = $dataRaw[1];
                                } else {
                                    $summ = $param;
                                    $cashType = 1;
                                }
                                zb_CashAdd($login, $summ, 'add', $cashType, 'SCHEDULED');
                                break;
                            case 'corrcash':
                                $corrNote = 'SCHEDULED';
                                if (ispos($each['note'], 'DEFSALE:')) {
                                    $corrNote = $each['note'];
                                }
                                zb_CashAdd($login, $param, 'correct', 1, $corrNote);
                                break;
                            case 'setcash':
                                zb_CashAdd($login, $param, 'set', 1, 'SCHEDULED');
                                break;
                            case 'credit':
                                $billing->setcredit($login, $param);
                                log_register('USER CREDIT CHANGE (' . $login . ') ON ' . $param);
                                break;
                            case 'creditexpire':
                                $billing->setcreditexpire($login, $param);
                                log_register('USER CREDIT EXPIRE CHANGE (' . $login . ') ON ' . $param);
                                break;
                            case 'tariffchange':
                                $billing->settariff($login, $param);
                                log_register('USER TARIFF CHANGE (' . $login . ') ON `' . $param . '`');
                                //optional user reset
                                if ($this->altCfg['TARIFFCHGRESET']) {
                                    $billing->resetuser($login);
                                    log_register('USER RESET (' . $login . ')');
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
                                log_register('USER PASSIVE CHANGE (' . $login . ') ON 1');
                                break;
                            case 'unfreeze':
                                $billing->setpassive($login, 0);
                                log_register('USER PASSIVE CHANGE (' . $login . ') ON 0');
                                break;
                            case 'reset':
                                $billing->resetuser($login);
                                log_register('USER RESET (' . $login . ')');
                                break;
                            case 'setspeed':
                                zb_UserDeleteSpeedOverride($login);
                                zb_UserCreateSpeedOverride($login, $param);
                                $billing->resetuser($login);
                                log_register("USER RESET (" . $login . ")");
                                break;
                            case 'down':
                                $billing->setdown($login, 1);
                                log_register('USER DOWN CHANGE (' . $login . ') ON 1');
                                break;
                            case 'undown':
                                $billing->setdown($login, 0);
                                log_register('USER DOWN CHANGE (' . $login . ') ON 0');
                                break;
                            case 'ao':
                                $billing->setao($login, 1);
                                log_register('USER ALWAYSONLINE CHANGE (' . $login . ') ON 1');
                                break;
                            case 'unao':
                                $billing->setao($login, 0);
                                log_register('USER ALWAYSONLINE CHANGE (' . $login . ') ON 0');
                                break;
                            case 'setdiscount':
                                $this->discounts->setDiscount($login, $param);
                                log_register('USER DISCOUNT CHANGE (' . $login . ') PERCENT `' . $param . '`');
                                break;
                        }

                        //flush task from database
                        $this->setTaskIsDone($each['id']);
                    } else {
                        log_register('SCHEDULER FAIL ID [' . $each['id'] . '] USER (' . $each['login'] . ')  NON EXISTS');
                        $this->deleteTask($each['id']);
                    }
                }
            }
        }
    }

    /**
     * Returns user profile fileds search form
     * 
     * @return string
     */
    public function renderDealWithItControl() {
        $messages = new UbillingMessageHelper();
        $controls = wf_Link('?module=report_dealwithit', wf_img('skins/dealwithitsmall.png') . ' ' . __('Available Held jobs for all users'), false, 'ubButton');
        $controls .= wf_Link('?module=report_dealwithit&history=true', wf_img('skins/icon_calendar.gif') . ' ' . __('History'), false, 'ubButton');

        $result = show_window('', $controls);
        $result .= show_window(__('User search'), $this->renderUsersSearchForm());
        if (wf_CheckPost(array('dealwithit_search')) and isset($_POST['dealwithit_search']['search_by'])) {

            $logins = $this->SearchUsers($_POST['dealwithit_search']);
            if (!empty($logins)) {
                show_window(__('Create new task'), $this->renderUsersSearchResults($logins));
            } else {
                show_window('', $messages->getStyledMessage(__('Query returned empty result'), 'info'));
            }
        } elseif (wf_CheckPost(array('dealwithit_search')) and ! isset($_POST['dealwithit_search']['search_by']) and isset($_POST['dealwithit_search']['exclude'])) {
            show_error(__('The search parameter is not selected. No, what to exclude from the request'));
        } elseif (wf_CheckPost(array('dealwithit_search')) and ! isset($_POST['dealwithit_search']['search_by']) and ! isset($_POST['dealwithit_search']['exclude'])) {
            show_error(__('No request parameters set'));
        }

        return ($result);
    }

    /**
     * Returns user profile fileds search form
     * 
     * @return string
     */
    protected function renderUsersSearchResults($logins) {
        $result = '';
        if (!empty($logins)) {
            $availableUsers = zb_UserGetAllStargazerDataAssoc();
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Login'));
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Real name'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Active'));
            $cells .= wf_TableCell(__('Balance'));
            $cells .= wf_TableCell(__('Credit'));
            $cells .= wf_TableCell(__('Held jobs for this user'));

            $cells .= wf_TableCell(wf_CheckInput('check', '', false, false), '', 'sorttable_nosort');
            $rows = wf_TableRow($cells, 'row1');

            $id = '1';
            $allRealNames = zb_UserGetAllRealnames();
            $allAddress = zb_AddressGetFulladdresslistCached();

            $quary_user_data = "SELECT `login`,`Cash`,`Credit`,`Passive`,`Tariff`,`IP` from `users`";
            $user_data = simple_queryall($quary_user_data);
            $user_data_arr = array();
            if (!empty($user_data)) {
                foreach ($user_data as $logindata) {
                    $user_data_arr[$logindata['login']]['Cash'] = $logindata['Cash'];
                    $user_data_arr[$logindata['login']]['Credit'] = $logindata['Credit'];
                    $user_data_arr[$logindata['login']]['Passive'] = $logindata['Passive'];
                    $user_data_arr[$logindata['login']]['Tariff'] = $logindata['Tariff'];
                    $user_data_arr[$logindata['login']]['ip'] = $logindata['IP'];
                }
            }

            if (!empty($this->allTasks)) {
                $tmpArr = array();
                foreach ($this->allTasks as $io => $each) {
                    $login = $each['login'];
                    $tmpArr[$login][] = $each['action'];
                }
            }

            foreach ($logins as $login) {
                //is this user real?
                if (isset($availableUsers[$login])) {
                    //finance check
                    $cash = $user_data_arr[$login]['Cash'];
                    $credit = $user_data_arr[$login]['Credit'];
                    $passive = $user_data_arr[$login]['Passive'];
                    $tariff = $user_data_arr[$login]['Tariff'];
                    $ip = $user_data_arr[$login]['ip'];
                    // Display user status
                    $act = '<img src=skins/icon_active.gif>' . __('Yes');
                    if ($cash < '-' . $credit) {
                        $act = '<img src=skins/icon_inactive.gif>' . __('No');
                    }
                    $act .= $passive ? '<br> <img src=skins/icon_passive.gif>' . __('Freezed') : '';

                    $cells = wf_TableCell($id);
                    $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . $login, false, ''));
                    $cells .= wf_TableCell(@$allAddress[$login]);
                    $cells .= wf_TableCell(@$allRealNames[$login]);
                    $cells .= wf_TableCell($ip);
                    $cells .= wf_TableCell($tariff);
                    $cells .= wf_TableCell($act);
                    $cells .= wf_TableCell($cash);
                    $cells .= wf_TableCell($credit);
                    if (isset($tmpArr[$login])) {
                        $cells_temp = '';
                        foreach ($tmpArr[$login] as $task) {
                            $actionIcon = (isset($this->actionIcons[$task])) ? wf_img_sized($this->actionIcons[$task], $this->actionNames[$task], '12', '12') . ' ' : '';
                            $cells_temp .= $actionIcon . $this->actionNames[$task] . wf_tag('br');
                        }
                        $cells .= wf_TableCell($cells_temp);
                    } else {
                        $cells .= wf_TableCell('');
                    }
                    $cells .= wf_TableCell(wf_CheckInput('_logins[' . $login . ']', '', false, false));
                    $rows .= wf_TableRow($cells, 'row3');
                    $id++;
                }
            }

            $result .= wf_AjaxLoader();

            $inputs = wf_HiddenInput('newschedloginsarr', true);
            $inputs .= wf_DatePickerPreset('newscheddate', date('Y-m-d', strtotime('+1 day')), true) . ' ' . __('Target date') . wf_tag('br');
            $inputs .= wf_AjaxSelectorAC('ajparamcontainer', $this->actions, __('Task'), '', true);
            $inputs .= wf_AjaxContainer('ajparamcontainer');
            $inputs .= wf_tag('br');
            $inputs .= wf_TableBody($rows, '100%', 0, 'sortable');

            $result .= wf_Form('', 'POST', $inputs, '');
        }

        return ($result);
    }

    /**
     * Returns search form
     * 
     * @return void
     */
    protected function renderUsersSearchForm() {

        $allcity = array();
        $tmpCity = zb_AddressGetCityAllData();

        if (!empty($tmpCity)) {
            foreach ($tmpCity as $io => $each) {
                $allcity[$each['id']] = $each['cityname'];
            }
        }
        $param_selector_status = array(
            '',
            'active' => __('Active'),
            'AlwaysOnline' => __('Always Online'),
            'inactive' => __('Inactive'),
            'down' => __('Disconnected'),
            'frozen' => __('Frozen'),
        );
        // Load tariffs
        $alltariffs = zb_TariffsGetAll();
        $tariffs_options = array();

        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $eachtariff) {
                $tariffs_options[$eachtariff['name']] = $eachtariff['name'];
            }
        }
        // Load services
        $allservices = multinet_get_services();
        $services_options = array();

        if (!empty($allservices)) {
            foreach ($allservices as $io => $eachservice) {
                $services_options[$eachservice['netid']] = $eachservice['desc'];
            }
        }
        // Load tags
        $query_alltags = "SELECT `id`,`tagname` FROM `tagtypes`";
        $alltags = simple_queryall($query_alltags);
        $tags_options = array();

        if (!empty($alltags)) {
            foreach ($alltags as $io => $eachtag) {
                $tags_options[$eachtag['id']] = $eachtag['tagname'];
            }
        }
        // Load switches
        $query_allswitches = "SELECT * from `switches` ORDER BY `location`";
        $allswitches = simple_queryall($query_allswitches);
        $switches_options = array();

        if (!empty($allswitches)) {
            foreach ($allswitches as $io => $eachsw) {
                $switches_options[$eachsw['id']] = $eachsw['ip'] . ' - ' . $eachsw['location'];
            }
        }

        // Рисуем форму, которая включает в запрос пользователей
        $cells = wf_TableCell(wf_tag('b') . __('Include in search query') . wf_tag('b'), '', '', 'colspan="3"');
        $rows = wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('All fields'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][all_fields]', '', false));
        $cells .= wf_TableCell(wf_TextInput('dealwithit_search[all_fields]', '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('City'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][city_id]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[city_id]', $allcity, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Tariff'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][tariff]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[tariff]', $tariffs_options, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Tariff contains'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][tariff_contains]', '', false));
        $cells .= wf_TableCell(wf_TextInput('dealwithit_search[tariff_contains]', '', '', ''));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Status'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][user_status]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[user_status]', $param_selector_status, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Services'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][services]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[services]', $services_options, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Tags'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][tags]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[tags]', $tags_options, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        $cells = wf_TableCell(__('Switch'));
        $cells .= wf_TableCell(wf_CheckInput('dealwithit_search[search_by][switch]', '', false));
        $cells .= wf_TableCell(wf_Selector('dealwithit_search[switch]', $switches_options, '', '', false));
        $rows .= wf_TableRow($cells, 'row2');

        // Рисуем форму, которая исключает из запроса пользователей
        $cells_ex = wf_TableCell(wf_tag('b') . __('Exclude from search query') . wf_tag('/b'), '', '', 'colspan="3"');
        $rows_ex = wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('All fields'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_all_fields]', '', false));
        $cells_ex .= wf_TableCell(wf_TextInput('dealwithit_search[ex_all_fields]', '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('City'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_city_id]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_city_id]', $allcity, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Tariff'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_tariff]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_tariff]', $tariffs_options, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Tariff contains'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_tariff_contains]', '', false));
        $cells_ex .= wf_TableCell(wf_TextInput('dealwithit_search[ex_tariff_contains]', '', '', ''));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Status'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_user_status]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_user_status]', $param_selector_status, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Services'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_services]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_services]', $services_options, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Tags'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_tags]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_tags]', $tags_options, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $cells_ex = wf_TableCell(__('Switch'));
        $cells_ex .= wf_TableCell(wf_CheckInput('dealwithit_search[exclude][ex_switch]', '', false));
        $cells_ex .= wf_TableCell(wf_Selector('dealwithit_search[ex_switch]', $switches_options, '', '', false));
        $rows_ex .= wf_TableRow($cells_ex, 'row2');

        $rows_ex .= wf_TableRow(wf_TableCell(wf_Submit('Search')));

        $form = wf_TableBody($rows, '', 0, '', 'style="float: left; padding-right: 20px;"');
        $form .= wf_TableBody($rows_ex, '', 0, '', 'style="float: left;"');
        $result = wf_Form("", "POST", $form, 'glamour');

        return ($result);
    }

    /**
     * Returns search form
     * 
     * @return array 
     */
    protected function SearchUsers(array $dealwithit_search) {

        $result = array();
        $result_exclude = array();
        $search_field = $_POST['dealwithit_search']['search_by'];
        $exclude_field = @$_POST['dealwithit_search']['exclude'];

        // Search login by City
        if (isset($search_field['city_id']) and $search_field['city_id'] == 'on') {
            // И шо я только курю
            $query = "SELECT `login` FROM `street` LEFT JOIN city ON street.cityid=city.id
                        LEFT JOIN build ON build.streetid=street.id
                        LEFT JOIN apt ON apt.buildid=build.id
                        RIGHT JOIN address ON address.aptid=apt.id
                        WHERE cityid = '" . vf($_POST['dealwithit_search']['city_id'], 3) . "'";
            $data_city = simple_queryall($query);
            if (!empty($data_city)) {
                foreach ($data_city as $login) {
                    $result[] = $login['login'];
                }
            }
        }
        // Search login by Tariff
        if (isset($search_field['tariff']) and $search_field['tariff'] == 'on') {
            $query = "SELECT `login` FROM `users` WHERE `Tariff` = '" . $_POST['dealwithit_search']['tariff'] . "'";
            $data_tariff = simple_queryall($query);
            if (!empty($data_tariff)) {
                foreach ($data_tariff as $login) {
                    $result[] = $login['login'];
                }
            }
        }
        // Search login by Tariff contains string
        if (isset($search_field['tariff_contains']) and $search_field['tariff_contains'] == 'on') {
            $query = "SELECT `login` FROM `users` WHERE `Tariff` LIKE '%" . $_POST['dealwithit_search']['tariff_contains'] . "%'";
            $data_tariff = simple_queryall($query);
            if (!empty($data_tariff)) {
                foreach ($data_tariff as $login) {
                    $result[] = $login['login'];
                }
            }
        }
        // Search login by status
        if (isset($search_field['user_status']) and $search_field['user_status'] == 'on') {
            $need_status = $_POST['dealwithit_search']['user_status'];
            if (!empty($need_status)) {
                switch ($need_status) {
                    case 'active':
                        $where = "`Cash` >= -`Credit`  ";
                        break;
                    case 'AlwaysOnline':
                        $where = "`AlwaysOnline` ='1'";
                        break;
                    case 'inactive':
                        $where = "`Cash` < -`Credit`  ";
                        break;
                    case 'down':
                        $where = "`Down` ='1'";
                        break;
                    case 'frozen':
                        $where = "`Passive` ='1'";
                        break;
                }
                $query = "SELECT `login` FROM `users` WHERE " . $where;
                $data_status = simple_queryall($query);
                if (!empty($data_status)) {
                    foreach ($data_status as $login) {
                        $result[] = $login['login'];
                    }
                }
            }
        }
        // Search login by Services
        if (isset($search_field['services']) and $search_field['services'] == 'on') {
            $query = "SELECT `login` FROM `users` INNER JOIN `nethosts` USING (`ip`) WHERE `nethosts`.`netid` = '" . vf($_POST['dealwithit_search']['services'], 3) . "'";
            $data_services = simple_queryall($query);
            if (!empty($data_services)) {
                foreach ($data_services as $login) {
                    $result[] = $login['login'];
                }
            }
        }
        // hide dead users array
        // This should be here, because below we will work with tags, among which may be buried users
        if (!empty($result) and $this->altCfg['DEAD_HIDE']) {
            if (!empty($this->altCfg['DEAD_TAGID'])) {
                $deadUsers = array();
                $tagDead = vf($this->altCfg['DEAD_TAGID'], 3);
                $query_dead = "SELECT `login` from `tags` WHERE `tagid`='" . $tagDead . "'";
                $alldead = simple_queryall($query_dead);
                if (!empty($alldead)) {
                    foreach ($alldead as $idead => $eachDead) {
                        $deadUsers[] = $eachDead['login'];
                    }
                    $result = array_diff($result, $deadUsers);
                }
            }
        }
        // Search login by all fields
        if (isset($search_field['all_fields']) and $search_field['all_fields'] == 'on') {
            $data_fileds = zb_UserSearchAllFields($_POST['dealwithit_search']['all_fields'], false);
            if (!empty($data_fileds) and is_array($data_fileds)) {
                foreach ($data_fileds as $login) {
                    $result[] = $login;
                }
            }
        }
        // Search login by Tag
        if (isset($search_field['tags']) and $search_field['tags'] == 'on') {
            $query = "SELECT `login` from `tags` WHERE `tagid`='" . vf($_POST['dealwithit_search']['tags'], 3) . "'";
            $data_tags = simple_queryall($query);
            if (!empty($data_tags)) {
                foreach ($data_tags as $login) {
                    $result[] = $login['login'];
                }
            }
        }
        // Search login by Switch
        if (isset($search_field['switch']) and $search_field['switch'] == 'on') {
            $query = "SELECT `login` from `switchportassign` WHERE `switchid`='" . vf($_POST['dealwithit_search']['switch'], 3) . "'";
            $data_switches = simple_queryall($query);
            if (!empty($data_switches)) {
                foreach ($data_switches as $login) {
                    $result[] = $login['login'];
                }
            }
        }

        // Исключаем из запроса пользователей
        // Начинаем заполнять массив исключения
        // Exclude login from request by City
        if (isset($exclude_field['ex_city_id']) and $exclude_field['ex_city_id'] == 'on') {
            // И шо я только курю уже дважды
            $query = "SELECT `login` FROM `street` LEFT JOIN city ON street.cityid=city.id
                        LEFT JOIN build ON build.streetid=street.id
                        LEFT JOIN apt ON apt.buildid=build.id
                        RIGHT JOIN address ON address.aptid=apt.id
                        WHERE cityid = '" . vf($_POST['dealwithit_search']['ex_city_id'], 3) . "'";
            $data_city = simple_queryall($query);
            if (!empty($data_city)) {
                foreach ($data_city as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }
        //  Exclude login from request by Tariff
        if (isset($exclude_field['ex_tariff']) and $exclude_field['ex_tariff'] == 'on') {
            $query = "SELECT `login` FROM `users` WHERE `Tariff` = '" . $_POST['dealwithit_search']['ex_tariff'] . "'";
            $data_tariff = simple_queryall($query);
            if (!empty($data_tariff)) {
                foreach ($data_tariff as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }
        // Search login by Tariff contains string
        if (isset($exclude_field['ex_tariff_contains']) and $exclude_field['ex_tariff_contains'] == 'on') {
            $query = "SELECT `login` FROM `users` WHERE `Tariff` LIKE '%" . $_POST['dealwithit_search']['ex_tariff_contains'] . "%'";
            $data_tariff = simple_queryall($query);
            if (!empty($data_tariff)) {
                foreach ($data_tariff as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }
        // Exclude login from request by status
        if (isset($exclude_field['ex_user_status']) and $exclude_field['ex_user_status'] == 'on') {
            $need_status_exclude = $_POST['dealwithit_search']['ex_user_status'];
            if (!empty($need_status_exclude)) {
                switch ($need_status_exclude) {
                    case 'active':
                        $where = "`Cash` >= -`Credit`  ";
                        break;
                    case 'AlwaysOnline':
                        $where = "`AlwaysOnline` ='1'";
                        break;
                    case 'inactive':
                        $where = "`Cash` < -`Credit`  ";
                        break;
                    case 'down':
                        $where = "`Down` ='1'";
                        break;
                    case 'frozen':
                        $where = "`Passive` ='1'";
                        break;
                }
                $query_exclude = "SELECT `login` FROM `users` WHERE " . $where;
                $data_status_exclude = simple_queryall($query_exclude);
                if (!empty($data_status_exclude)) {
                    foreach ($data_status_exclude as $login) {
                        $result_exclude[] = $login['login'];
                    }
                }
            }
        }
        // Exclude login from request by Services
        if (isset($exclude_field['ex_services']) and $exclude_field['ex_services'] == 'on') {
            $query_exclude = "SELECT `login` FROM `users` INNER JOIN `nethosts` USING (`ip`) WHERE `nethosts`.`netid` = '" . vf($_POST['dealwithit_search']['ex_services'], 3) . "'";
            $data_services_exclude = simple_queryall($query_exclude);
            if (!empty($data_services_exclude)) {
                foreach ($data_services_exclude as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }
        // Exclude dead users array
        // This should be here, because below we will work with tags, among which may be buried users
        if (!empty($result_exclude) and $this->altCfg['DEAD_HIDE']) {
            if (!empty($this->altCfg['DEAD_TAGID'])) {
                $deadUsers = array();
                $tagDead = vf($this->altCfg['DEAD_TAGID'], 3);
                $query_dead = "SELECT `login` from `tags` WHERE `tagid`='" . $tagDead . "'";
                $alldead = simple_queryall($query_dead);
                if (!empty($alldead)) {
                    foreach ($alldead as $idead => $eachDead) {
                        $deadUsers[] = $eachDead['login'];
                    }
                    $result_exclude = array_diff($result_exclude, $deadUsers);
                }
            }
        }
        // Exclude login from request by all fields
        if (isset($exclude_field['ex_all_fields']) and $exclude_field['ex_all_fields'] == 'on') {
            $data_fileds_exclude = zb_UserSearchAllFields($_POST['dealwithit_search']['ex_all_fields'], false);
            if (!empty($data_fileds_exclude) and is_array($data_fileds_exclude)) {
                foreach ($data_fileds_exclude as $login) {
                    $result_exclude[] = $login;
                }
            }
        }
        // Exclude login from request by Tag
        if (isset($exclude_field['ex_tags']) and $exclude_field['ex_tags'] == 'on') {
            $query_exclude = "SELECT `login` from `tags` WHERE `tagid`='" . vf($_POST['dealwithit_search']['ex_tags'], 3) . "'";
            $data_tags_exclude = simple_queryall($query_exclude);
            if (!empty($data_tags_exclude)) {
                foreach ($data_tags_exclude as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }
        // Exclude login by switch
        if (isset($exclude_field['ex_switch']) and $exclude_field['ex_switch'] == 'on') {
            $query_exclude = "SELECT `login` from `switchportassign` WHERE `switchid`='" . vf($_POST['dealwithit_search']['ex_switch'], 3) . "'";
            $data_switches_exclude = simple_queryall($query_exclude);
            if (!empty($data_switches_exclude)) {
                foreach ($data_switches_exclude as $login) {
                    $result_exclude[] = $login['login'];
                }
            }
        }

        // Сам прцоцесс исключения пользователей из результатов поиска
        if (!empty($result) and ! empty($result_exclude)) {
            $result = array_diff($result, $result_exclude);
        }

        // Delete duplicates that come from more that one selected options
        if (!empty($result)) {
            $result = array_unique($result);
        }

        return ($result);
    }
}
