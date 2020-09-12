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
     * Contains all of task states as id=>statedata
     *
     * @var array
     */
    protected $allStates = array();

    public function __construct() {
        $this->setTypes();
        $this->setTypesIcons();
        $this->InitDatabase();
        $this->loadAllTasksStates();
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
        $this->allStates = $this->statesDb->getAll('id');
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
        $result .= wf_tag('div', false);
        if (!empty($this->stateTypes)) {
            foreach ($this->stateTypes as $stateId => $stateLabel) {
                $stateIcon= $this->stateIcons[$stateId];
                $result .= wf_tag('div', false, 'dashtask');
                $result.= wf_img($stateIcon);
                $result.= wf_delimiter(0).$stateLabel;
                $result .= wf_tag('div', true);
            }
        }
        $result .= wf_tag('div', true);
        $result.= wf_CleanDiv();

        return($result);
    }

}
