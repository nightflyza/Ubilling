<?php

/**
 * Performs tasks processing states management
 */
class TaskStates {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available state types as type=>label
     *
     * @var array
     */
    protected $stateTypes = array();

    /**
     * Contains available state type icons as type=>iconpath
     *
     * @var array
     */
    protected $stateIcons = array();

    /**
     * Contains available princess list logins as login=>login
     *
     * @var array
     */
    protected $princessList = array();

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Database abstraction layer placeholder
     *
     * @var object
     */
    protected $statesDb = '';

    /**
     * Default states icon file extension
     */
    const ICON_EXT = '.png';

    /**
     * Default state icons path
     */
    const ICON_PATH = 'skins/';

    /**
     * Name of default state icon if normal isnt found on icons path
     */
    const ICON_DEFAULT = 'state_default';

    /**
     * Base callback URL for controller
     */
    const URL_BASE = '?module=taskman';

    /**
     * Contains all of task states as taskid=>statedata
     *
     * @var array
     */
    protected $allStates = array();

    /**
     * Creates new task states instance
     * 
     * @param bool $loadDb Load states from DB
     */
    public function __construct($loadStatesDb = true) {
        $this->setMyLogin();
        $this->loadAlter();
        $this->loadPrincessList();
        $this->setTypes();
        $this->setTypesIcons();

        if ($loadStatesDb) {
            $this->InitDatabase();
            $this->loadAllTasksStates();
        }
    }

    /**
     * Sets default task states types for further usage
     * 
     * @return void
     */
    protected function setTypes() {
        $this->stateTypes['STATE_INPROGRESS'] = ' ' . __('Task is in progress');
        $this->stateTypes['STATE_DONE'] = ' ' . __('Done');
        $this->stateTypes['STATE_UNDONE'] = ' ' . __('Undone');
        $this->stateTypes['STATE_MOVED'] = ' ' . __('Moved');
        $this->stateTypes['STATE_CALLFAIL'] = '️ ' . __('Missed a phone call');
        $this->stateTypes['STATE_CANCELLED'] = ' ' . __('Canceled');
        if (!empty($this->princessList)) {
            $this->stateTypes['STATE_PRINCESS'] = ' ' . __('Princess was here'); //protected state. May be modified only by princess.
        }
    }

    /**
     * Sets current administrator login
     * 
     * @return void
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Checks is me an princess or not?
     * 
     * @return bool
     */
    public function iAmPrincess() {
        $result = false;
        if (isset($this->princessList[$this->myLogin])) {
            $result = true;
        }
        return($result);
    }

    /**
     * Loads system alter config into protected prop
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
     * Preloads princess list from config option
     * 
     * @return void
     */
    protected function loadPrincessList() {
        if (isset($this->altCfg['PRINCESS_LIST'])) {
            if (!empty($this->altCfg['PRINCESS_LIST'])) {
                $princessRaw = explode(',', $this->altCfg['PRINCESS_LIST']);
                if (!empty($princessRaw)) {
                    foreach ($princessRaw as $io => $eachPrincess) {
                        $eachPrincess = trim($eachPrincess);
                        $this->princessList[$eachPrincess] = $eachPrincess;
                    }
                }
            }
        }
    }

    /**
     * Returns localized state name by its ID
     * 
     * @param string $stateId
     * 
     * @return string
     */
    public function getStateName($stateId) {
        $result = '';
        if (isset($this->stateTypes[$stateId])) {
            $result .= $this->stateTypes[$stateId];
        } else {
            $result .= $stateId;
        }
        return($result);
    }

    /**
     * Fills icons array for all of available task state types
     * 
     * @return void
     */
    protected function setTypesIcons() {
        if (!empty($this->stateTypes)) {
            foreach ($this->stateTypes as $eachStateId => $eachStateLabel) {
                $fileName = self::ICON_PATH . strtolower($eachStateId) . self::ICON_EXT;
                if (file_exists($fileName)) {
                    $this->stateIcons[$eachStateId] = $fileName;
                } else {
                    $this->stateIcons[$eachStateId] = self::ICON_PATH . self::ICON_DEFAULT . self::ICON_EXT;
                }
            }
        }
    }

    /**
     * Initializes states database abstraction layer
     * 
     * @return void
     */
    protected function InitDatabase() {
        $this->statesDb = new NyanORM('taskstates');
    }

    /**
     * Loads all tasks states from database
     * 
     * @return void
     */
    protected function loadAllTasksStates() {
        $this->allStates = $this->statesDb->getAll('taskid');
    }

    /**
     * Returns all available state types as type=>name
     * 
     * @return array
     */
    public function getStateTypes() {
        return($this->stateTypes);
    }

