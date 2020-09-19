<?php

class TaskStates {

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
     * Contains all of task states as id=>statedata
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
        $this->stateTypes['STATE_DONE'] = ' ' . __('Done');
        $this->stateTypes['STATE_UNDONE'] = ' ' . __('Undone');
        $this->stateTypes['STATE_MOVED'] = ' ' . __('Moved');
        $this->stateTypes['STATE_CALLFAIL'] = '️ ' . __('Missed a phone call');
        $this->stateTypes['STATE_CANCELLED'] = ' ' . __('Canceled');
        $this->stateTypes['STATE_CONNECTOR'] = ' ' . __('Fixed connector');
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
        $log_data = '';
        $log_data_arr = array();
        $prevState = (isset($this->allStates[$taskId])) ? $this->allStates[$taskId]['state'] : '';
        $log_data .= __('State') . ':`' . $prevState . '` => `' . $stateId . '`';

        $log_data_arr['taskstate']['old'] = $prevState;
        $log_data_arr['taskstate']['new'] = $stateId;

        $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`)
        VALUES (NULL, '" . $taskId . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'modify', '" . serialize($log_data_arr) . "') ");
        nr_query($queryLogTask);
    }

    /**
     * Renders states control panel
     * 
     * @param int $taskId
     * 
     * @return string
     */
    public function renderStatePanel($takskId) {
        $result = '';
        $containerName = 'ajTaskState_' . $takskId;
        $result .= wf_AjaxLoader();
        $result .= wf_tag('div', false, '', 'id="' . $containerName . '"');
        if (!empty($this->stateTypes)) {
            foreach ($this->stateTypes as $stateId => $stateLabel) {
                $stateIcon = $this->stateIcons[$stateId];
                $controlClass = 'dashtask';
                if (isset($this->allStates[$takskId])) {
                    if ($this->allStates[$takskId]['state'] == $stateId) {
                        $controlClass .= ' todaysig';
                    }
                }

                $controlUrl = wf_AjaxLink(self::URL_BASE . '&edittask=' . $takskId . '&changestate=' . $stateId, wf_img($stateIcon), $containerName);

                $result .= wf_tag('div', false, $controlClass, '');
                $result .= $controlUrl;
                $result .= wf_delimiter(0) . $stateLabel;
                $result .= wf_tag('div', true);
            }
        }
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();

        return($result);
    }

}