    /**
     * Returns all available state icons as type=>iconpath
     * 
     * @return array
     */
    public function getStateIcons() {
        return($this->stateIcons);
    }

    /**
     * Returns task state if it exists
     * 
     * @param int $taskId
     * 
     * @return string/void
     */
    public function getTaskState($taskId) {
        $result = '';
        if (isset($this->allStates[$taskId])) {
            $result = $this->allStates[$taskId]['state'];
        }

        return($result);
    }

    /**
     * Sets new state for the some task
     * 
     * @param int $taskId
     * @param string $stateId
     * 
     * @return void/string on error
     */
    public function setTaskState($taskId, $stateId) {
        $result = '';
        $taskId = ubRouting::filters($taskId, 'int');
        $stateId = ubRouting::filters($stateId, 'mres');
        if (isset($this->stateTypes[$stateId])) {
            //need some cleanup
            if (isset($this->allStates[$taskId])) {
                $this->statesDb->where('taskid', '=', $taskId);
                $this->statesDb->delete();
            }

            //setting new state for the task
            $this->statesDb->data('taskid', $taskId);
            $this->statesDb->data('state', $stateId);
            $this->statesDb->data('date', curdatetime());
            $this->statesDb->create();

            //saving logs
            $this->logStateChange($taskId, $stateId);

            //updating internal struct
            $this->loadAllTasksStates();
        } else {
            $result .= __('Something went wrong') . ': ' . __('Status') . ' ' . $stateId . ' ' . __('Not found');
        }
        return($result);
    }

    /**
     * Logs state change for some task
     * 
     * @param int $taskId
     * @param string $stateId
     * 
     * @retrun void
     */
    protected function logStateChange($taskId, $stateId) {
        $taskId = ubRouting::filters($taskId, 'int');

        $log_data_arr = array();
        $prevState = (isset($this->allStates[$taskId])) ? $this->allStates[$taskId]['state'] : '';
        $logData['taskstate']['old'] = $prevState;
        $logData['taskstate']['new'] = $stateId;
        $storeLogData = serialize($logData);

        $taskmanLogs = new NyanORM('taskmanlogs');
        $taskmanLogs->data('taskid', $taskId);
        $taskmanLogs->data('date', curdatetime());
        $taskmanLogs->data('admin', whoami());
        $taskmanLogs->data('ip', @$_SERVER['REMOTE_ADDR']);
        $taskmanLogs->data('event', 'modify');
        $taskmanLogs->data('logs', $storeLogData);
        $taskmanLogs->create();
    }

    /**
     * Renders states control panel
     * 
     * @param int $takskId Existing task ID
     * @param bool $protected Deny of modification tasks states
     * 
     * @return string
     */
    public function renderStatePanel($takskId, $protected = false) {
        $result = '';
        $containerName = 'ajTaskState_' . $takskId;
        $result .= wf_AjaxLoader();
        $result .= wf_tag('div', false, '', 'id="' . $containerName . '"');
        if (!empty($this->stateTypes)) {
            if (isset($this->allStates[$takskId])) {
                $currentTaskState = $this->allStates[$takskId]['state'];
            }

            //take some decision about state change protection
            if ($protected) {
                $stateChangeble = false;
            } else {
                if ($currentTaskState == 'STATE_PRINCESS') { //protected state
                    if ($this->iAmPrincess()) {
                        $stateChangeble = true;
                    } else {
                        $stateChangeble = false;
                    }
                } else {
                    //normal states
                    $stateChangeble = true;
                }
            }

            foreach ($this->stateTypes as $stateId => $stateLabel) {
                $stateIcon = $this->stateIcons[$stateId];
                $controlClass = 'dashtask';

                //setting state as currently selected
                if ($currentTaskState == $stateId) {
                    $controlClass .= ' todaysig';
                }

                if ($stateId == 'STATE_PRINCESS') {
                    if ($protected) {
                        $stateChangeble = false;
                    } else {
                        if ($this->iAmPrincess()) {
                            $stateChangeble = true;
                        } else {
                            $stateChangeble = false;
                        }
                    }
                }


                if ($stateChangeble) {
                    $controlUrl = wf_AjaxLink(self::URL_BASE . '&edittask=' . $takskId . '&changestate=' . $stateId, wf_img($stateIcon), $containerName);
                } else {
                    $controlUrl = wf_img($stateIcon);
                }


                $result .= wf_tag('div', false, $controlClass, '');
                $result .= $controlUrl;
                $result .= wf_delimiter(0) . $stateLabel;
                $result .= wf_tag('div', true);
            }
        }
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        if ($protected) {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('You cant modify closed tasks state'), 'warning');
        }

        return($result);
    }

}
